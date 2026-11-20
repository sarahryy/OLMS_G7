<?php
/**
 * File: index.php  (src/api/bank/index.php)
 * Module: Bank Siber (simulated external payment provider)
 * Description: JSON API exposed by the simulated SiberQR service. It is
 *              deliberately written as a standalone endpoint that knows
 *              nothing about StationeryMart: it accepts signed requests,
 *              issues QR codes, reports their status and records
 *              confirmations. The shop reaches it only over HTTP through
 *              the payment-gateway middleware, never by including its code.
 *
 *              Endpoints:
 *                POST ?action=create   issue a QR code for an amount
 *                GET  ?action=status   report the state of a QR code
 *                POST ?action=confirm  record that a payer has paid
 *                POST ?action=cancel   void an unpaid QR code
 *
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 11/11/2026
 */
 
declare(strict_types=1);
 
require_once __DIR__ . '/bank-config.php';
require_once __DIR__ . '/../../config/db-config.php';
 
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
 
/**
 * Sends a JSON response and ends the request.
 *
 * Every exit path from this API goes through this function, so no route
 * can accidentally return HTML, an empty body, or a PHP warning that the
 * merchant's middleware would then fail to parse.
 *
 * @param int   $httpStatus HTTP status code to send
 * @param array $body       Payload to encode as JSON
 * @return void
 */
function respond(int $httpStatus, array $body): void
{
    http_response_code($httpStatus);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}
 
/**
 * Reads and decodes the JSON request body.
 *
 * @return array Decoded body, or an empty array when absent or invalid
 */
function readJsonBody(): array
{
    $raw = file_get_contents('php://input');
 
    if ($raw === false || trim($raw) === '') {
        return [];
    }
 
    $decoded = json_decode($raw, true);
 
    return is_array($decoded) ? $decoded : [];
}
 
/**
 * Applies a simple per-merchant rate limit.
 *
 * Counts requests in the current clock minute and refuses further calls
 * once the configured ceiling is reached. The merchant's middleware is
 * expected to recognise HTTP 429 and back off rather than retry blindly.
 *
 * @param string $merchantId Merchant making the request
 * @return bool True when the request is within the limit
 */
function withinRateLimit(string $merchantId): bool
{
    $file = sys_get_temp_dir() . '/siberqr_rate_' . md5($merchantId) . '.txt';
    $minute = (int)date('YmdHi');
 
    $count = 0;
    if (is_readable($file)) {
        $parts = explode(':', (string)file_get_contents($file));
        if (count($parts) === 2 && (int)$parts[0] === $minute) {
            $count = (int)$parts[1];
        }
    }
 
    $count++;
    @file_put_contents($file, $minute . ':' . $count);
 
    return $count <= SIBER_RATE_LIMIT_PER_MINUTE;
}
 
// ------------------------------------------------------------------
// Route the request
// ------------------------------------------------------------------
 
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
 
try {
    $conn = getConnection();
} catch (Throwable $e) {
    // The bank is unavailable. Report it as a server-side fault so the
    // middleware can distinguish it from a rejected request.
    respond(503, [
        'status'  => 'error',
        'code'    => 'BANK_UNAVAILABLE',
        'message' => 'The payment service is temporarily unavailable.'
    ]);
}
 
switch ($action) {
 
    // --------------------------------------------------------------
    // Issue a QR code for a given amount
    // --------------------------------------------------------------
    case 'create':
        if ($method !== 'POST') {
            respond(405, [
                'status'  => 'error',
                'code'    => 'METHOD_NOT_ALLOWED',
                'message' => 'This endpoint accepts POST requests only.'
            ]);
        }
 
        $body       = readJsonBody();
        $merchantId = trim((string)($body['merchant_id'] ?? ''));
        $orderRef   = trim((string)($body['order_ref']   ?? ''));
        $amountRaw  = $body['amount'] ?? null;
        $signature  = trim((string)($body['signature']   ?? ''));
 
        // --- Validate every field before touching the ledger ---
        if ($merchantId === '' || $orderRef === '' || $signature === '') {
            respond(400, [
                'status'  => 'error',
                'code'    => 'MISSING_FIELD',
                'message' => 'merchant_id, order_ref and signature are required.'
            ]);
        }
 
        if (!is_numeric($amountRaw)) {
            respond(400, [
                'status'  => 'error',
                'code'    => 'INVALID_AMOUNT',
                'message' => 'amount must be a number.'
            ]);
        }
 
        $amount = round((float)$amountRaw, 2);
 
        if ($amount <= 0 || $amount > 50000) {
            respond(400, [
                'status'  => 'error',
                'code'    => 'AMOUNT_OUT_OF_RANGE',
                'message' => 'amount must be greater than 0.00 and at most 50000.00.'
            ]);
        }
 
        if ($merchantId !== SIBER_MERCHANT_ID) {
            respond(401, [
                'status'  => 'error',
                'code'    => 'UNKNOWN_MERCHANT',
                'message' => 'This merchant is not registered with Bank Siber.'
            ]);
        }
 
        if (!withinRateLimit($merchantId)) {
            respond(429, [
                'status'  => 'error',
                'code'    => 'RATE_LIMITED',
                'message' => 'Too many requests. Please retry in a moment.'
            ]);
        }
 
        // --- Verify the signature, using a timing-safe comparison ---
        $expected = siberSign($merchantId, $orderRef, $amount);
 
        if (!hash_equals($expected, $signature)) {
            respond(401, [
                'status'  => 'error',
                'code'    => 'BAD_SIGNATURE',
                'message' => 'Request signature does not match the request body.'
            ]);
        }
 
        // --- Reuse an existing unpaid QR for the same order ---
        // Without this, a customer who refreshes the payment page would
        // generate a second QR code for the same order.
        $stmt = $conn->prepare(
            "SELECT qr_id, amount, expires_at
             FROM bank_transactions
             WHERE order_ref = ? AND status = 'Pending' AND expires_at > NOW()
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->bind_param('s', $orderRef);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
 
        if ($existing) {
            respond(200, [
                'status'      => 'ok',
                'qr_id'       => $existing['qr_id'],
                'qr_payload'  => 'SIBERQR|' . $existing['qr_id'] . '|'
                                 . number_format((float)$existing['amount'], 2, '.', ''),
                'amount'      => (float)$existing['amount'],
                'expires_at'  => $existing['expires_at'],
                'reused'      => true
            ]);
        }
 
        // --- Issue a new QR code ---
        $qrId     = strtoupper(bin2hex(random_bytes(8)));
        $expires  = date('Y-m-d H:i:s', time() + SIBER_QR_LIFETIME);
 
        $stmt = $conn->prepare(
            "INSERT INTO bank_transactions
                (qr_id, merchant_id, order_ref, amount, status, expires_at)
             VALUES (?, ?, ?, ?, 'Pending', ?)"
        );
        $stmt->bind_param('sssds', $qrId, $merchantId, $orderRef, $amount, $expires);
        $stmt->execute();
        $stmt->close();
 
        respond(201, [
            'status'     => 'ok',
            'qr_id'      => $qrId,
            'qr_payload' => 'SIBERQR|' . $qrId . '|' . number_format($amount, 2, '.', ''),
            'amount'     => $amount,
            'expires_at' => $expires,
            'reused'     => false
        ]);
        break;
 
    // --------------------------------------------------------------
    // Report the state of a QR code
    // --------------------------------------------------------------
    case 'status':
        $qrId = trim((string)($_GET['qr_id'] ?? ''));
 
        if ($qrId === '') {
            respond(400, [
                'status'  => 'error',
                'code'    => 'MISSING_FIELD',
                'message' => 'qr_id is required.'
            ]);
        }
 
        $stmt = $conn->prepare(
            "SELECT qr_id, order_ref, amount, status, reference_no,
                    payer_name, expires_at, paid_at
             FROM bank_transactions WHERE qr_id = ? LIMIT 1"
        );
        $stmt->bind_param('s', $qrId);
        $stmt->execute();
        $txn = $stmt->get_result()->fetch_assoc();
        $stmt->close();
 
        if (!$txn) {
            respond(404, [
                'status'  => 'error',
                'code'    => 'NOT_FOUND',
                'message' => 'No transaction exists with that identifier.'
            ]);
        }
 
        // Expire the code lazily, on read, rather than with a scheduled job.
        if ($txn['status'] === 'Pending' && strtotime($txn['expires_at']) < time()) {
            $upd = $conn->prepare(
                "UPDATE bank_transactions SET status = 'Expired' WHERE qr_id = ?"
            );
            $upd->bind_param('s', $qrId);
            $upd->execute();
            $upd->close();
            $txn['status'] = 'Expired';
        }
 
        respond(200, [
            'status'        => 'ok',
            'qr_id'         => $txn['qr_id'],
            'order_ref'     => $txn['order_ref'],
            'amount'        => (float)$txn['amount'],
            'payment_state' => $txn['status'],
            'reference_no'  => $txn['reference_no'],
            'payer_name'    => $txn['payer_name'],
            'expires_at'    => $txn['expires_at'],
            'paid_at'       => $txn['paid_at']
        ]);
        break;
 
    // --------------------------------------------------------------
    // Record that the payer has approved the transfer
    // --------------------------------------------------------------
    case 'confirm':
        if ($method !== 'POST') {
            respond(405, [
                'status'  => 'error',
                'code'    => 'METHOD_NOT_ALLOWED',
                'message' => 'This endpoint accepts POST requests only.'
            ]);
        }
 
        $body      = readJsonBody();
        $qrId      = trim((string)($body['qr_id']      ?? ''));
        $payerName = trim((string)($body['payer_name'] ?? 'Bank Siber Customer'));
 
        if ($qrId === '') {
            respond(400, [
                'status'  => 'error',
                'code'    => 'MISSING_FIELD',
                'message' => 'qr_id is required.'
            ]);
        }
 
        $stmt = $conn->prepare(
            "SELECT qr_id, amount, status, expires_at, reference_no
             FROM bank_transactions WHERE qr_id = ? LIMIT 1"
        );
        $stmt->bind_param('s', $qrId);
        $stmt->execute();
        $txn = $stmt->get_result()->fetch_assoc();
        $stmt->close();
 
        if (!$txn) {
            respond(404, [
                'status'  => 'error',
                'code'    => 'NOT_FOUND',
                'message' => 'No transaction exists with that identifier.'
            ]);
        }
 
        // Paying twice must not create a second payment. Report the
        // existing reference instead, so a repeated tap is harmless.
        if ($txn['status'] === 'Paid') {
            respond(200, [
                'status'        => 'ok',
                'payment_state' => 'Paid',
                'reference_no'  => $txn['reference_no'],
                'message'       => 'This transaction was already paid.',
                'duplicate'     => true
            ]);
        }
 
        if ($txn['status'] !== 'Pending') {
            respond(409, [
                'status'  => 'error',
                'code'    => 'NOT_PAYABLE',
                'message' => 'This transaction is ' . strtolower((string)$txn['status']) . '.'
            ]);
        }
 
        if (strtotime((string)$txn['expires_at']) < time()) {
            $upd = $conn->prepare(
                "UPDATE bank_transactions SET status = 'Expired' WHERE qr_id = ?"
            );
            $upd->bind_param('s', $qrId);
            $upd->execute();
            $upd->close();
 
            respond(409, [
                'status'  => 'error',
                'code'    => 'EXPIRED',
                'message' => 'This QR code has expired. Please request a new one.'
            ]);
        }
 
        $referenceNo = 'SBR' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
 
        $stmt = $conn->prepare(
            "UPDATE bank_transactions
             SET status = 'Paid', reference_no = ?, payer_name = ?, paid_at = NOW()
             WHERE qr_id = ? AND status = 'Pending'"
        );
        $stmt->bind_param('sss', $referenceNo, $payerName, $qrId);
        $stmt->execute();
        $changed = $stmt->affected_rows;
        $stmt->close();
 
        if ($changed === 0) {
            // Another request confirmed it between our read and our write.
            respond(409, [
                'status'  => 'error',
                'code'    => 'CONFLICT',
                'message' => 'The transaction state changed. Please check the status.'
            ]);
        }
 
        respond(200, [
            'status'        => 'ok',
            'payment_state' => 'Paid',
            'reference_no'  => $referenceNo,
            'amount'        => (float)$txn['amount'],
            'message'       => 'Payment approved.',
            'duplicate'     => false
        ]);
        break;
 
    // --------------------------------------------------------------
    // Void an unpaid QR code
    // --------------------------------------------------------------
    case 'cancel':
        $body = readJsonBody();
        $qrId = trim((string)($body['qr_id'] ?? ''));
 
        if ($qrId === '') {
            respond(400, [
                'status'  => 'error',
                'code'    => 'MISSING_FIELD',
                'message' => 'qr_id is required.'
            ]);
        }
 
        $stmt = $conn->prepare(
            "UPDATE bank_transactions SET status = 'Cancelled'
             WHERE qr_id = ? AND status = 'Pending'"
        );
        $stmt->bind_param('s', $qrId);
        $stmt->execute();
        $stmt->close();
 
        respond(200, ['status' => 'ok', 'payment_state' => 'Cancelled']);
        break;
 
    // --------------------------------------------------------------
    default:
        respond(404, [
            'status'  => 'error',
            'code'    => 'UNKNOWN_ACTION',
            'message' => 'Supported actions are create, status, confirm and cancel.'
        ]);
}
 