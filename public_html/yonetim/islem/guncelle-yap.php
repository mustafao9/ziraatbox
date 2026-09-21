<?php
/**
 * ZiraatBox - Otomatik Güncelleme & Migration Motoru (500 Hata Korumalı)
 */
// 1. HATALARI YOLDA YAKALAMAK İÇİN AÇALIM
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

set_time_limit(300);
ini_set('memory_limit', '256M');

require_once "../../sistem/ayar.php";

// Admin Kontrolü
if (!isset($_SESSION['admin_id']) && (!isset($_SESSION['yetki']) \vert{}\vert{}$_SESSION['yetki'] != 'admin')) {
    die("❌ Hata: Yetkisiz erişim. Lütfen admin girişi yapın.");
}

$islem = $_GET['islem'] ?? '';$root_dir = dirname(dirname(__DIR__)); // public_html/
$yedek_dizini = __DIR__ . "/../yedekler/";

// Klasör Yoksa Oluşturmayı Dene
if (!is_dir($yedek_dizini)) {
    @mkdir($yedek_dizini, 0755, true);
}

try {
    if ($islem === 'guncelle') {
        $yeni_versiyon =$_GET['version'] ?? '1.0.5';
        $github_repo = "mustafao9/ziraatbox";

        // ZipArchive Eklentisi Kontrolü
        if (!class_exists('ZipArchive')) {
            throw new Exception("Sunucuda PHP ZipArchive eklentisi aktif değil. Lütfen cPanel > Select PHP Version alanından zip eklentisini açın.");
        }

        // ADIM 1: GITHUB'DAN ZIP İNDİR (cURL ile SSL Bypassed)
        $zip_download_url = "https://github.com/$github_repo/archive/refs/tags/v$yeni_versiyon.zip";
        $temp_zip = $yedek_dizini . "download_v$yeni_versiyon.zip";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$zip_download_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZiraatBox-AutoUpdater');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $zip_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Eğer Tag zip verisi bulunamazsa main dalını çek
        if ($http_code != 200 \vert{}\vert{} !$zip_data) {
            $fallback_url = "https://github.com/$github_repo/archive/refs/heads/main.zip";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$fallback_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'ZiraatBox-AutoUpdater');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $zip_data = curl_exec($ch);
            curl_close($ch);
        }

        if (!$zip_data) {
            throw new Exception("GitHub sunucusundan paket indirilemedi. Lütfen internet/bağlantı ayarlarını kontrol edin.");
        }

        file_put_contents($temp_zip,$zip_data);

        // ADIM 2: ZIP DOSYASINI DOSYALARIN ÜZERİNE ÇIKAR
        $zip = new ZipArchive();
        if ($zip->open($temp_zip) === TRUE) {
            $root_folder_in_zip =$zip->getNameIndex(0);

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename =$zip->getNameIndex($i);$relative_file = substr($filename, strlen($root_folder_in_zip));

                if (empty($relative_file)) continue;

                // Korunacak özel klasör ve dosyalar
                if (strpos($relative_file, 'uploads/') === 0 || $relative_file === '.env' \vert{}\vert{} strpos($relative_file, 'yonetim/yedekler/') === 0) continue;

                $target_path = $root_dir . '/' .$relative_file;

                if (substr($filename, -1) === '/') {
                    if (!is_dir($target_path)) @mkdir($target_path, 0755, true);
                } else {
                    $dir = dirname($target_path);
                    if (!is_dir($dir)) @mkdir($dir, 0755, true);
                    @file_put_contents($target_path, $zip->getFromIndex($i));
                }
            }
            $zip->close();
            @unlink($temp_zip);
        } else {
            throw new Exception("İndirilen paket zip olarak açılamadı. Dosya bozuk veya eksik inmiş olabilir.");
        }

        // ADIM 3: VERİTABANI KONTROLÜ / İLETİŞİM TABLOSU DÜZELTMESİ
        if (isset($db)) {$db->exec("
                CREATE TABLE IF NOT EXISTS `iletisim_mesajlari` (
                  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                  `ad_soyad` varchar(100) NOT NULL,
                  `eposta` varchar(120) NOT NULL,
                  `konu` varchar(255) DEFAULT NULL,
                  `mesaj` text NOT NULL,
                  `ip_adresi` varchar(45) DEFAULT NULL,
                  `durum` enum('okunmadi','okundu','cevaplandi') DEFAULT 'okunmadi',
                  `created_at` timestamp NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // ADIM 4: VERSIYON DOSYASINI GÜNCELLE
        $versiyon_file_content = "<?php\nif (!defined('SISTEM_VERSIYON')) {\n    define('SISTEM_VERSIYON', '" . $yeni_versiyon . "');\n}\n";
        @file_put_contents($root_dir . "/sistem/versiyon.php", $versiyon_file_content);

        // BAŞARILI YÖNLENDİRME
        header("Location: ../guncelleme.php?durum=guncellendi&v=" . $yeni_versiyon);
        exit;
    }

} catch (Exception $e) {
    // 500 HAKKINI ENGELLEYİP NET HATAYI BİLDİRELİM
    header("Location: ../guncelleme.php?durum=hata&msg=" . urlencode($e->getMessage()));
    exit;
}