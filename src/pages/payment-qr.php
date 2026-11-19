<?php
/**
 * File: payment-qr.php
 * Module: Payment Gateway (Lab 7, 8 & 9)
 * Description: Displays the SiberQR code a customer scans to pay for an
 *              order, and waits for the bank to report the payment. Hardened
 *              with strict integer validation (FILTER_VALIDATE_INT), explicit
 *              order ownership guards, HTTP status codes, and XSS escaping.
 *
 * Author: Siti Sarah Raihanah Binti Azizan
 * Audited & Hardened by: Sharifah Sofiyyah Lu'lu' Binti Syed Firdaus Hilmi
 * Created: 11/11/2026
 */

require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/payment-gateway.php';
require_once __DIR__ . '/../config/db-config.php';

requireCustomer();

// 1. Validate session user identifier defensively
$userId = filter_var($_SESSION['userId'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if ($userId === false || $userId === null) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}

// 2. Strict positive-integer validation on $_GET['order_id']
// Rejects non-numeric strings (?order_id=abc), zeroes (?order_id=0), and negatives (?order_id=-1)
$rawOrderId = $_GET['order_id'] ?? null;
$orderId = filter_var($rawOrderId, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 2147483647]
]);

$errorMessage = '';
$qr           = null;

if ($orderId === false || $orderId === null) {
    http_response_code(400); // 400 Bad Request
    $orderId = 0;
    $errorMessage = 'Invalid order ID. Please provide a valid positive order number.';
} else {
    $conn = getConnection();
    $stmt = $conn->prepare(
        "SELECT o.order_id, o.user_id, o.total_amount, o.status,
                (SELECT p.payment_status FROM payments p
                 WHERE p.order_id = o.order_id
                 ORDER BY p.payment_id DESC LIMIT 1) AS payment_status
         FROM orders o
         WHERE o.order_id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    // 3. Guard Clause: Order existence check
    if (!$order) {
        http_response_code(404); // 404 Not Found
        $errorMessage = 'That order could not be found.';
    }
    // 4. Guard Clause: Explicit Ownership Check (Prevents IDOR across customer accounts)
    elseif ((int)$order['user_id'] !== (int)$userId) {
        http_response_code(403); // 403 Forbidden
        $errorMessage = 'Access denied. This order does not belong to your account.';
    }
    // 5. Guard Clause: Order & Payment State Assertions
    elseif (($order['payment_status'] ?? '') === 'Paid') {
        $_SESSION['orderSuccess'] = 'This order has already been paid.';
        header('Location: order-history.php');
        exit;
    } elseif (($order['status'] ?? '') === 'Cancelled') {
        http_response_code(409); // 409 Conflict
        $errorMessage = 'This order has been cancelled and cannot be paid.';
    } else {
        // ---- Ask the middleware for a QR code ----
        $result = createQrPayment($orderId);

        if (!empty($result['success']) && is_array($result['data'] ?? null)) {
            $qr = $result['data'];
        } else {
            http_response_code(502); // 502 Bad Gateway (upstream payment service error)
            $errorMessage = (string)($result['message'] ?? 'Unable to generate payment QR code.');
        }
    }
}

// Build the simulated Bank Siber URL encoded inside the QR code
$bankAppUrl = '';
if ($qr && !empty($qr['qr_id'])) {
    $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    $scheme = $forwarded !== ''
        ? $forwarded
        : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $bankAppUrl = $scheme . '://' . $host . $dir . '/bank-app.php?qr_id='
                . urlencode((string)$qr['qr_id']);
}

$pageTitle = 'Scan to Pay';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Scan to Pay</h1>
<p class="page-subtitle">Pay with SiberQR through your Bank Siber app</p>

<?php if ($errorMessage !== ''): ?>

    <div class="message-error"><?= htmlspecialchars($errorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>

    <div class="form-actions">
        <a class="btn btn-secondary" href="order-history.php">Back to My Orders</a>
        <?php if ($orderId > 0 && http_response_code() !== 403 && http_response_code() !== 404): ?>
            <a class="btn btn-primary" href="payment-qr.php?order_id=<?= (int)$orderId ?>">
                Try Again
            </a>
        <?php endif; ?>
    </div>

<?php else: ?>

    <div class="card" style="max-width:420px;margin:0 auto;text-align:center;">

        <h2 class="card-title">Bank Siber &middot; SiberQR</h2>

        <p class="text-muted" style="margin-top:-6px;">
            Order #<?= (int)$orderId ?>
        </p>

        <div class="summary-total" style="text-align:center;border-top:none;">
            RM <?= number_format((float)($qr['amount'] ?? 0), 2) ?>
        </div>

        <div id="qrcode" style="display:flex;justify-content:center;margin:18px 0;"></div>

        <p class="form-hint" id="qr-fallback" hidden>
            Scanner unavailable. Reference:
            <strong><?= htmlspecialchars((string)($qr['qr_id'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
        </p>

        <p class="form-hint">
            Expires at <?= htmlspecialchars(date('g:i a', strtotime((string)($qr['expires_at'] ?? 'now'))), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </p>

        <div id="waiting" class="message-info" style="margin-top:16px;">
            Waiting for payment&hellip;
        </div>

        <div id="paid" class="message-success" style="margin-top:16px;" hidden>
            Payment received. Redirecting&hellip;
        </div>

        <div id="failed" class="message-error" style="margin-top:16px;" hidden></div>

        <div class="form-actions" style="justify-content:center;">
            <a class="btn btn-primary" href="<?= htmlspecialchars($bankAppUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
               target="_blank" rel="noopener">Open Bank Siber App</a>
            <a class="link-back" href="order-history.php">Pay later</a>
        </div>

        <p class="form-hint" style="margin-top:10px;">
            On a phone, scan the code above. On this computer, use the button
            to open the simulated bank app.
        </p>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
    (function () {
        var orderId   = <?= (int)$orderId ?>;
        var qrId      = <?= json_encode((string)($qr['qr_id'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var bankUrl   = <?= json_encode($bankAppUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        // ---- Draw the QR code, falling back to the reference text ----
        try {
            if (typeof QRCode === 'undefined') { throw new Error('library blocked'); }
            new QRCode(document.getElementById('qrcode'), {
                text: bankUrl,
                width: 220,
                height: 220,
                correctLevel: QRCode.CorrectLevel.M
            });
        } catch (e) {
            document.getElementById('qr-fallback').hidden = false;
        }

        // ---- Poll the shop for the payment state ----
        var waiting = document.getElementById('waiting');
        var paid    = document.getElementById('paid');
        var failed  = document.getElementById('failed');

        var attempts    = 0;
        var maxAttempts = 100;   // about five minutes at three seconds
        var misses      = 0;     // consecutive network failures

        function stop(el, text) {
            waiting.hidden = true;
            el.hidden = false;
            if (text) { el.textContent = text; }
        }

        function poll() {
            if (attempts++ >= maxAttempts) {
                stop(failed, 'This page stopped checking. Reload to continue waiting.');
                return;
            }

            fetch('payment-status.php?order_id=' + orderId
                  + '&qr_id=' + encodeURIComponent(qrId), { cache: 'no-store' })
                .then(function (r) {
                    if (!r.ok) { throw new Error('HTTP ' + r.status); }
                    return r.json();
                })
                .then(function (data) {
                    misses = 0;

                    if (data.state === 'Paid') {
                        stop(paid);
                        setTimeout(function () {
                            window.location = 'order-history.php';
                        }, 1200);
                        return;
                    }

                    if (data.state === 'Expired' || data.state === 'Cancelled') {
                        stop(failed, data.message || 'This QR code is no longer valid.');
                        return;
                    }

                    if (data.state === 'Error') {
                        stop(failed, data.message || 'The payment service reported a problem.');
                        return;
                    }

                    setTimeout(poll, 3000);
                })
                .catch(function () {
                    // Tolerate a few dropped requests before giving up, so a
                    // momentary hiccup does not abandon a pending payment.
                    if (++misses >= 5) {
                        stop(failed, 'Lost contact with the server. Reload to check again.');
                        return;
                    }
                    setTimeout(poll, 3000);
                });
        }

        setTimeout(poll, 3000);
    })();
    </script>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>