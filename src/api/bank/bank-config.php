<?php
/**
 * File: bank-config.php
 * Module: Bank Siber (simulated external payment provider)
 * Description: Configuration for the simulated SiberQR payment service.
 *              In a real deployment this file would live on the bank's
 *              own servers and the merchant would hold only the merchant
 *              identifier and the shared secret. It is kept here because
 *              the entire bank is simulated for this laboratory exercise.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 11/11/2026
 */
 
// Identifier the bank has issued to this merchant.
define('SIBER_MERCHANT_ID', 'MERCH-STATIONERYMART-001');
 
// Shared secret used to sign and verify requests between the merchant
// and the bank. A real integration would never commit this to version
// control; it is here only because the bank is simulated.
define('SIBER_SHARED_SECRET', 'siberqr_demo_secret_2026_group7');
 
// How long a generated QR code remains valid, in seconds.
define('SIBER_QR_LIFETIME', 300);
 
// Maximum number of requests one merchant may make per minute before
// the bank responds with HTTP 429. Demonstrates rate-limit handling.
define('SIBER_RATE_LIMIT_PER_MINUTE', 60);
 
/**
 * Produces the signature for a payment request.
 *
 * Both the merchant and the bank compute this independently from the
 * same fields and the shared secret. A request whose signature does not
 * match is rejected, which prevents a tampered amount being accepted.
 *
 * @param string $merchantId Merchant identifier issued by the bank
 * @param string $orderRef   Merchant's own reference for the order
 * @param float  $amount     Amount to be collected, in RM
 * @return string Hexadecimal HMAC-SHA256 signature
 */
function siberSign($merchantId, $orderRef, $amount)
{
    $payload = $merchantId . '|' . $orderRef . '|' . number_format((float)$amount, 2, '.', '');
    return hash_hmac('sha256', $payload, SIBER_SHARED_SECRET);
}
 
