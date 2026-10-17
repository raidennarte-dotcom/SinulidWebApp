<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = '3307';
const DB_NAME = 'sinulid_db';
const DB_USER = 'root';
const DB_PASS = '';

const APP_DEBUG = false;

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE             => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES    => false,
            PDO::MYSQL_ATTR_INIT_COMMAND  => "SET time_zone = '+08:00'",
        ]);
    } catch (PDOException $e) {
        error_log('Sinulid DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        echo APP_DEBUG
            ? 'Database error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            : 'Could not connect to the database. Make sure MySQL is running and config/database.php is correct.';
        exit;
    }
    return $pdo;
}
