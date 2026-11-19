<?php
/**
 * File: bank-app.php
 * Module: Bank Siber (simulated mobile banking application)
 * Description: Stands in for the Bank Siber mobile app that a customer
 *              would open by scanning a SiberQR code. Hardened with strict
 *              qr_id format validation on GET and POST, action whitelisting,
 *              payer input sanitization, explicit HTTP status codes, and
 *              UTF-8 HTML output escaping.
 *
 * Author: Siti Sarah Raihanah Binti Azizan
 * Audited & Hardened by: Sharifah Sofiyyah Lu'lu' Binti Syed Firdaus Hilmi
 */

require_once __DIR__ . '/../api/bank/bank-config.php';

// Derive API endpoints dynamically so the page runs across local WAMP,
// LAN addresses, and tunnels without hardcoded hostnames.
$bankProto  = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
$bankScheme = $bankProto !== ''
    ? $bankProto
    : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
$bankRoot   = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/src/pages/x.php')), '/\\');
$bankHost   = $_SERVER['HTTP_HOST'] ?? 'localhost';

define('BANK_API',        'http://127.0.0.1' . $bankRoot . '/api/bank/index.php');
define('BANK_API_PUBLIC', $bankScheme . '://' . $bankHost . $bankRoot . '/api/bank/index.php');
define('BANK_API_HOST',   $bankHost);

/**
 * Calls the bank API and decodes the JSON reply.
 *
 * @param string $action  Bank action to call
 * @param string $method  GET or POST
 * @param array  $payload Query string for GET, JSON body for POST
 * @return array|null Decoded reply, or null when the transport call failed
 */
function bankCall($action, $method, array $payload = [])
{
    foreach ([BANK_API, BANK_API_PUBLIC] as $base) {
        $url = $base . '?action=' . urlencode($action);

        if ($method === 'GET' && $payload) {
            $url .= '&' . http_build_query($payload);
        }

        $header = "Content-Type: application/json\r\n";

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
            continue;
        }

        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return null;
}

// 1. Input Sanitization & Format Assertion for qr_id (GET and POST)
$rawGetQr  = str_replace("\0", '', (string)($_GET['qr_id'] ?? ''));
$rawPostQr = str_replace("\0", '', (string)($_POST['qr_id'] ?? ''));
$qrId      = trim($rawPostQr !== '' ? $rawPostQr : $rawGetQr);

$payerInput = 'Bank Siber Customer';
$notice     = '';
$isError    = false;
$done       = false;
$txn        = null;

// Guard Clause: Reject missing or malformed qr_id before making any API request
if ($qrId === '' || !preg_match('/^[A-Za-z0-9_-]{6,32}$/', $qrId)) {
    http_response_code(400); // 400 Bad Request
    $notice  = 'Invalid or missing transaction code (qr_id).';
    $isError = true;
} else {
    // 2. Handle POST Payment Approval or Cancellation Defensively
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = trim(str_replace("\0", '', (string)($_POST['action'] ?? '')));

        // Guard Clause: Whitelist allowed POST actions
        if (!in_array($action, ['confirm', 'cancel'], true)) {
            http_response_code(400); // 400 Bad Request
            $notice  = 'Invalid payment action requested.';
            $isError = true;
        } elseif ($action === 'confirm') {
            // Sanitize payer name (strip null bytes, trim, clamp to VARCHAR(100))
            $rawPayer   = trim(str_replace("\0", '', (string)($_POST['payer_name'] ?? '')));
            $payer      = $rawPayer !== '' ? mb_substr($rawPayer, 0, 100, 'UTF-8') : 'Bank Siber Customer';
            $payerInput = $payer;

            $result = bankCall('confirm', 'POST', [
                'qr_id'      => $qrId,
                'payer_name' => $payer
            ]);

            if ($result === null) {
                http_response_code(502); // 502 Bad Gateway
                $notice  = 'Could not reach Bank Siber. No money has left your account.';
                $isError = true;
            } elseif (($result['status'] ?? '') === 'ok') {
                $done   = true;
                $notice = 'Payment approved. Reference ' . (string)($result['reference_no'] ?? '—');
            } else {
                http_response_code(409); // 409 Conflict / Declined
                $notice  = (string)($result['message'] ?? 'The transfer was declined.');
                $isError = true;
            }
        } elseif ($action === 'cancel') {
            $cancelResult = bankCall('cancel', 'POST', ['qr_id' => $qrId]);
            if ($cancelResult === null) {
                http_response_code(502);
                $notice  = 'Could not reach Bank Siber to cancel the transaction.';
                $isError = true;
            } else {
                $notice  = 'Payment cancelled. You can close this window.';
                $isError = true;
            }
        }
    }

    // 3. Read Current Transaction State from Bank API
    $status = bankCall('status', 'GET', ['qr_id' => $qrId]);

    if ($status === null) {
        http_response_code(502);
        $notice  = 'Could not reach Bank Siber. Please try again.';
        $isError = true;
    } elseif (($status['status'] ?? '') !== 'ok') {
        http_response_code(404);
        $notice  = (string)($status['message'] ?? 'This transaction is not available.');
        $isError = true;
    } else {
        $txn = $status;
        if (($txn['payment_state'] ?? '') === 'Paid') {
            $done = true;
        }
    }
}

// Prepare strictly escaped output values
$escNotice     = htmlspecialchars($notice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escMerchantId = htmlspecialchars((string)($txn['merchant_id'] ?? 'MERCH-STATIONERYMART-001'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escOrderRef   = htmlspecialchars((string)($txn['order_ref'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escRefNo      = htmlspecialchars((string)($txn['reference_no'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escAmount     = htmlspecialchars(number_format((float)($txn['amount'] ?? 0), 2), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escPayerInput = htmlspecialchars($payerInput, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$escQrIdAttr   = htmlspecialchars($qrId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
                <?= $escNotice ?>
            </div>
        <?php endif; ?>

        <?php if ($done): ?>

            <div class="tick">&#10003;</div>
            <p style="text-align:center;font-size:15px;margin:0 0 4px;">Transfer complete</p>
            <?php if ($txn): ?>
                <p style="text-align:center;font-size:13px;color:#6b7b76;margin:0 0 18px;">
                    RM <?= $escAmount ?> paid to StationeryMart
                </p>
                <div class="label">Merchant ID</div>
                <div class="value"><?= $escMerchantId ?></div>
                <div class="label">Reference</div>
                <div class="value"><?= $escRefNo ?></div>
            <?php endif; ?>

            <p class="foot">
                You may close this window. The merchant has been notified
                automatically.
            </p>

        <?php elseif ($txn && ($txn['payment_state'] ?? '') === 'Pending'): ?>

            <div class="label">Pay to</div>
            <div class="value">StationeryMart Sdn Bhd (<?= $escMerchantId ?>)</div>

            <div class="label">Reference</div>
            <div class="value"><?= $escOrderRef ?></div>

            <div class="label">Amount due</div>
            <div class="amount">RM <?= $escAmount ?></div>

            <form method="post"
                  action="bank-app.php?qr_id=<?= urlencode($qrId) ?>">
                <input type="hidden" name="qr_id" value="<?= $escQrIdAttr ?>">
                <div class="label">Account holder</div>
                <input class="field" type="text" name="payer_name"
                       value="<?= $escPayerInput ?>" maxlength="100" required>

                <input type="hidden" name="action" value="confirm">
                <button class="btn pay" type="submit">Confirm Payment</button>
            </form>

            <form method="post"
                  action="bank-app.php?qr_id=<?= urlencode($qrId) ?>">
                <input type="hidden" name="qr_id" value="<?= $escQrIdAttr ?>">
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