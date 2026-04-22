<?php
/*Name: CSRF token class
  Description: This class is responsible for generating and validating CSRF tokens to protect against Cross-Site Request Forgery attacks.
  Paraskevas Vafeiadis
  13-Apr-2024 v0.1
  */


class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    private static function startSessionIfPossible(): bool
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return true;
        }

        if (headers_sent()) {
            error_log('Csrf::startSessionIfPossible skipped: headers already sent');
            return false;
        }

        session_start();
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public static function ensureToken(): string
    {
        if (!self::startSessionIfPossible()) {
            return '';
        }

        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return (string)$_SESSION[self::SESSION_KEY];
    }

    public static function validateRequestToken(): bool
    {
        if (!self::startSessionIfPossible()) {
            return false;
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        $postedToken = $_POST['_csrf'] ?? '';
        if (!is_string($postedToken) || $postedToken === '') {
            $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            $postedToken = is_string($headerToken) ? $headerToken : '';
        }

        return $postedToken !== '' && hash_equals($sessionToken, $postedToken);
    }

    public static function reject(bool $expectsJson): void
    {
        http_response_code(403);

        if ($expectsJson) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid request token']);
            exit();
        }

        echo 'Request validation failed.';
        exit();
    }
}
