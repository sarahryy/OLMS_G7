<?php
/**
 * File: TestRunner.php
 * Module: Testing
 * Description: Minimal unit test harness for the OLMS project. Provides four
 *              assertion functions and a pass/fail summary. Used instead of
 *              PHPUnit so the suite runs on any WAMP install with no
 *              Composer dependency.
 *
 * Author: Group 7
 * Created: 21/11/2026
 */

$GLOBALS['testsRun']    = 0;
$GLOBALS['testsPassed'] = 0;
$GLOBALS['testsFailed'] = 0;

// Readable output whether the suite is run from the browser or the CLI.
if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

/**
 * Records one result and prints it.
 *
 * @param string $name   What was being tested
 * @param bool   $ok     Whether the assertion held
 * @param string $detail Explanation shown only on failure
 * @return void
 */
function report($name, $ok, $detail = '')
{
    $GLOBALS['testsRun']++;

    if ($ok) {
        $GLOBALS['testsPassed']++;
        echo 'PASS  ' . $name . PHP_EOL;
    } else {
        $GLOBALS['testsFailed']++;
        echo 'FAIL  ' . $name . ($detail !== '' ? '  -- ' . $detail : '') . PHP_EOL;
    }
}

/**
 * Asserts that two values are equal.
 *
 * @param mixed  $expected Value the code should produce
 * @param mixed  $actual   Value it did produce
 * @param string $name     Test name
 * @return void
 */
function assertEquals($expected, $actual, $name)
{
    report($name, $expected == $actual,
        'expected ' . var_export($expected, true)
        . ', got ' . var_export($actual, true));
}

/**
 * Asserts that a condition is true.
 *
 * @param bool   $condition Condition under test
 * @param string $name      Test name
 * @return void
 */
function assertTrue($condition, $name)
{
    report($name, $condition === true, 'expected true, got '
        . var_export($condition, true));
}

/**
 * Asserts that a value is null.
 *
 * @param mixed  $value Value under test
 * @param string $name  Test name
 * @return void
 */
function assertNull($value, $name)
{
    report($name, $value === null,
        'expected null, got ' . var_export($value, true));
}

/**
 * Asserts that the callback throws.
 *
 * @param callable $callback Code expected to throw
 * @param string   $name     Test name
 * @return void
 */
function assertThrows($callback, $name)
{
    try {
        $callback();
        report($name, false, 'no exception was thrown');
    } catch (Throwable $e) {
        report($name, true);
    }
}

/**
 * Prints the totals. Call once at the end of a test file.
 *
 * @return void
 */
function testSummary()
{
    echo PHP_EOL . str_repeat('-', 50) . PHP_EOL;
    echo 'Total: '  . $GLOBALS['testsRun']
       . '   Passed: ' . $GLOBALS['testsPassed']
       . '   Failed: ' . $GLOBALS['testsFailed'] . PHP_EOL;
}
