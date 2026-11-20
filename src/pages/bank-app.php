<?php
/**
 * File: bank-app.php
 * Module: Bank Siber (simulated mobile banking application)
 * Description: Stands in for the Bank Siber mobile app that a customer
 *              would open by scanning a SiberQR code. It is intentionally
 *              a separate interface with its own styling: it belongs to
 *              the bank, not to StationeryMart, and it deliberately does
 *              not use the shop's header, footer or session.
 *
 *              It reads the transaction from the bank API, shows the payer
 *              what they are about to pay, and posts their approval back to
 *              the bank. The shop learns about the payment only by polling
 *              the bank afterwards, exactly as a real merchant would.
 *
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 11/11/2026
 */

require_once __DIR__ . '/../api/bank/bank-config.php';

// This page is part of the bank, so it calls the bank API over HTTP in the
// same way the merchant does, rather than including the bank's database code.
// The address is derived from the request so the same file runs on a local
// WAMP install, behind a tunnel and on a deployed host without being edited.
$bankProto  = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
$bankScheme = $bankProto !== ''
    ? $bankProto
    : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
$bankRoot   = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/src/pages/x.php')), '/\\');
$bankHost   = $_SERVER['HTTP_HOST'] ?? 'localhost';

// The API runs on this same server, so the call is made over the loopback
// interface. It never leaves the machine, needs no DNS and avoids TLS, which
// matters behind a tunnel where the public address would route the request
// out to the internet and back in again.
define('BANK_API',          'http://127.0.0.1' . $bankRoot . '/api/bank/index.php');
define('BANK_API_PUBLIC',   $bankScheme . '://' . $bankHost . $bankRoot . '/api/bank/index.php');
define('BANK_API_HOST',     $bankHost);

/**
 * Calls the bank API and decodes the reply.
 *
 * @param string $action  Bank action to call
 * @param string $method  GET or POST
 * @param array  $payload Query string for GET, JSON body for POST
 * @return array|null Decoded reply, or null when the call failed
 */
function bankCall($action, $method, array $payload = [])
{
    // Loopback first, then the public address if the server cannot reach
    // itself that way.
    foreach ([BANK_API, BANK_API_PUBLIC] as $base) {

        $url = $base . '?action=' . urlencode($action);

        if ($method === 'GET' && $payload) {
            $url .= '&' . http_build_query($payload);
        }

        $header = "Content-Type: application/json\r\n";

        // A loopback request must name the site it is for, because one
        // address may serve several.
        if ($base === BANK_API) {
            $header .= 'Host: ' . BANK_API_HOST . "\r\n";
        }

        $opts = [
            'http' => [
                'method'        => $method,
                'header'        => $header,
                'timeout'       => 8,
                'ignore_errors' => true
            ]
        ];

        if ($method === 'POST') {
            $opts['http']['content'] = json_encode($payload);
        }

        $raw = @file_get_contents($url, false, stream_context_create($opts));

        if ($raw === false || $raw === '') {
            continue;   // try the next address
        }

        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return null;
}

$qrId    = trim((string)($_GET['qr_id'] ?? ''));
$notice  = '';
$isError = false;
$done    = false;
$txn     = null;

if ($qrId === '') {
    $notice  = 'No transaction code was supplied.';
    $isError = true;
} else {

    // ---- Handle the payer tapping Confirm or Cancel ----
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'confirm') {
            $payer  = trim((string)($_POST['payer_name'] ?? '')) ?: 'Bank Siber Customer';
            $result = bankCall('confirm', 'POST', [
                'qr_id'      => $qrId,
                'payer_name' => $payer
            ]);

            if ($result === null) {
                $notice  = 'Could not reach Bank Siber. No money has left your account.';
                $isError = true;
            } elseif (($result['status'] ?? '') === 'ok') {
                $done   = true;
                $notice = 'Payment approved. Reference '
                        . ($result['reference_no'] ?? '—');
            } else {
                $notice  = (string)($result['message'] ?? 'The transfer was declined.');
                $isError = true;
            }

        } elseif ($action === 'cancel') {
            bankCall('cancel', 'POST', ['qr_id' => $qrId]);
            $notice  = 'Payment cancelled. You can close this window.';
            $isError = true;
        }
    }

    // ---- Read the current state of the transaction ----
    $status = bankCall('status', 'GET', ['qr_id' => $qrId]);

    if ($status === null) {
        $notice  = 'Could not reach Bank Siber. Please try again.';
        $isError = true;
    } elseif (($status['status'] ?? '') !== 'ok') {
        $notice  = (string)($status['message'] ?? 'This transaction is not available.');
        $isError = true;
    } else {
        $txn = $status;
        if ($txn['payment_state'] === 'Paid') {
            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bank Siber</title>
<style>
    /* The bank app is a separate system and is styled as one.
       It shares no stylesheet with StationeryMart. */
    * { box-sizing: border-box; }
    body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #0e2a22;
        font-family: "Segoe UI", Arial, sans-serif;
        color: #10261f;
        padding: 20px;
    }
    .phone {
        width: 100%;
        max-width: 360px;
        background: #ffffff;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 18px 50px rgba(0,0,0,.45);
    }
    .bar {
        background: #0f7a4f;
        color: #ffffff;
        padding: 18px 20px;
    }
    .bar h1 { margin: 0; font-size: 17px; letter-spacing: .3px; }
    .bar p  { margin: 2px 0 0; font-size: 12px; opacity: .85; }
    .body { padding: 22px 20px 26px; }

    .label {
        font-size: 11px;
        letter-spacing: .09em;
        text-transform: uppercase;
        color: #6b7b76;
        margin-bottom: 3px;
    }
    .value { font-size: 15px; margin-bottom: 16px; word-break: break-word; }

    .amount {
        font-size: 34px;
        font-weight: 700;
        color: #0f7a4f;
        text-align: center;
        margin: 6px 0 22px;
    }

    .note {
        padding: 11px 13px;
        border-radius: 7px;
        font-size: 13px;
        margin-bottom: 16px;
        line-height: 1.45;
    }
    .note.ok   { background: #e2f5ea; color: #15603f; border-left: 3px solid #0f7a4f; }
    .note.bad  { background: #fdeaea; color: #8f2020; border-left: 3px solid #b53030; }

    .field {
        width: 100%;
        padding: 10px 12px;
        font-size: 14px;
        font-family: inherit;
        border: 1px solid #c7d2ce;
        border-radius: 7px;
        margin-bottom: 18px;
    }
    .btn {
        display: block;
        width: 100%;
        padding: 13px;
        font-size: 15px;
        font-family: inherit;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        margin-bottom: 10px;
        text-align: center;
        text-decoration: none;
    }
    .btn.pay    { background: #0f7a4f; color: #ffffff; }
    .btn.pay:hover { background: #0c6440; }
    .btn.quiet  { background: #eef1f0; color: #42524d; }
    .tick {
        font-size: 46px;
        text-align: center;
        color: #0f7a4f;
        margin: 6px 0 2px;
    }
    .foot {
        text-align: center;
        font-size: 11px;
        color: #8a9793;
        margin-top: 14px;
        line-height: 1.5;
    }
</style>
</head>
<body>

<div class="phone">
    <div class="bar">
        <h1>Bank Siber</h1>
        <p>SiberQR Payment</p>
    </div>

    <div class="body">

        <?php if ($notice !== ''): ?>
            <div class="note <?= $isError ? 'bad' : 'ok' ?>">
                <?= htmlspecialchars($notice) ?>
            </div>
        <?php endif; ?>

        <?php if ($done): ?>

            <div class="tick">&#10003;</div>
            <p style="text-align:center;font-size:15px;margin:0 0 4px;">Transfer complete</p>
            <?php if ($txn): ?>
                <p style="text-align:center;font-size:13px;color:#6b7b76;margin:0 0 18px;">
                    RM <?= number_format((float)$txn['amount'], 2) ?> paid to StationeryMart
                </p>
                <div class="label">Reference</div>
                <div class="value"><?= htmlspecialchars((string)$txn['reference_no']) ?></div>
            <?php endif; ?>

            <p class="foot">
                You may close this window. The merchant has been notified
                automatically.
            </p>

        <?php elseif ($txn && $txn['payment_state'] === 'Pending'): ?>

            <div class="label">Pay to</div>
            <div class="value">StationeryMart Sdn Bhd</div>

            <div class="label">Reference</div>
            <div class="value"><?= htmlspecialchars((string)$txn['order_ref']) ?></div>

            <div class="label">Amount due</div>
            <div class="amount">RM <?= number_format((float)$txn['amount'], 2) ?></div>

            <form method="post"
                  action="bank-app.php?qr_id=<?= urlencode($qrId) ?>">
                <div class="label">Account holder</div>
                <input class="field" type="text" name="payer_name"
                       value="Bank Siber Customer" maxlength="100" required>

                <input type="hidden" name="action" value="confirm">
                <button class="btn pay" type="submit">Confirm Payment</button>
            </form>

            <form method="post"
                  action="bank-app.php?qr_id=<?= urlencode($qrId) ?>">
                <input type="hidden" name="action" value="cancel">
                <button class="btn quiet" type="submit">Cancel</button>
            </form>

            <p class="foot">
                Simulated banking interface for CSEB5223 Lab 7 &amp; 8.<br>
                No real account is debited.
            </p>

        <?php else: ?>

            <p class="foot" style="margin-top:4px;">
                Simulated banking interface for CSEB5223 Lab 7 &amp; 8.<br>
                No real account is debited.
            </p>

        <?php endif; ?>

    </div>
</div>

</body>
</html>