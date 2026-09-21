<?php
/**
 * ZiraatBox - Akıllı Yapılandırma Dosyası
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

// .env OKUYUCU FONKSİYON (/home/ziraatbo/.env)
if (!function_exists('env')) {
    function env($key, $default = null) {
        static $envVars = null;
        if ($envVars === null) {
            $envVars = [];
            $envPath = dirname(__DIR__, 2) . '/.env';
            if (!file_exists($envPath)) {
                $envPath = __DIR__ . '/../.env';
            }
            
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || strpos($line, '#') === 0) continue;
                    if (strpos($line, '=') !== false) {
                        list($name, $value) = explode('=', $line, 2);
                        $envVars[trim($name)] = trim($value, " \t\n\r\0\x0B\"'");
                    }
                }
            }
        }
        return isset($envVars[$key]) ? $envVars[$key] : $default;
    }
}

// VERİTABANI BAĞLANTISI
try {
    $db_host = env('DB_HOST', 'localhost');
    $db_name = env('DB_NAME', 'ziraatbo_ziraatbox');
    $db_user = env('DB_USER', 'ziraatbo_ziraatbox');
    $db_pass = env('DB_PASS', 'Aa63856385*');

    $db = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Sistem şu an teknik bir çalışma nedeniyle bakım aşamasındadır.");
}

// SABİTLER
if (!defined('URL')) {
    define('URL', env('APP_URL', 'https://ziraatbox.com'));
}

if (!defined('SITE_ADI')) {
    define('SITE_ADI', 'ZiraatBox');
}

date_default_timezone_set('Europe/Istanbul');

if (file_exists(__DIR__ . "/fonksiyonlar.php")) {
    require_once __DIR__ . "/fonksiyonlar.php";
}