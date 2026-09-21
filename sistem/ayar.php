<?php
/**
 * ZiraatBox - Akıllı Yapılandırma Dosyası (v4.0 Kök Dizin & Dinamik URL Uyumlu)
 * Mustafa Satılmış - Webmaster
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

// 1. .env OKUYUCU FONKSİYON (Bir Üst Klasör & Öncelikli Arama)
if (!function_exists('env')) {
    function env($key, $default = null) {
        static $envVars = null;
        if ($envVars === null) {
            $envVars = [];
            
            // .env Arama Sırası (Önce Bir Üst Güvenli Klasör, Sonra Kök Dizin)
            $possiblePaths = [
                dirname(__DIR__, 2) . '/.env',        // 1. Güvenli Konum: /var/www/.env veya /home/ziraatbo/.env (Bir Üst Dizin)
                dirname(__DIR__) . '/.env',           // 2. /var/www/html/.env (Proje Kök Dizini)
                $_SERVER['DOCUMENT_ROOT'] . '/.env'   // 3. Web Sunucusu Kökü
            ];

            $envPath = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $envPath = $path;
                    break;
                }
            }
            
            if ($envPath && file_exists($envPath)) {
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

// 2. VERİTABANI BAĞLANTISI (Local & Production Uyumlu)
try {
    $db_host = env('DB_HOST', '127.0.0.1');
    $db_name = env('DB_NAME', env('DB_DATABASE', 'ziraatbo_ziraatbox'));
    $db_user = env('DB_USER', env('DB_USERNAME', 'root'));
    $db_pass = env('DB_PASS', env('DB_PASSWORD', '123456'));

    $db = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    if (env('APP_DEBUG', 'true') === 'true' || env('APP_ENV', 'local') === 'local') {
        die("<strong>Veritabanı Bağlantı Hatası:</strong> " . $e->getMessage());
    } else {
        die("Sistem şu an teknik bir çalışma nedeniyle bakım aşamasındadır.");
    }
}

// 3. OTOMATİK DİNAMİK URL HESAPLAMA (Eski ziraatbox/public_html Yollarını Temizler)
if (!defined('URL')) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // .env dosyasından APP_URL tanımlıysa alır, tanımlı değilse adresten dinamik üretir
    $defaultUrl = $protocol . $host;
    define('URL', rtrim(env('APP_URL', $defaultUrl), '/'));
}

if (!defined('SITE_ADI')) {
    define('SITE_ADI', 'ZiraatBox');
}

date_default_timezone_set('Europe/Istanbul');

// 4. BAĞIMLI DOSYALARIN YÜKLENMESİ
if (file_exists(__DIR__ . "/fonksiyonlar.php")) {
    require_once __DIR__ . "/fonksiyonlar.php";
}

if (file_exists(__DIR__ . "/versiyon.php")) {
    require_once __DIR__ . "/versiyon.php";
}
