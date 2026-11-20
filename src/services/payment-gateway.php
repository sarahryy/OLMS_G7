<?php
/**
 * File: payment-gateway.php
 * Module: Payment Gateway Middleware (Lab 7 & 8)
 * Description: Middleware layer between StationeryMart and the Bank Siber
 *              SiberQR API. Every call to the bank passes through this file.
 *              No page in the system contacts the bank directly, and no part
 *              of the shop includes the bank's code: the only coupling is the
 *              JSON contract defined here.
 *
 *              The middleware is responsible for:
 *                - signing outgoing requests so the bank can verify them
 *                - enforcing a connect and read timeout
 *                - classifying every failure (timeout, client error, server
 *                  error, malformed body) into a single result shape
 *                - recording every call in payment_gateway_log
 *                - refusing to credit a payment whose amount does not match
 *                  the order it claims to settle
 *
 *              Every public function returns the same array shape:
 *                ['success' => bool, 'code' => string,
 *                 'message' => string, 'data' => array]
 *              so calling pages never have to interpret an HTTP status or a
 *              cURL error number themselves.
 *
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 11/11/2026
 */

require_once __DIR__ . '/../config/db-config.php';
require_once __DIR__ . '/../api/bank/bank-config.php';

// ---- Working out where the bank API lives ----
//
// The path is derived from the request rather than hardcoded, so the same
// codebase runs on a developer's WAMP install, on a machine reached by its
// LAN address, behind a tunnel and on a deployed host without being edited.
// SCRIPT_NAME points at the running script inside /src/<folder>/, so two
// levels up is /src.
$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/src/pages/x.php')), '/\\');
$publicHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$proto      = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
$scheme     = $proto !== ''
    ? $proto
    : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');

// The path to the bank, shared by both addresses below.
define('SIBER_API_PATH', $scriptDir . '/api/bank/index.php');

// The address a browser would use. Kept for reference and used as a
// fallback when the loopback address is not reachable.
define('SIBER_API_PUBLIC', $scheme . '://' . $publicHost . SIBER_API_PATH);

// The address this server uses to reach the bank. The bank runs on the same
// machine, so the call is made over the loopback interface: it never leaves
// the server, needs no DNS, and avoids TLS entirely. This matters behind a
// tunnel or a reverse proxy, where the public address would route the
// request out to the internet and back in again, and where a development
// PHP build often cannot verify the certificate on the way.
define('SIBER_API_LOOPBACK', 'http://127.0.0.1' . SIBER_API_PATH);

// The host name the loopback request claims to be for. Shared hosting
// serves many sites from one address, so without this header the request
// would reach the wrong virtual host.
define('SIBER_API_HOST_HEADER', $publicHost);

// Connect and read timeouts, in seconds. A bank that does not answer
// within this window is treated as unavailable rather than left to hang.
// The loopback attempt gets a shorter connect timeout because a server
// talking to itself either answers immediately or not at all.
define('SIBER_CONNECT_TIMEOUT', 3);
define('SIBER_LOOPBACK_CONNECT_TIMEOUT', 2);
define('SIBER_READ_TIMEOUT', 8);

// Order status the shop advances to once payment is confirmed.
// 'Preparing' rather than 'Shipped': nothing has physically been
// dispatched yet, and the admin screen expects the sequence to advance
// one stage at a time.
define('SIBER_STATUS_AFTER_PAYMENT', 'Preparing');


/**
 * Writes one line to the gateway audit log.
 *
 * Logging is best-effort by design: a failure to record a call must never
 * prevent the call itself from returning a result to the customer.
 *
 * @param int|null $orderId    Order the call relates to, when known
 * @param string   $endpoint   Bank action that was called
 * @param int|null $httpStatus HTTP status received, or null on no response
 * @param string   $outcome    One of the payment_gateway_log outcome values
 * @param string   $message    Short human-readable summary
 * @param int      $durationMs Round-trip time in milliseconds
 * @return void
 */
function logGatewayCall($orderId, $endpoint, $httpStatus, $outcome, $message, $durationMs = 0)
{
    try {
        $conn = getConnection();
        $stmt = $conn->prepare(
            "INSERT INTO payment_gateway_log
                (order_id, endpoint, http_status, outcome, message, duration_ms)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $trimmed = substr((string)$message, 0, 255);
        $stmt->bind_param(
            'isissi',
            $orderId, $endpoint, $httpStatus, $outcome, $trimmed, $durationMs
        );
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Throwable $e) {
        // Deliberately swallowed. See the note above.
        error_log('payment-gateway: could not write audit log: ' . $e->getMessage());
    }
}


/**
 * Makes one attempt to reach the bank at a given base address.
 *
 * Separated from callBankApi() so the same request can be tried over the
 * loopback interface first and over the public address only if that fails.
 *
 * @param string      $base       Full URL of the bank endpoint
 * @param string      $action     Bank action to call
 * @param string      $method     GET or POST
 * @param array       $payload    Query string for GET, JSON body for POST
 * @param string|null $hostHeader Host header to send, or null to let the
 *                                URL decide
 * @param int         $connectTimeout Connect timeout in seconds
 * @return array ['raw' => string|false, 'status' => int|null,
 *                'error' => string, 'ms' => int]
 */
function attemptBankCall($base, $action, $method, array $payload,
                         $hostHeader, $connectTimeout)
{
    $url = $base . '?action=' . urlencode($action);

    if ($method === 'GET' && !empty($payload)) {
        $url .= '&' . http_build_query($payload);
    }

    $body    = ($method === 'POST') ? json_encode($payload) : null;
    $started = microtime(true);

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Merchant-Id: ' . SIBER_MERCHANT_ID
    ];

    // Shared hosting serves many sites from one address, so a loopback
    // request must say which site it is for.
    if ($hostHeader !== null && $hostHeader !== '') {
        $headers[] = 'Host: ' . $hostHeader;
    }

    $raw            = false;
    $httpStatus     = null;
    $transportError = '';

    if (function_exists('curl_init')) {
        // --- Preferred path: cURL, which gives real timeout control ---
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_TIMEOUT        => SIBER_READ_TIMEOUT,
            CURLOPT_HTTPHEADER     => $headers
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw            = curl_exec($ch);
        $httpStatus     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errNo          = curl_errno($ch);
        $transportError = curl_error($ch);
        curl_close($ch);

        if ($errNo !== 0) {
            $raw = false;
        }
    } else {
        // --- Fallback: stream context, for a PHP build without cURL ---
        $context = stream_context_create([
            'http' => [
                'method'        => $method,
                'header'        => implode("\r\n", $headers) . "\r\n",
                'content'       => $body,
                'timeout'       => SIBER_READ_TIMEOUT,
                'ignore_errors' => true
            ]
        ]);

        $raw = @file_get_contents($url, false, $context);

        if (isset($http_response_header[0])
            && preg_match('#HTTP/\S+\s+(\d{3})#', $http_response_header[0], $m)) {
            $httpStatus = (int)$m[1];
        }
    }

    return [
        'raw'    => $raw,
        'status' => $httpStatus,
        'error'  => $transportError,
        'ms'     => (int)round((microtime(true) - $started) * 1000)
    ];
}


/**
 * Calls the bank API and normalises the outcome.
 *
 * This is the only function in the system that opens a network connection
 * to the bank. It never throws: every failure mode is converted into the
 * standard result array, so the caller handles one shape rather than a
 * mixture of exceptions, false values and HTTP codes.
 *
 * The bank runs on the same server, so the loopback address is tried first.
 * That keeps the request inside the machine, which is both faster and
 * necessary behind a tunnel or reverse proxy, where the public address
 * would route the call out to the internet and back. The public address is
 * used only if loopback produces nothing.
 *
 * @param string   $action  Bank action: create, status, confirm or cancel
 * @param string   $method  GET or POST
 * @param array    $payload Request body for POST, or query string for GET
 * @param int|null $orderId Order the call relates to, for the audit log
 * @return array Standard result array
 */
function callBankApi($action, $method, array $payload = [], $orderId = null)
{
    // Loopback first, public address as a fallback.
    $attempt = attemptBankCall(
        SIBER_API_LOOPBACK, $action, $method, $payload,
        SIBER_API_HOST_HEADER, SIBER_LOOPBACK_CONNECT_TIMEOUT
    );

    $durationMs     = $attempt['ms'];
    $raw            = $attempt['raw'];
    $httpStatus     = $attempt['status'];
    $transportError = $attempt['error'];

    if ($raw === false || $raw === null || $raw === '') {
        $fallback = attemptBankCall(
            SIBER_API_PUBLIC, $action, $method, $payload,
            null, SIBER_CONNECT_TIMEOUT
        );

        $durationMs    += $fallback['ms'];
        $raw            = $fallback['raw'];
        $httpStatus     = $fallback['status'];
        $transportError = $attempt['error'] !== ''
            ? 'loopback: ' . $attempt['error'] . '; public: ' . $fallback['error']
            : $fallback['error'];
    }

    // --- No response at all: the bank is unreachable or too slow ---
    if ($raw === false || $raw === null || $raw === '') {
        $msg = 'Could not reach the payment service.'
             . ($transportError !== '' ? ' (' . $transportError . ')' : '');
        logGatewayCall($orderId, $action, $httpStatus, 'Timeout', $msg, $durationMs);

        return [
            'success' => false,
            'code'    => 'GATEWAY_UNREACHABLE',
            'message' => 'The payment service is not responding. '
                       . 'Your order has not been charged. Please try again shortly.',
            'data'    => []
        ];
    }

    // --- A response arrived, but it is not the JSON we expect ---
    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
        logGatewayCall(
            $orderId, $action, $httpStatus, 'Malformed',
            'Response was not valid JSON: ' . substr($raw, 0, 120), $durationMs
        );

        return [
            'success' => false,
            'code'    => 'GATEWAY_MALFORMED',
            'message' => 'The payment service returned an unreadable response. '
                       . 'Your order has not been charged.',
            'data'    => []
        ];
    }

    // --- The bank answered with an error of its own ---
    if ($httpStatus === null || $httpStatus >= 400) {
        $outcome = ($httpStatus !== null && $httpStatus >= 500) ? 'ServerError' : 'ClientError';
        $bankCode = (string)($decoded['code'] ?? 'UNKNOWN');

        if ($bankCode === 'BAD_SIGNATURE') {
            $outcome = 'SignatureFailed';
        }

        logGatewayCall(
            $orderId, $action, $httpStatus, $outcome,
            $bankCode . ': ' . (string)($decoded['message'] ?? ''), $durationMs
        );

        // Rate limiting is reported distinctly so the caller can ask the
        // customer to wait rather than suggesting the payment failed.
        if ($httpStatus === 429) {
            return [
                'success' => false,
                'code'    => 'GATEWAY_RATE_LIMITED',
                'message' => 'The payment service is busy. Please wait a moment and try again.',
                'data'    => $decoded
            ];
        }

        return [
            'success' => false,
            'code'    => 'GATEWAY_' . $bankCode,
            'message' => (string)($decoded['message'] ?? 'The payment service rejected the request.'),
            'data'    => $decoded
        ];
    }

    logGatewayCall($orderId, $action, $httpStatus, 'Success', 'OK', $durationMs);

    return [
        'success' => true,
        'code'    => 'OK',
        'message' => (string)($decoded['message'] ?? 'Request completed.'),
        'data'    => $decoded
    ];
}


/**
 * Requests a QR code from the bank for a given order.
 *
 * The amount is read from the orders table rather than accepted from the
 * caller, so a tampered form field cannot change what the customer is
 * asked to pay.
 *
 * @param int $orderId Order to be paid
 * @return array Standard result array; on success data holds qr_id,
 *               qr_payload, amount and expires_at
 */
function createQrPayment($orderId)
{
    $orderId = (int)$orderId;

    if ($orderId <= 0) {
        return [
            'success' => false,
            'code'    => 'INVALID_ORDER',
            'message' => 'That order reference is not valid.',
            'data'    => []
        ];
    }

    $conn = getConnection();
    $stmt = $conn->prepare(
        "SELECT order_id, total_amount, status FROM orders WHERE order_id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$order) {
        return [
            'success' => false,
            'code'    => 'ORDER_NOT_FOUND',
            'message' => 'That order could not be found.',
            'data'    => []
        ];
    }

    if ($order['status'] === 'Cancelled') {
        return [
            'success' => false,
            'code'    => 'ORDER_CANCELLED',
            'message' => 'This order has been cancelled and cannot be paid.',
            'data'    => []
        ];
    }

    $amount   = round((float)$order['total_amount'], 2);
    $orderRef = 'OLMS-' . $orderId;

    return callBankApi('create', 'POST', [
        'merchant_id' => SIBER_MERCHANT_ID,
        'order_ref'   => $orderRef,
        'amount'      => $amount,
        'signature'   => siberSign(SIBER_MERCHANT_ID, $orderRef, $amount)
    ], $orderId);
}


/**
 * Asks the bank for the current state of a QR code.
 *
 * @param string   $qrId    Identifier returned by createQrPayment()
 * @param int|null $orderId Order the code relates to, for the audit log
 * @return array Standard result array
 */
function getQrPaymentStatus($qrId, $orderId = null)
{
    $qrId = trim((string)$qrId);

    if ($qrId === '') {
        return [
            'success' => false,
            'code'    => 'INVALID_QR',
            'message' => 'No payment reference was supplied.',
            'data'    => []
        ];
    }

    return callBankApi('status', 'GET', ['qr_id' => $qrId], $orderId);
}


/**
 * Credits a confirmed bank payment against an order.
 *
 * This function is the point at which money reported by the bank becomes
 * a paid order in the shop, so it is deliberately strict:
 *
 *   1. It re-reads the state from the bank rather than trusting anything
 *      the browser submitted.
 *   2. It refuses if the amount the bank collected does not match the
 *      order total, which prevents an underpayment settling an order.
 *   3. It refuses if the bank's order_ref does not name this order.
 *   4. It is safe to call twice: a payment already marked Paid returns
 *      success without writing a second record.
 *
 * All writes happen in one transaction, so an order can never be left
 * marked paid with its shipping record untouched.
 *
 * @param int    $orderId Order being settled
 * @param string $qrId    Bank transaction identifier
 * @return array Standard result array
 */
function settleQrPayment($orderId, $qrId)
{
    $orderId = (int)$orderId;
    $qrId    = trim((string)$qrId);

    // --- Ask the bank; never trust the browser ---
    $bank = getQrPaymentStatus($qrId, $orderId);

    if (!$bank['success']) {
        return $bank;
    }

    $state     = (string)($bank['data']['payment_state'] ?? '');
    $bankAmt   = round((float)($bank['data']['amount'] ?? 0), 2);
    $bankRef   = (string)($bank['data']['reference_no'] ?? '');
    $orderRef  = (string)($bank['data']['order_ref'] ?? '');

    if ($state !== 'Paid') {
        return [
            'success' => false,
            'code'    => 'NOT_PAID',
            'message' => 'The bank has not recorded this payment as completed.',
            'data'    => ['payment_state' => $state]
        ];
    }

    // --- The transaction must name this order ---
    if ($orderRef !== 'OLMS-' . $orderId) {
        logGatewayCall(
            $orderId, 'settle', 200, 'ClientError',
            'order_ref mismatch: bank said ' . $orderRef
        );

        return [
            'success' => false,
            'code'    => 'ORDER_MISMATCH',
            'message' => 'This payment belongs to a different order.',
            'data'    => []
        ];
    }

    $conn = getConnection();

    try {
        $conn->begin_transaction();

        // --- Re-read the order inside the transaction ---
        $stmt = $conn->prepare(
            "SELECT total_amount, status FROM orders WHERE order_id = ? FOR UPDATE"
        );
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) {
            $conn->rollback();
            $conn->close();
            return [
                'success' => false,
                'code'    => 'ORDER_NOT_FOUND',
                'message' => 'That order could not be found.',
                'data'    => []
            ];
        }

        $orderTotal = round((float)$order['total_amount'], 2);

        // --- The amounts must agree to the sen ---
        if (abs($bankAmt - $orderTotal) > 0.001) {
            $conn->rollback();
            $conn->close();

            logGatewayCall(
                $orderId, 'settle', 200, 'ClientError',
                'amount mismatch: bank ' . $bankAmt . ' vs order ' . $orderTotal
            );

            return [
                'success' => false,
                'code'    => 'AMOUNT_MISMATCH',
                'message' => 'The amount received does not match this order total. '
                           . 'Please contact support before paying again.',
                'data'    => []
            ];
        }

        // --- Already settled? Report success and change nothing ---
        $stmt = $conn->prepare(
            "SELECT payment_id, payment_status FROM payments
             WHERE order_id = ? ORDER BY payment_id DESC LIMIT 1"
        );
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($payment && $payment['payment_status'] === 'Paid') {
            $conn->commit();
            $conn->close();

            return [
                'success' => true,
                'code'    => 'ALREADY_PAID',
                'message' => 'This order has already been paid.',
                'data'    => ['reference_no' => $bankRef, 'duplicate' => true]
            ];
        }

        // --- Mark the payment paid ---
        $method = 'QR Payment (SiberQR)';

        if ($payment) {
            $stmt = $conn->prepare(
                "UPDATE payments
                 SET payment_status = 'Paid', payment_method = ?,
                     bank_reference = ?, qr_id = ?
                 WHERE payment_id = ?"
            );
            $stmt->bind_param('sssi', $method, $bankRef, $qrId, $payment['payment_id']);
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO payments
                    (order_id, amount, payment_method, bank_reference, qr_id, payment_status)
                 VALUES (?, ?, ?, ?, ?, 'Paid')"
            );
            $stmt->bind_param('idsss', $orderId, $orderTotal, $method, $bankRef, $qrId);
        }
        $stmt->execute();
        $stmt->close();

        // --- Advance the order out of Pending ---
        $next = SIBER_STATUS_AFTER_PAYMENT;
        $stmt = $conn->prepare(
            "UPDATE orders SET status = ? WHERE order_id = ? AND status = 'Pending'"
        );
        $stmt->bind_param('si', $next, $orderId);
        $stmt->execute();
        $stmt->close();

        // --- Keep the shipping record in step ---
        $stmt = $conn->prepare(
            "UPDATE shipping SET shipping_status = ?
             WHERE order_id = ? AND shipping_status = 'Pending'"
        );
        $stmt->bind_param('si', $next, $orderId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $conn->close();

        logGatewayCall($orderId, 'settle', 200, 'Success', 'Settled ' . $bankRef);

        return [
            'success' => true,
            'code'    => 'PAID',
            'message' => 'Payment received. Your order is now being prepared.',
            'data'    => [
                'reference_no' => $bankRef,
                'amount'       => $orderTotal,
                'order_status' => $next,
                'duplicate'    => false
            ]
        ];

    } catch (Throwable $e) {
        $conn->rollback();
        $conn->close();

        logGatewayCall($orderId, 'settle', null, 'ServerError', $e->getMessage());

        return [
            'success' => false,
            'code'    => 'SETTLE_FAILED',
            'message' => 'The payment was received but could not be recorded. '
                       . 'Please contact support with your bank reference.',
            'data'    => ['reference_no' => $bankRef]
        ];
    }
}