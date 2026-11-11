<?php
/** MySQL connection settings for a fresh XAMPP install. */
// This file is loaded by includes/functions.php, so every page uses the same connection settings.
// Database login. These are the XAMPP defaults (user root, no password). Change them if your MySQL
// setup is different.
const DB_HOST = "localhost";
const DB_USER = "root";
const DB_PASS = "";
// Must match the database created by database/coffee_talk.sql.
const DB_NAME = "coffee_talk3";
// Leave empty to auto-detect from the current request (any port or folder name works).
const APP_BASE_URL = "";

// Makes MySQL errors throw exceptions, so the try/catch blocks in the action files can handle them
// instead of failing silently.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Opens the MySQL connection once and reuses it for the rest of the request. Throws an exception if
// MySQL is not available.
function db_connect(): mysqli
{
    // static keeps the value between calls, so only one connection is opened per request.
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        // utf8mb4 stores the peso sign, accents and emoji correctly.
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

// Same as db_connect(), but stops with a plain error message when the connection fails. Most pages
// call this one.
function db(): mysqli
{
    try {
        return db_connect();
    } catch (mysqli_sql_exception $e) {
        http_response_code(500);
        exit("Database connection failed. Start MySQL, import database/coffee_talk.sql, and check config/database.php.");
    }
}
