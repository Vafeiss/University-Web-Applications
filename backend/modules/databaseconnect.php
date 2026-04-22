<?php
/*NAME: Database Connection
Description: This file is responsible for connecting to the advicut database using PDO for error handling
Paraskevas Vafeiadis
26-feb-2026 v0.1
Inputs: None
Outputs: None
Error Messages : if connection fails throw exception with message
Files in use: UsersClass.php where the connection is used to query the database for log in
*/

declare(strict_types=1);

require_once __DIR__ . '/Env.php';

function ConnectToDatabase(): PDO {
    static $conn = null;

    if ($conn === null) {
        Env::loadFromProjectRoot();

        $host = (string)(getenv('DB_HOST') ?: '127.0.0.1');
        $port = trim((string)(getenv('DB_PORT') ?: ''));
        $db   = (string)(getenv('DB_NAME') ?: 'advicut');
        $user = (string)(getenv('DB_USER') ?: 'root');
        $pass = (string)(getenv('DB_PASS') ?: '');
        $charset = (string)(getenv('DB_CHARSET') ?: 'utf8mb4');

        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
        if ($port !== '' && ctype_digit($port)) {
            $dsn .= ";port={$port}";
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            error_log('[DB] Connecting to db=' . $db . ' host=' . $host . ' port=' . ($port === '' ? '(default)' : $port));
            $conn = new PDO($dsn, $user, $pass, $options);
            error_log('[DB] Connection successful');
        } catch (PDOException $e) {
            error_log('[DB] Connection failed: ' . $e->getMessage());
            throw $e;
        }
    }

    return $conn;
}