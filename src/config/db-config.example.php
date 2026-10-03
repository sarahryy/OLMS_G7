<?php
/**
 * File: db-config.example.php
 * Module: Configuration
 * Description: Template for local database credentials. Copy this file
 *              to db-config.php and fill in your own MySQL settings.
 *              db-config.php is gitignored and must never be committed.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 03/10/2026
 */

// ---- Database connection settings (edit for your local setup) ----
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '');            // WAMP default is empty
define('DB_NAME', 'olms_g7');

/**
 * Creates and returns a mysqli connection to the OLMS database.
 *
 * @return mysqli Active database connection
 * @throws RuntimeException If the connection cannot be established
 */
function getConnection()
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $conn->set_charset('utf8mb4');
        return $conn;
    } catch (mysqli_sql_exception $e) {
        throw new RuntimeException(
            'Database connection failed: ' . $e->getMessage()
        );
    }
}