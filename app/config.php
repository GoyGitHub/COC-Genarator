<?php
declare(strict_types=1);

const APP_NAME = 'HRMO OJT Certification Generator';
const DB_DRIVER = 'sqlite'; // sqlite or mysql

// SQLite path for zero-setup local use.
const SQLITE_PATH = __DIR__ . '/../storage/hrmo_coc.sqlite';

// Optional MySQL config if you switch DB_DRIVER to mysql.
const DB_HOST = '127.0.0.1';
const DB_NAME = 'HRMO_COC';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (DB_DRIVER === 'mysql') {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }

    $storageDir = dirname(SQLITE_PATH);
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0777, true);
    }

    $pdo = new PDO('sqlite:' . SQLITE_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS interns (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            intern_level TEXT NOT NULL CHECK (intern_level IN ("college", "shs")),
            full_name TEXT NOT NULL,
            gender TEXT NOT NULL CHECK (gender IN ("male", "female")),
            school TEXT NOT NULL,
            course TEXT NULL,
            hours_rendered INTEGER NOT NULL,
            department TEXT NOT NULL,
            start_date TEXT NOT NULL,
            end_date TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )'
    );

    return $pdo;
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
