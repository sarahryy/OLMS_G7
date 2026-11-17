
<?php
/**
 * File: run-all.php
 * Description: Runs all StationeryMart test files.
 */

require_once __DIR__ . '/TestRunner.php';

echo "STATIONERYMART TEST SUITE" . PHP_EOL;
echo "=========================" . PHP_EOL;

$testFiles = [
    __DIR__ . '/UserTest.php',
    // Other members can register their test files here.
    // Example: __DIR__ . '/OrderTest.php',
    // Example: __DIR__ . '/ProductTest.php',
];

foreach ($testFiles as $file) {
    if (file_exists($file)) {
        echo PHP_EOL . "Running: " . basename($file) . PHP_EOL;
        require_once $file;
    } else {
        echo "[WARNING] Test file not found: " . $file . PHP_EOL;
    }
}

TestRunner::summary();