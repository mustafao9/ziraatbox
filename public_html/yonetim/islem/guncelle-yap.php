<?php

/**
 * ZiraatBox - Güvenli Otomatik Güncelleme Motoru
 *
 * Akış:
 * 1. Yetki kontrolü
 * 2. Canlı kökün tespiti
 * 3. GitHub ZIP indirme
 * 4. ZIP doğrulama
 * 5. ZIP'i geçici alana çıkarma
 * 6. Proje kökünün bulunması
 * 7. Canlı sistemin yedeğinin alınması
 * 8. Yazma izinlerinin test edilmesi
 * 9. Dosyaların canlı sisteme aktarılması
 * 10. Dosyaların doğrulanması
 * 11. Veritabanı güncellemesi
 * 12. Versiyon dosyasının oluşturulması
 * 13. Başarılı / başarısız sonuç
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '0');

set_time_limit(600);
ini_set('memory_limit', '512M');

/* -----------------------------------------------------------
 * TEMEL YOLLAR
 * ----------------------------------------------------------- */

$aktif = __FILE__;
$islem_dizini = dirname($aktif);
$yonetim_dizini = dirname($islem_dizini);
$root = dirname($yonetim_dizini);

$yedekler =$islem_dizini . DIRECTORY_SEPARATOR . 'yedekler';
$log_dosyasi =$yedekler . DIRECTORY_SEPARATOR . 'guncelle_hata.log';

if (!is_dir($yedekler)) {
    if (!mkdir($yedekler, 0755, true) && !is_dir($yedekler)) {
        die('Yedek klasoru olusturulamadi.');
    }
}

ini_set('log_errors', '1');
ini_set('error_log', $log_dosyasi);

/* -----------------------------------------------------------
 * AYAR DOSYASI
 * ----------------------------------------------------------- */

$ayar =$root . DIRECTORY_SEPARATOR . 'sistem' . DIRECTORY_SEPARATOR . 'ayar.php';

if (!file_exists($ayar)) {
    die('Ayar dosyasi bulunamadi: ' . htmlspecialchars($ayar, ENT_QUOTES, 'UTF-8'));
}

require_once $ayar;

/* -----------------------------------------------------------
 * SESSION & GÜVENLİK
 * ----------------------------------------------------------- */

if (function_exists('session_status') && session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['yetki']) \vert{}\vert{}$_SESSION['yetki'] !== 'admin') {
    die('Yetkisiz erisim.');
}

/* -----------------------------------------------------------
 * GİRDİLER
 * ----------------------------------------------------------- */

$islem = isset($_GET['islem']) ? trim((string) $_GET['islem']) : '';
$versiyon = isset($_GET['version']) ? trim((string) $_GET['version']) : '';$force = (isset($_GET['force']) && (string)$_GET['force'] === '1');

if ($islem !== 'guncelle') {
    die('Gecersiz islem.');
}

if (!$force && !preg_match('/^\d+(?:\.\d+){0,3}$/',$versiyon)) {
    die('Gecersiz surum numarasi.');
}

/* -----------------------------------------------------------
 * SABİTLER
 * ----------------------------------------------------------- */

$repo = 'mustafao9/ziraatbox';

$github_url =$force
    ? 'https://github.com/' . $repo . '/archive/refs/heads/main.zip'
    : 'https://github.com/' . $repo . '/archive/refs/tags/v' .$versiyon . '.zip';

$korunan_yollar = array(
    'uploads',
    '.env',
    'yonetim/yedekler'
);

$ozel_dosyalar = array(
    'sistem/versiyon.php'
);

/* -----------------------------------------------------------
 * YARDIMCI FONKSİYONLAR
 * ----------------------------------------------------------- */

function guncelle_hata($mesaj)
{
    error_log('[' . date('Y-m-d H:i:s') . '] ' . $mesaj);
}

function temizle_dizin($dir)
{
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    if ($items === false) {
        return;
    }

    foreach ($items as$item) {
        if ($item === '.' \vert{}\vert{}$item === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path) && !is_link($path)) {
            temizle_dizin($path);
            @rmdir($path);
        } else {
            @unlink($path);
        }
    }

    @rmdir($dir);
}

function yol_korumali_mi($rel,$korunan_yollar)
{
    $rel = trim(str_replace('\\', '/',$rel), '/');

    foreach ($korunan_yollar as$korunan) {
        $korunan = trim(str_replace('\\', '/',$korunan), '/');

        if ($rel ===$korunan || strpos($rel,$korunan . '/') === 0) {
            return true;
        }
    }

    return false;
}

function guvenli_yol_mu($rel)
{
    $rel = str_replace('\\', '/',$rel);
    $rel = ltrim($rel, '/');

    if ($rel === '' \vert{}\vert{} strpos($rel, "\0") !== false) {
        return false;
    }

    $parts = explode('/',$rel);

    foreach ($parts as$part) {
        if ($part === '..') {
            return false;
        }
    }

    return true;
}

function normalize_rel_path($path)
{
    return ltrim(str_replace('\\', '/', $path), '/');
}

function zip_kok_dizini_bul($zip)
{
    $ilk = '';

    for ($i = 0; $i <$zip->numFiles; $i++) {$name = $zip->getNameIndex($i);
        if ($name === false) {
            continue;
        }

        $name = normalize_rel_path($name);
        if ($name === '') {
            continue;
        }

        $ilk =$name;
        break;
    }

    if ($ilk === '') {
        throw new Exception('ZIP dosyasi bos.');
    }

    $parts = explode('/',$ilk);
    if (count($parts) <= 1) {
        return '';
    }

    return $parts[0];
}

function klasor_kopyala($kaynak,$hedef, $korunan_yollar,$ozel_dosyalar, &$yazilanlar, &$hatalar)
{
    if (!is_dir($kaynak)) {
        throw new Exception('Kaynak proje klasoru bulunamadi: ' . $kaynak);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($kaynak, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {$source_path = $item->getPathname();$relative = substr($source_path, strlen($kaynak));
        $relative = normalize_rel_path($relative);

        if ($relative === '') {
            continue;
        }

        if (!guvenli_yol_mu($relative)) {
            $hatalar[] = 'Guvensiz yol reddedildi: ' .$relative;
            continue;
        }

        if (yol_korumali_mi($relative,$korunan_yollar)) {
            continue;
        }

        if (in_array($relative,$ozel_dosyalar, true)) {
            continue;
        }

        $target_path = $hedef . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

        if ($item->isDir()) {
            if (!is_dir($target_path)) {
                if (!mkdir($target_path, 0755, true) && !is_dir($target_path)) {
                    $hatalar[] = 'Klasor olusturulamadi: ' .$target_path;
                }
            }
            continue;
        }

        $target_dir = dirname($target_path);

        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true) && !is_dir($target_dir)) {
                $hatalar[] = 'Hedef klasor olusturulamadi: ' .$target_dir;
                continue;
            }
        }

        if (file_exists($target_path) && !is_writable($target_path)) {
            $hatalar[] = 'Mevcut dosya yazilabilir degil: ' .$target_path;
            continue;
        }

        if (!file_exists($target_path) && !is_writable($target_dir)) {
            $hatalar[] = 'Hedef klasor yazilabilir degil: ' .$target_dir;
            continue;
        }

        $ok = @copy($source_path,$target_path);

        if (!$ok) {
            $hatalar[] = 'Dosya kopyalanamadi: ' .$target_path;
            continue;
        }

        if (!file_exists($target_path) || filesize($target_path) !== filesize($source_path)) {
            $hatalar[] = 'Dosya dogrulanamadi: ' .$target_path;
            continue;
        }

        $yazilanlar[] =$relative;
    }
}

/* -----------------------------------------------------------
 * ANA GÜNCELLEME İŞLEMİ
 * ----------------------------------------------------------- */

$tmp_zip = '';
$tmp_extract = '';$backup_file = '';
$yazilanlar = array();$hatalar = array();

try {

    $root = realpath($root);
    if ($root === false) {
        throw new Exception('Canli sistem koku cozumlenemedi.');
    }
    $root = rtrim($root, DIRECTORY_SEPARATOR);

    if (!is_dir($root . DIRECTORY_SEPARATOR . 'sistem')) {
        throw new Exception('Canli sistem koku yanlis gorunuyor (sistem yok). Tespit edilen kok: ' . $root);
    }

    if (!is_dir($root . DIRECTORY_SEPARATOR . 'yonetim')) {
        throw new Exception('Canli sistem koku yanlis gorunuyor (yonetim yok). Tespit edilen kok: ' . $root);
    }

    // ZIP Indir
    $tmp_zip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ziraatbox_update_' . uniqid('', true) . '.zip';

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $github_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_USERAGENT => 'ZiraatBox-Updater/2.0',
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => array('Accept: application/zip')
    ));

    $data = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($data === false) {
        throw new Exception('GitHub baglantisi basarisiz: ' . $curl_error);
    }

    if ($http_code < 200 \vert{}\vert{}$http_code >= 300) {
        throw new Exception('GitHub ZIP indirilemedi. HTTP kodu: ' . $http_code);
    }

    if (strlen($data) < 100) {
        throw new Exception('GitHub tarafindan gecerli bir ZIP alinamadi.');
    }

    if (file_put_contents($tmp_zip,$data, LOCK_EX) === false) {
        throw new Exception('Gecici ZIP dosyasi yazilamadi: ' . $tmp_zip);
    }

    unset($data);

    // ZIP Kontrol
    $zip = new ZipArchive();$open_result = $zip->open($tmp_zip);

    if ($open_result !== true) {
        throw new Exception('GitHub ZIP dosyasi acilamadi. Kodu: ' . $open_result);
    }

    if ($zip->numFiles <= 0) {$zip->close();
        throw new Exception('GitHub ZIP dosyasi bos.');
    }

    $zip_root = zip_kok_dizini_bul($zip);

    // Gecici Cikarma Alani
    $tmp_extract = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ziraatbox_extract_' . uniqid('', true);

    if (!mkdir($tmp_extract, 0755, true)) {$zip->close();
        throw new Exception('Gecici guncelleme klasoru olusturulamadi.');
    }

    for ($i = 0; $i <$zip->numFiles; $i++) {$name = $zip->getNameIndex($i);
        if ($name === false) {
            continue;
        }

        $name = normalize_rel_path($name);
        if ($name === '') {
            continue;
        }

        if (!guvenli_yol_mu($name)) {
            $hatalar[] = 'ZIP icindeki guvensiz yol reddedildi: ' .$name;
            continue;
        }

        $destination = $tmp_extract . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $name);

        if (substr($name, -1) === '/') {
            if (!is_dir($destination) && !mkdir($destination, 0755, true)) {
                $hatalar[] = 'ZIP klasoru olusturulamadi: ' .$name;
            }
            continue;
        }

        $destination_dir = dirname($destination);

        if (!is_dir($destination_dir)) {
            if (!mkdir($destination_dir, 0755, true) && !is_dir($destination_dir)) {
                $hatalar[] = 'ZIP hedef klasoru olusturulamadi: ' .$destination_dir;
                continue;
            }
        }

        $content = $zip->getFromIndex($i);

        if ($content === false) {
            $hatalar[] = 'ZIP dosyasi okunamadi: ' .$name;
            continue;
        }

        if (file_put_contents($destination,$content, LOCK_EX) === false) {
            $hatalar[] = 'Gecici dosya olusturulamadi: ' .$destination;
        }
    }

    $zip->close();

    if (!empty($hatalar)) {
        throw new Exception('ZIP hazirlanirken ' . count($hatalar) . ' hata olustu.');
    }

    // Gercek Proje Koku
    $source_root = $tmp_extract . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $zip_root);

    if (!is_dir($source_root)) {
        throw new Exception('GitHub proje koku bulunamadi: ' . $source_root);
    }

    $kaynak_sistem =$source_root . DIRECTORY_SEPARATOR . 'sistem';
    $kaynak_yonetim =$source_root . DIRECTORY_SEPARATOR . 'yonetim';

    if (!is_dir($kaynak_sistem) && !is_dir($kaynak_yonetim)) {
        throw new Exception('GitHub ZIP icindeki proje yapisi taninamadi.');
    }

    // Canli Yazma Testi
    $izin_test_dosyasi =$root . DIRECTORY_SEPARATOR . 'sistem' . DIRECTORY_SEPARATOR . '.ziraatbox_update_test';
    $izin_test_ok = @file_put_contents($izin_test_dosyasi, 'ZiraatBox update permission test', LOCK_EX);

    if ($izin_test_ok === false) {
        throw new Exception('Canli sistem dizinine yazilamiyor. Sunucu dosya izinleri kontrol edilmeli.');
    }

    @unlink($izin_test_dosyasi);

    // Yedek Al
    $backup_file =$yedekler . DIRECTORY_SEPARATOR . 'AUTO_BEFORE_UPDATE_' . date('Y-m-d_H-i-s') . '.zip';
    $backup_zip = new ZipArchive();$backup_open = $backup_zip->open($backup_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    if ($backup_open !== true) {
        throw new Exception('Guncelleme oncesi yedek olusturulamadi. Kod: ' . $backup_open);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as$file) {
        if (!$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();$relative = normalize_rel_path(substr($path, strlen($root)));

        if ($relative === '' \vert{}\vert{} yol_korumali_mi($relative, array('yonetim/yedekler'))) {
            continue;
        }

        $backup_zip->addFile($path,$relative);
    }

    if (!$backup_zip->close()) {
        throw new Exception('Guncelleme yedeği tamamlanamadi.');
    }

    // Kopyalama Islemı
    $yazilanlar = array();$hatalar = array();

    klasor_kopyala($source_root,$root, $korunan_yollar,$ozel_dosyalar, $yazilanlar,$hatalar);

    if (!empty($hatalar)) {
        $ilk_hatalar = array_slice($hatalar, 0, 10);
        throw new Exception('Guncelleme sirasinda ' . count($hatalar) . ' hata olustu: ' . implode(' | ', $ilk_hatalar));
    }

    if (empty($yazilanlar)) {
        throw new Exception('GitHub paketinden canli sisteme hicbir dosya aktarilmadi.');
    }

    // Veritabani Tablosu
    if (isset($db) && is_object($db) && method_exists($db, 'exec')) {$db->exec("
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

    // Versiyon Dosyasi
    $versiyon_yaz = $versiyon !== '' ?$versiyon : 'dev';
    $versiyon_yaz = preg_replace('/[^0-9A-Za-z._-]/', '',$versiyon_yaz);
    if ($versiyon_yaz === '') {$versiyon_yaz = 'dev';
    }

    $v_icerik = "<?php\nif (!defined('SISTEM_VERSIYON')) {\n    define('SISTEM_VERSIYON', '" . addslashes($versiyon_yaz) . "');\n}\n";
    $versiyon_path =$root . DIRECTORY_SEPARATOR . 'sistem' . DIRECTORY_SEPARATOR . 'versiyon.php';

    if (file_put_contents($versiyon_path,$v_icerik, LOCK_EX) === false) {
        throw new Exception('Versiyon dosyasi yazilamadi: ' . $versiyon_path);
    }

    // Temizlik
    if ($tmp_zip !== '' && file_exists($tmp_zip)) {
        @unlink($tmp_zip);
    }

    if ($tmp_extract !== '' && is_dir($tmp_extract)) {
        temizle_dizin($tmp_extract);
    }

    header('Location: ../guncelleme.php?durum=guncellendi&v=' . urlencode($versiyon_yaz));
    exit;

} catch (Exception $ex) {

    $hata_mesaji =$ex->getMessage();
    guncelle_hata('GUNCELLEME BASARISIZ: ' . $hata_mesaji);

    if ($tmp_zip !== '' && file_exists($tmp_zip)) {
        @unlink($tmp_zip);
    }

    if ($tmp_extract !== '' && is_dir($tmp_extract)) {
        temizle_dizin($tmp_extract);
    }

    header('Location: ../guncelleme.php?durum=hata&msg=' . urlencode($hata_mesaji));
    exit;
}