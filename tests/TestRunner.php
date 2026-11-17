
<?php
/**
 * File: TestRunner.php
 * Description: Simple PHP testing framework for StationeryMart.
 * No Composer or external dependencies required.
 */

class TestRunner
{
    private static $passed = 0;
    private static $failed = 0;

    public static function assertEquals($expected, $actual, $message = '')
    {
        if ($expected === $actual) {
            self::$passed++;
            echo "[PASS] " . $message . PHP_EOL;
        } else {
            self::$failed++;
            echo "[FAIL] " . $message .
                 " | Expected: " . var_export($expected, true) .
                 " | Actual: " . var_export($actual, true) . PHP_EOL;
        }
    }

    public static function assertTrue($actual, $message = '')
    {
        self::assertEquals(true, $actual, $message);
    }

    public static function assertNull($actual, $message = '')
    {
        self::assertEquals(null, $actual, $message);
    }

    public static function assertThrows($callback, $message = '')
    {
        try {
            $callback();
            self::$failed++;
            echo "[FAIL] " . $message . " | No exception thrown" . PHP_EOL;
        } catch (Throwable $e) {
            self::$passed++;
            echo "[PASS] " . $message . PHP_EOL;
        }
    }

    public static function summary()
    {
        echo PHP_EOL . "===== TEST SUMMARY =====" . PHP_EOL;
        echo "Passed: " . self::$passed . PHP_EOL;
        echo "Failed: " . self::$failed . PHP_EOL;
        echo "Total: " . (self::$passed + self::$failed) . PHP_EOL;
    }
}