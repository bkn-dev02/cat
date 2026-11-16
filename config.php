<?php
require_once __DIR__ . '/env.php';

/**
 * Database Configuration - AUTO DETECT ENVIRONMENT
 * 
 * Cara kerja:
 * - Jika di localhost → pakai config.local.php
 * - Jika di production → pakai config.production.php
 * 
 * Atau bisa manual set environment dengan uncomment salah satu:
 */

// Set APP_ENV explicitly on each server when possible.
if (!defined('ENVIRONMENT')) {
    $environment = getenv('APP_ENV');

    if ($environment === false || $environment === '') {
        $serverAddress = $_SERVER['SERVER_ADDR'] ?? '';
        $httpHost = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST) ?: '';
        $localServerAddresses = ['127.0.0.1', '::1'];
        $localHttpHosts = ['localhost', '127.0.0.1', '::1'];
        $environment = in_array($serverAddress, $localServerAddresses, true)
            || in_array(strtolower($httpHost), $localHttpHosts, true)
            ? 'local'
            : 'production';
    }

    if (!in_array($environment, ['local', 'production'], true)) {
        throw new RuntimeException('APP_ENV must be local or production.');
    }

    define('ENVIRONMENT', $environment);
}

// ============================================
// SET TIMEZONE KE WIB (Asia/Jakarta)
// ============================================
date_default_timezone_set('Asia/Jakarta');

// ============================================
// LOAD CONFIG SESUAI ENVIRONMENT
// ============================================
$configFile = __DIR__ . '/config.' . ENVIRONMENT . '.php';

if (!file_exists($configFile)) {
    die('Config file not found: ' . $configFile);
}

$config = require $configFile;

// Tambahkan info environment ke config
$config['environment'] = ENVIRONMENT;
$config['app_debug'] = filter_var(
    project_env_value('APP_DEBUG', ENVIRONMENT === 'local' ? 'true' : 'false'),
    FILTER_VALIDATE_BOOLEAN
);

return $config;
