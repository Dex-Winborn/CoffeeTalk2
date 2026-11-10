<?php
/** MySQL connection settings for a fresh XAMPP install. */
const DB_HOST = "localhost";
const DB_USER = "root";
const DB_PASS = "";
const DB_NAME = "coffee_talk3";
// Used when generating password-reset links. Update if Apache uses another port or host.
const APP_BASE_URL = "http://localhost:8080/coffee_talk3";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db_connect(): mysqli
{
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

function db(): mysqli
{
    try {
        return db_connect();
    } catch (mysqli_sql_exception $e) {
        http_response_code(500);
        exit("Database connection failed. Start MySQL, import database/coffee_talk.sql, and check config/database.php.");
    }
}
