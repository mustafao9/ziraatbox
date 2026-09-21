<?php
$e_rapor = E_ALL & ~E_DEPRECATED & ~E_NOTICE;
error_reporting($e_rapor);

ini_set('display_errors', '0');
set_time_limit(300);
ini_set('memory_limit', '256M');

$aktif = __FILE__;
$d1 = dirname($aktif);
$d2 = dirname($d1);
$root = dirname($d2);

$yedekler =$d1 . "/yedekler/";

if (!is_dir($yedekler)) {
    mkdir($yedekler, 0755, true);
}

ini_set('log_errors', '1');
ini_set('error_log', $yedekler . 'guncelle_hata.log');

$ayar =$root . "/sistem/ayar.php";

if (file_exists($ayar)) {
    require_once $ayar;
} else {
    die("Ayar dosyasi yok.");
}

if (function_exists('session_start') && session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Güvenlik kontrolü
if (!isset($_SESSION['admin_id'])) {
    die("Yetkisiz erisim.");
}

if (!isset($_SESSION['yetki']) \vert{}\vert{}$_SESSION['yetki'] != 'admin') {
    die("Yetkisiz erisim.");
}

$islem = isset($_GET['islem']) ? $_GET['islem'] : '';$versiyon = isset($_GET['version']) ?$_GET['version'] : '1.0.6';
$force = isset($_GET['force']) &&$_GET['force'] == 1;

try {

    if ($islem === 'guncelle') {

        $repo = "mustafao9/ziraatbox";

        if (!class_exists('ZipArchive')) {
            throw new Exception("ZipArchive eklentisi yok.");
        }

        if ($force) {
            $url = "https://github.com/" . $repo . "/archive/refs/heads/main.zip";
        } else {
            $url = "https://github.com/" . $repo . "/archive/refs/tags/v" . $versiyon . ".zip";
        }

        $tmp =$yedekler . "update.zip";

        // GitHub'dan ZIP indir
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZiraatBox');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // Sürüm ZIP'i alınamazsa main branch'i dene
        if ($code != 200 \vert{}\vert{} empty($data)) {

            $url2 = "https://github.com/" . $repo . "/archive/refs/heads/main.zip";

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL,$url2);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'ZiraatBox');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

            $data = curl_exec($ch);

            curl_close($ch);
        }

        if (empty($data)) {
            throw new Exception("GitHub verisi alinamadi.");
        }

        if (file_put_contents($tmp,$data) === false) {
            throw new Exception("Tmp zip yazilamadi.");
        }

        // ZIP dosyasını aç
        $zip = new ZipArchive();

        if ($zip->open($tmp) === true) {

            $f_name = $zip->getNameIndex(0);$yazilamayanlar = array();

            for ($i = 0; $i < $zip->numFiles; $i++) {

                $name =$zip->getNameIndex($i);$rel = substr($name, strlen($f_name));

                if (empty($rel)) {
                    continue;
                }

                // uploads/ standartına göre korunan dizinler ve dosyalar
                if (
                    strpos($rel, 'uploads/') === 0 \vert{}\vert{}$rel === '.env' ||
                    strpos($rel, 'yonetim/yedekler/') === 0
                ) {
                    continue;
                }

                $hedef = $root . '/' . ltrim($rel, '/');

                // Klasör oluşturma
                if (substr($name, -1) === '/') {

                    if (!is_dir($hedef)) {
                        mkdir($hedef, 0755, true);
                    }

                } else {

                    $d = dirname($hedef);

                    if (!is_dir($d)) {
                        mkdir($d, 0755, true);
                    }

                    $icerik = $zip->getFromIndex($i);

                    if ($icerik !== false) {$yazildi = file_put_contents($hedef,$icerik);
                        if ($yazildi === false) {
                            $yazilamayanlar[] =$rel;
                            error_log("Yazilamadi: " . $hedef);
                        }
                    }
                }
            }

            $zip->close();

            if (file_exists($tmp)) {
                unlink($tmp);
            }

            if (!empty($yazilamayanlar)) {
                throw new Exception(count($yazilamayanlar) . " adet dosya yazilamadi (Izin hatasi).");
            }

        } else {
            throw new Exception("Zip acilamadi.");
        }

        // İletişim mesajları tablosunu oluştur
        if (isset($db)) {

            $db->exec("
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
                ) ENGINE=InnoDB
                DEFAULT CHARSET=utf8mb4
                COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // Versiyon dosyasını oluştur
        $v_icerik = "<?php\n";
        $v_icerik .= "if (!defined('SISTEM_VERSIYON')) {\n";
        $v_icerik .= "    define('SISTEM_VERSIYON', '" . $versiyon . "');\n";
        $v_icerik .= "}\n";

        file_put_contents(
            $root . "/sistem/versiyon.php",
            $v_icerik
        );

        header(
            "Location: ../guncelleme.php?durum=guncellendi&v=" .
            urlencode($versiyon)
        );

        exit;
    }

} catch (Exception $ex) {

    $err = urlencode($ex->getMessage());

    header(
        "Location: ../guncelleme.php?durum=hata&msg=" . $err
    );

    exit;
}