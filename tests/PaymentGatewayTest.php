<?php
/**
 * File: tests/PaymentGatewayTest.php
 * Module: Payment Gateway and API Testing
 * Description: Unit tests for HMAC-SHA256 request signing (siberSign) and
 *              network failure resilience (GATEWAY_UNREACHABLE on port 9999).
 * Author: Sharifah Sofiyyah Lu'lu' Binti Syed Firdaus Hilmi
 */

// Redirect gateway endpoints to an unused port before loading middleware
if (!defined('SIBER_API_LOOPBACK')) {
    define('SIBER_API_LOOPBACK', 'http://127.0.0.1:9999/api/bank/index.php');
}
if (!defined('SIBER_API_PUBLIC')) {
    define('SIBER_API_PUBLIC', 'http://127.0.0.1:9999/api/bank/index.php');
}

// Load shared TestRunner if present on the branch; otherwise use a compatible fallback
if (file_exists(__DIR__ . '/TestRunner.php')) {
    require_once __DIR__ . '/TestRunner.php';
} elseif (!class_exists('TestRunner')) {
    class TestRunner
    {
        private static int $passed = 0;
        private static int $failed = 0;

        public static function assertEquals($expected, $actual, string $message = ''): void
        {
            if ($expected === $actual) {
                self::$passed++;
                echo "[PASS] " . $message . PHP_EOL;
            } else {
                self::$failed++;
                echo "[FAIL] " . $message
                    . " | Expected: " . var_export($expected, true)
                    . " | Actual: " . var_export($actual, true)
                    . PHP_EOL;
            }
        }

        public static function assertTrue($actual, string $message = ''): void
        {
            self::assertEquals(true, $actual, $message);
        }

        public static function summary(): void
        {
            $total = self::$passed + self::$failed;
            echo PHP_EOL . "===== TEST SUMMARY =====" . PHP_EOL;
            echo "Passed: " . self::$passed . PHP_EOL;
            echo "Failed: " . self::$failed . PHP_EOL;
            echo "Total: " . $total . PHP_EOL;
        }
    }
}

require_once __DIR__ . '/../src/services/payment-gateway.php';

echo PHP_EOL . "--- PAYMENT GATEWAY AND API TESTS ---" . PHP_EOL;

// 1. Test siberSign() directly: canonical amount formatting & determinism
$sigFloat  = siberSign('MERCH-STATIONERYMART-001', 'OLMS-101', 15.9);
$sigString = siberSign('MERCH-STATIONERYMART-001', 'OLMS-101', '15.90');

TestRunner::assertEquals(
    64,
    strlen($sigFloat),
    'siberSign() returns a 64-character SHA-256 hex digest'
);

TestRunner::assertEquals(
    $sigFloat,
    $sigString,
    'siberSign() normalises numeric amounts (15.9 === "15.90") to identical signatures'
);

// 2. Test siberSign() tamper detection (1-sen difference & order reference change)
$sigTamperedAmount = siberSign('MERCH-STATIONERYMART-001', 'OLMS-101', 15.89);
$sigTamperedOrder  = siberSign('MERCH-STATIONERYMART-001', 'OLMS-102', 15.90);

TestRunner::assertTrue(
    !hash_equals($sigFloat, $sigTamperedAmount),
    'siberSign() detects 1-sen amount tampering (15.90 vs 15.89)'
);

TestRunner::assertTrue(
    !hash_equals($sigFloat, $sigTamperedOrder),
    'siberSign() detects order reference tampering (OLMS-101 vs OLMS-102)'
);

// 3. Test network resilience against unreachable endpoint (http://127.0.0.1:9999)
$callResult = callBankApi('status', 'GET', ['qr_id' => 'TESTQR123456'], null);

TestRunner::assertEquals(
    false,
    $callResult['success'] ?? null,
    'callBankApi() returns success=false when endpoint 127.0.0.1:9999 is unreachable'
);

TestRunner::assertEquals(
    'GATEWAY_UNREACHABLE',
    $callResult['code'] ?? '',
    'callBankApi() classifies unreachable port 9999 as GATEWAY_UNREACHABLE'
);

$statusResult = getQrPaymentStatus('TESTQR123456', 999);

TestRunner::assertEquals(
    'GATEWAY_UNREACHABLE',
    $statusResult['code'] ?? '',
    'getQrPaymentStatus() propagates GATEWAY_UNREACHABLE safely without throwing'
);

// Print summary when executed standalone (skipped if included by run-all.php)
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    TestRunner::summary();
}