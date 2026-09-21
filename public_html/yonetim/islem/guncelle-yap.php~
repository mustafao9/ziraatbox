<?php
/**
 * ZiraatBox - En Sade ve Kesin Uyumlu Güncelleme Motoru
 */
ini_set('display_errors', '0');
error_reporting(E_ALL);
set_time_limit(300);
ini_set('memory_limit', '256M');

$aktif_dosya = __FILE__;
$islem_klasoru = dirname($aktif_dosya);
$yonetim_klasoru = dirname($islem_klasoru);
$root_dir = dirname($yonetim_klasoru);

$yedek_dizini =$yonetim_klasoru . "/yedekler/";
if (!is_dir($yedek_dizini)) {
    mkdir($yedek_dizini, 0755, true);
}

ini_set('log_errors', '1');
ini_set('error_log', $yedek_dizini . 'guncelle_hata.log');

$ayar_dosyasi =$root_dir . "/sistem/ayar.php";
if (file_exists($ayar_dosyasi)) {
    require_once $ayar_dosyasi;
} else {
    die("Kritik Hata: Sistem ayar dosyasi bulunamadi.");
}

if (function_exists('session_start') && !isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['admin_id']) && (!isset($_SESSION['yetki']) \vert{}\vert{}$_SESSION['yetki'] != 'admin')) {
    die("Yetkisiz erisim.");
}

$islem = isset($_GET['islem']) ? $_GET['islem'] : '';$yeni_versiyon = isset($_GET['version']) ?$_GET['version'] : '1.0.6';
$force = isset($_GET['force']) &&$_GET['force'] == 1;

try {
    if ($islem === 'guncelle') {$github_repo = "mustafao9/ziraatbox";

        if (!class_exists('ZipArchive')) {
            throw new Exception("Sunucuda ZipArchive PHP eklentisi aktif degil.");
        }

        if ($force) {
            $zip_download_url = "https://github.com/" . $github_repo . "/archive/refs/heads/main.zip";
        } else {
            $zip_download_url = "https://github.com/" . $github_repo . "/archive/refs/tags/v" . $yeni_versiyon . ".zip";
        }

        $temp_zip =$yedek_dizini . "download_update.zip";

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

        if ($http_code != 200 \vert{}\vert{} empty($zip_data)) {
            $fallback_url = "https://github.com/" . $github_repo . "/archive/refs/heads/main.zip";
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

        if (empty($zip_data)) {
            throw new Exception("GitHub deposundan paket indirilemedi.");
        }

        if (file_put_contents($temp_zip,$zip_data) === false) {
            throw new Exception("Gecici zip dosyasi yazilamadi.");
        }

        $zip = new ZipArchive();
        if ($zip->open($temp_zip) === TRUE) {
            $root_folder_in_zip =$zip->getNameIndex(0);

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename =$zip->getNameIndex($i);$relative_file = substr($filename, strlen($root_folder_in_zip));

                if (empty($relative_file)) continue;

                if (strpos($relative_file, 'uploads/') === 0 || $relative_file === '.env' \vert{}\vert{} strpos($relative_file, 'yonetim/yedekler/') === 0) {
                    continue;
                }

                $target_path = $root_dir . '/' . ltrim($relative_file, '/');

                if (substr($filename, -1) === '/') {
                    if (!is_dir($target_path)) {
                        mkdir($target_path, 0755, true);
                    }
                } else {
                    $dir = dirname($target_path);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $content = $zip->getFromIndex($i);
                    if ($content !== false) {
                        file_put_contents($target_path,$content);
                    }
                }
            }
            $zip->close();
            unlink($temp_zip);
        } else {
            throw new Exception("Indirilen zip dosyasi acilamadi.");
        }

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

        $versiyon_file_content = "<?php\nif (!defined('SISTEM_VERSIYON')) {\n    define('SISTEM_VERSIYON', '" . $yeni_versiyon . "');\n}\n";
        file_put_contents($root_dir . "/sistem/versiyon.php", $versiyon_file_content);

        header("Location: ../guncelleme.php?durum=guncellendi&v=" . urlencode($yeni_versiyon));
        exit;
    }
} catch (Exception $e) {
    $hata_mesaji = urlencode($e->getMessage());
    header("Location: ../guncelleme.php?durum=hata&msg=" . $hata_mesaji);
    exit;
}