<?php
declare(strict_types=1);

require_once __DIR__ . '/../modules/Env.php';

Env::loadFromProjectRoot();

if (!defined('APP_TIMEZONE')) {
    $configuredTimezone = trim((string)(getenv('APP_TIMEZONE') ?: 'asia/nicosia'));
    $timezoneAliasMap = [
        'asia/nicosia' => 'Europe/Nicosia',
    ];

    $normalizedTimezone = $timezoneAliasMap[strtolower($configuredTimezone)] ?? $configuredTimezone;
    if (!in_array($normalizedTimezone, timezone_identifiers_list(), true)) {
        $normalizedTimezone = 'Europe/Nicosia';
    }

    date_default_timezone_set($normalizedTimezone);
    define('APP_TIMEZONE', $normalizedTimezone);
}

if (!defined('BASE_URL')) {
    $configuredBase = (string)(getenv('APP_BASE_PATH') ?: '/academic/University-Web-Applications-System-A');
    $configuredBase = '/' . trim($configuredBase, '/');
    define('BASE_URL', $configuredBase);
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        $base = rtrim(BASE_URL, '/');
        $path = ltrim($path, '/');
        return $path === '' ? $base : ($base . '/' . $path);
    }
}

if (!function_exists('frontend_url')) {
    function frontend_url(string $path = ''): string
    {
        return app_url('frontend/' . ltrim($path, '/'));
    }
}

if (!function_exists('backend_url')) {
    function backend_url(string $path = ''): string
    {
        return app_url('backend/' . ltrim($path, '/'));
    }
}
