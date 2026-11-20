<?php
/**
 * File: payment-qr.php
 * Module: Payment Gateway (Lab 7 & 8)
 * Description: Displays the SiberQR code a customer scans to pay for an
 *              order, and waits for the bank to report the payment. The
 *              page never contacts the bank itself: it asks the payment
 *              gateway middleware, which owns every call to the bank.
 *
 *              While the page is open it polls payment-status.php every
 *              three seconds. As soon as the bank reports the transfer,
 *              the middleware credits the order and the page redirects
 *              the customer to their order history.
 *
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 11/11/2026
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/payment-gateway.php';
require_once __DIR__ . '/../config/db-config.php';
 
requireCustomer();
 
$orderId = (int)($_GET['order_id'] ?? 0);
$userId  = (int)$_SESSION['userId'];
 
$errorMessage = '';
$qr           = null;
 
// ---- The order must exist and belong to the signed-in customer ----
// Without this check, changing the order_id in the address bar would
// display another customer's order total.
if ($orderId <= 0) {
    $errorMessage = 'No order was specified.';
} else {
    $conn = getConnection();
    $stmt = $conn->prepare(
        "SELECT o.order_id, o.total_amount, o.status,
                (SELECT p.payment_status FROM payments p
                 WHERE p.order_id = o.order_id
                 ORDER BY p.payment_id DESC LIMIT 1) AS payment_status
         FROM orders o
         WHERE o.order_id = ? AND o.user_id = ? LIMIT 1"
    );
    $stmt->bind_param('ii', $orderId, $userId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
 
    if (!$order) {
        $errorMessage = 'That order could not be found in your account.';
    } elseif ($order['payment_status'] === 'Paid') {
        // Nothing to pay. Send the customer on rather than issuing a QR.
        $_SESSION['orderSuccess'] = 'This order has already been paid.';
        header('Location: order-history.php');
        exit;
    } elseif ($order['status'] === 'Cancelled') {
        $errorMessage = 'This order has been cancelled and cannot be paid.';
    } else {
        // ---- Ask the middleware for a QR code ----
        $result = createQrPayment($orderId);
 
        if ($result['success']) {
            $qr = $result['data'];
        } else {
            $errorMessage = $result['message'];
        }
    }
}
 
// The URL a phone's camera opens when it scans the code. Built from the
// current host so the demonstration also works from another device on
// the same network, not only from this machine.
$bankAppUrl = '';
if ($qr) {
    // Behind a tunnel or a host's reverse proxy, Apache sees plain HTTP while
    // the browser is on HTTPS. Trust the forwarded protocol header when it is
    // present, so the QR encodes a link the phone will actually open.
    $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    $scheme = $forwarded !== ''
        ? $forwarded
        : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $bankAppUrl = $scheme . '://' . $host . $dir . '/bank-app.php?qr_id='
                . urlencode($qr['qr_id']);
}
 
$pageTitle = 'Scan to Pay';
include __DIR__ . '/../includes/header.php';
?>
 
<h1 class="page-title">Scan to Pay</h1>
<p class="page-subtitle">Pay with SiberQR through your Bank Siber app</p>
 
<?php if ($errorMessage !== ''): ?>
 
    <div class="message-error"><?= htmlspecialchars($errorMessage) ?></div>
 
    <div class="form-actions">
        <a class="btn btn-secondary" href="order-history.php">Back to My Orders</a>
        <?php if ($orderId > 0): ?>
            <a class="btn btn-primary" href="payment-qr.php?order_id=<?= $orderId ?>">
                Try Again
            </a>
        <?php endif; ?>
    </div>
 
<?php else: ?>
 
    <div class="card" style="max-width:420px;margin:0 auto;text-align:center;">
 
        <h2 class="card-title">Bank Siber &middot; SiberQR</h2>
 
        <p class="text-muted" style="margin-top:-6px;">
            Order #<?= $orderId ?>
        </p>
 
        <div class="summary-total" style="text-align:center;border-top:none;">
            RM <?= number_format((float)$qr['amount'], 2) ?>
        </div>
 
        <div id="qrcode" style="display:flex;justify-content:center;margin:18px 0;"></div>
 
        <p class="form-hint" id="qr-fallback" hidden>
            Scanner unavailable. Reference:
            <strong><?= htmlspecialchars($qr['qr_id']) ?></strong>
        </p>
 
        <p class="form-hint">
            Expires at <?= htmlspecialchars(date('g:i a', strtotime($qr['expires_at']))) ?>
        </p>
 
        <div id="waiting" class="message-info" style="margin-top:16px;">
            Waiting for payment&hellip;
        </div>
 
        <div id="paid" class="message-success" style="margin-top:16px;" hidden>
            Payment received. Redirecting&hellip;
        </div>
 
        <div id="failed" class="message-error" style="margin-top:16px;" hidden></div>
 
        <div class="form-actions" style="justify-content:center;">
            <a class="btn btn-primary" href="<?= htmlspecialchars($bankAppUrl) ?>"
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
        var qrId      = <?= json_encode($qr['qr_id']) ?>;
        var bankUrl   = <?= json_encode($bankAppUrl) ?>;
 
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
 