<?php
/**
 * File: payment-status.php
 * Module: Payment Gateway (Lab 7 & 8)
 * Description: Small JSON endpoint polled by payment-qr.php. It asks the
 *              middleware for the bank's view of a QR code and, the moment
 *              the bank reports the transfer, has the middleware credit the
 *              order. Returning a single short JSON object keeps the polling
 *              cheap and keeps all payment logic in the middleware.
 *
 *              Response shape:
 *                {"state": "Pending|Paid|Expired|Cancelled|Error",
 *                 "message": "...", "reference": "..."}
 *
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 11/11/2026
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/payment-gateway.php';
require_once __DIR__ . '/../config/db-config.php';
 
requireCustomer();
 
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
 
/**
 * Sends a JSON reply and ends the request.
 *
 * @param string $state     One of Pending, Paid, Expired, Cancelled, Error
 * @param string $message   Text the page may show the customer
 * @param string $reference Bank reference number, when the payment is done
 * @return void
 */
function reply($state, $message = '', $reference = '')
{
    echo json_encode([
        'state'     => $state,
        'message'   => $message,
        'reference' => $reference
    ]);
    exit;
}
 
$orderId = (int)($_GET['order_id'] ?? 0);
$qrId    = trim((string)($_GET['qr_id'] ?? ''));
$userId  = (int)$_SESSION['userId'];
 
if ($orderId <= 0 || $qrId === '') {
    reply('Error', 'Missing order or payment reference.');
}
 
// ---- The order must belong to the signed-in customer ----
$conn = getConnection();
$stmt = $conn->prepare(
    "SELECT order_id FROM orders WHERE order_id = ? AND user_id = ? LIMIT 1"
);
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$owns = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
 
if (!$owns) {
    reply('Error', 'That order could not be found in your account.');
}
 
// ---- Ask the middleware what the bank says ----
$status = getQrPaymentStatus($qrId, $orderId);
 
if (!$status['success']) {
    // A transport problem is not a failed payment. Report it as Pending so
    // the page keeps waiting, unless the bank positively rejected the code.
    if (in_array($status['code'], ['GATEWAY_UNREACHABLE', 'GATEWAY_RATE_LIMITED',
                                   'GATEWAY_MALFORMED'], true)) {
        reply('Pending', $status['message']);
    }
 
    reply('Error', $status['message']);
}
 
$state = (string)($status['data']['payment_state'] ?? 'Pending');
 
if ($state === 'Expired') {
    reply('Expired', 'This QR code has expired. Please generate a new one.');
}
 
if ($state === 'Cancelled') {
    reply('Cancelled', 'This payment was cancelled.');
}
 
if ($state !== 'Paid') {
    reply('Pending', 'Waiting for payment.');
}
 
// ---- The bank reports payment: credit the order ----
// settleQrPayment() re-reads the bank itself and checks the amount, so it
// is safe to call here and safe to call more than once.
$settled = settleQrPayment($orderId, $qrId);
 
if (!$settled['success']) {
    reply('Error', $settled['message']);
}
 
reply('Paid', $settled['message'], (string)($settled['data']['reference_no'] ?? ''));
 