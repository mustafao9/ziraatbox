<?php

/**
 * ============================================================
 * ZIRAATBOX - GITHUB GÜNCELLEME SİSTEMİ
 * ============================================================
 *
 * Dosya:
 * /yonetim/islem/guncelle-yap.php
 *
 * Görev:
 * GitHub üzerinden sürüm indirir, yedek alır ve günceller.
 *
 * ============================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

set_time_limit(300);
ini_set('memory_limit', '256M');


/* ============================================================
   1. DİZİNLER
   ============================================================ */

$ISLEM_DIR  = __DIR__;
$YONETIM_DIR = dirname($ISLEM_DIR);
$ROOT_DIR   = dirname($YONETIM_DIR);

/*
 * Yönetim panelindeki rollback sistemiyle aynı klasör.
 */
$YEDEK_DIR = $YONETIM_DIR . DIRECTORY_SEPARATOR . 'yedekler';

/*
 * Güncelleme kilidi.
 */
$LOCK_FILE = $ISLEM_DIR . DIRECTORY_SEPARATOR . '.guncelleme.lock';

/*
 * Log.
 */
$LOG_FILE = $ISLEM_DIR . DIRECTORY_SEPARATOR . 'guncelleme.log';


/* ============================================================
   2. LOG SİSTEMİ
   ============================================================ */

ini_set('error_log', $LOG_FILE);

function update_log($message)
{
    global $LOG_FILE;

    $line =
        '[' .
        date('Y-m-d H:i:s') .
        '] ' .
        $message .
        PHP_EOL;

    @file_put_contents(
        $LOG_FILE,
        $line,
        FILE_APPEND | LOCK_EX
    );
}


/* ============================================================
   3. HATA FONKSİYONU
   ============================================================ */

function update_error($message)
{
    update_log('HATA: ' . $message);

    throw new Exception($message);
}


/* ============================================================
   4. YÖNETİM OTURUMU
   ============================================================ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* ============================================================
   5. AYAR DOSYASI
   ============================================================ */

$AYAR_FILE =
    $ROOT_DIR .
    DIRECTORY_SEPARATOR .
    'sistem' .
    DIRECTORY_SEPARATOR .
    'ayar.php';

if (!file_exists($AYAR_FILE)) {

    die(
        'Sistem ayar dosyasi bulunamadi: ' .
        htmlspecialchars(
            $AYAR_FILE,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

require_once $AYAR_FILE;


/* ============================================================
   6. ADMİN KONTROLÜ
   ============================================================ */

if (
    !isset($_SESSION['admin_id']) ||
    empty($_SESSION['admin_id'])
) {
    die('Yetkisiz erisim.');
}

if (
    isset($_SESSION['yetki']) &&
    $_SESSION['yetki'] !== 'admin'
) {
    die('Yetkisiz erisim.');
}


/* ============================================================
   7. PARAMETRELER
   ============================================================ */

$islem = isset($_GET['islem'])
    ? trim($_GET['islem'])
    : '';

$versiyon = isset($_GET['version'])
    ? trim($_GET['version'])
    : '';

$force =
    isset($_GET['force']) &&
    $_GET['force'] == '1';


if ($islem !== 'guncelle') {
    die('Gecersiz guncelleme islemi.');
}


/*
 * Örnek:
 * 1.0.6
 * 2.1
 * 10.4.12
 */
if ($versiyon === '') {
    die('Guncellenecek versiyon belirtilmedi.');
}

if (
    !preg_match(
        '/^[0-9]+(\.[0-9]+){0,3}$/',
        $versiyon
    )
) {
    die('Gecersiz versiyon numarasi.');
}


/* ============================================================
   8. GITHUB AYARLARI
   ============================================================ */

$GITHUB_REPO = 'mustafao9/ziraatbox';

$GITHUB_TAG_URL =
    'https://github.com/' .
    $GITHUB_REPO .
    '/archive/refs/tags/v' .
    $versiyon .
    '.zip';

$GITHUB_MAIN_URL =
    'https://github.com/' .
    $GITHUB_REPO .
    '/archive/refs/heads/main.zip';


/* ============================================================
   9. KLASÖRÜ OLUŞTUR
   ============================================================ */

if (!is_dir($YEDEK_DIR)) {

    if (!@mkdir($YEDEK_DIR, 0755, true)) {

        die(
            'Yedek klasoru olusturulamadi: ' .
            htmlspecialchars(
                $YEDEK_DIR,
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }
}


/* ============================================================
   10. YAZMA KONTROLÜ
   ============================================================ */

if (!is_writable($ISLEM_DIR)) {

    die(
        'Guncelleme klasoru yazilabilir degil: ' .
        htmlspecialchars(
            $ISLEM_DIR,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

if (!is_writable($YEDEK_DIR)) {

    die(
        'Yedek klasoru yazilabilir degil: ' .
        htmlspecialchars(
            $YEDEK_DIR,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}


/* ============================================================
   11. GÜNCELLEME KİLİDİ
   ============================================================ */

$lockHandle = @fopen($LOCK_FILE, 'c');

if ($lockHandle === false) {

    die(
        'Guncelleme kilit dosyasi olusturulamadi.'
    );
}

if (!@flock($lockHandle, LOCK_EX | LOCK_NB)) {

    fclose($lockHandle);

    die(
        'Baska bir guncelleme islemi zaten devam ediyor.'
    );
}


/* ============================================================
   12. GEÇİCİ DOSYA LİSTESİ
   ============================================================ */

$tmpZip = false;
$tmpDir = false;
$backupZip = false;

$backupManifest = array();

$updatedFiles = array();

$newFiles = array();

$success = false;


/* ============================================================
   13. GÜVENLİ RELATIVE PATH
   ============================================================ */

function safe_relative_path($path)
{
    $path = str_replace('\\', '/', $path);

    /*
     * Başındaki slash'ları kaldır.
     */
    $path = ltrim($path, '/');

    $parts = explode('/', $path);

    $clean = array();

    foreach ($parts as $part) {

        if ($part === '' || $part === '.') {
            continue;
        }

        if ($part === '..') {
            throw new Exception(
                'Guvenli olmayan ZIP yolu tespit edildi.'
            );
        }

        $clean[] = $part;
    }

    return implode('/', $clean);
}


/* ============================================================
   14. KORUNACAK DOSYA/KLASÖRLER
   ============================================================ */

function is_protected_path($relative)
{
    $relative = str_replace('\\', '/', $relative);
    $relative = ltrim($relative, '/');

    /*
     * Kullanıcı yüklemeleri.
     */
    if (
        $relative === 'uploads' ||
        strpos($relative, 'uploads/') === 0
    ) {
        return true;
    }

    /*
     * Ortam dosyası.
     */
    if ($relative === '.env') {
        return true;
    }

    /*
     * Veritabanı / sistem ayarları.
     */
    if ($relative === 'sistem/ayar.php') {
        return true;
    }

    /*
     * Yedekler.
     */
    if (
        $relative === 'yonetim/yedekler' ||
        strpos($relative, 'yonetim/yedekler/') === 0
    ) {
        return true;
    }

    /*
     * Güncelleme kilidi.
     */
    if ($relative === 'yonetim/islem/.guncelleme.lock') {
        return true;
    }

    /*
     * Güncelleme logu.
     */
    if ($relative === 'yonetim/islem/guncelleme.log') {
        return true;
    }

    return false;
}


/* ============================================================
   15. DOSYAYI GÜVENLİ ŞEKİLDE KOPYALA
   ============================================================ */

function copy_update_file($source, $destination)
{
    $destinationDir = dirname($destination);

    if (!is_dir($destinationDir)) {

        if (!@mkdir($destinationDir, 0755, true)) {

            throw new Exception(
                'Klasor olusturulamadi: ' .
                $destinationDir
            );
        }
    }

    /*
     * Var olan dosya varsa yazılabilir mi?
     */
    if (
        file_exists($destination) &&
        !is_writable($destination)
    ) {

        throw new Exception(
            'Dosya yazilabilir degil: ' .
            $destination
        );
    }

    /*
     * Hedef klasör yazılabilir mi?
     */
    if (!is_writable($destinationDir)) {

        throw new Exception(
            'Hedef klasor yazilabilir degil: ' .
            $destinationDir
        );
    }

    /*
     * Önce geçici dosyaya yaz.
     */
    $tempDestination =
        $destination .
        '.update_tmp_' .
        bin2hex(random_bytes(5));

    if (!@copy($source, $tempDestination)) {

        throw new Exception(
            'Dosya kopyalanamadi: ' .
            $destination
        );
    }

    /*
     * Mevcut izinleri korumaya çalış.
     */
    if (file_exists($destination)) {

        $permissions = @fileperms($destination);

        if ($permissions !== false) {
            @chmod(
                $tempDestination,
                $permissions & 0777
            );
        }
    } else {

        @chmod(
            $tempDestination,
            0644
        );
    }

    /*
     * Atomik değiştirme.
     */
    if (!@rename($tempDestination, $destination)) {

        /*
         * rename başarısız olursa unlink + rename denemesi.
         */
        if (file_exists($destination)) {

            if (!@unlink($destination)) {

                @unlink($tempDestination);

                throw new Exception(
                    'Eski dosya kaldirilamadi: ' .
                    $destination
                );
            }
        }

        if (!@rename($tempDestination, $destination)) {

            @unlink($tempDestination);

            throw new Exception(
                'Guncel dosya hedefe tasinamadi: ' .
                $destination
            );
        }
    }

    return true;
}


/* ============================================================
   16. KLASÖR OLUŞTUR
   ============================================================ */

function ensure_directory($directory)
{
    if (is_dir($directory)) {
        return true;
    }

    if (!@mkdir($directory, 0755, true)) {

        if (!is_dir($directory)) {

            throw new Exception(
                'Klasor olusturulamadi: ' .
                $directory
            );
        }
    }

    return true;
}


/* ============================================================
   17. ZIP İNDİR
   ============================================================ */

function download_github_zip($url, $destination)
{
    if (!function_exists('curl_init')) {

        throw new Exception(
            'Sunucuda cURL eklentisi bulunamadi.'
        );
    }

    /*
     * Hedef dosya oluştur.
     */
    $fp = @fopen($destination, 'wb');

    if ($fp === false) {

        throw new Exception(
            'ZIP dosyasi icin gecici dosya acilamadi: ' .
            $destination
        );
    }

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FILE, $fp);

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    curl_setopt(
        $ch,
        CURLOPT_USERAGENT,
        'Mozilla/5.0 ZiraatBox-Updater'
    );

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    /*
     * HTTP hata kodlarını başarısız say.
     */
    curl_setopt($ch, CURLOPT_FAILONERROR, false);

    $result = curl_exec($ch);

    $httpCode =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

    $error =
        curl_error($ch);

    curl_close($ch);

    fclose($fp);

    if ($result === false) {

        @unlink($destination);

        throw new Exception(
            'GitHub ZIP indirilemedi: ' .
            $error
        );
    }

    if ($httpCode < 200 || $httpCode >= 300) {

        @unlink($destination);

        throw new Exception(
            'GitHub ZIP indirilemedi. HTTP kodu: ' .
            $httpCode
        );
    }

    if (!file_exists($destination)) {

        throw new Exception(
            'ZIP dosyasi sunucuda olusturulamadi.'
        );
    }

    $size = @filesize($destination);

    if ($size === false || $size < 1000) {

        @unlink($destination);

        throw new Exception(
            'Indirilen ZIP dosyasi bos veya gecersiz.'
        );
    }

    return true;
}


/* ============================================================
   18. GITHUB ZIP'İNİ AÇ
   ============================================================ */

function extract_github_zip($zipFile, $destination)
{
    if (!class_exists('ZipArchive')) {

        throw new Exception(
            'Sunucuda ZipArchive eklentisi bulunamadi.'
        );
    }

    $zip = new ZipArchive();

    $openResult =
        $zip->open($zipFile);

    if ($openResult !== true) {

        throw new Exception(
            'GitHub ZIP dosyasi acilamadi. Kod: ' .
            $openResult
        );
    }

    ensure_directory($destination);

    $topFolder = null;

    /*
     * Önce ZIP yollarını kontrol et.
     */
    for ($i = 0; $i < $zip->numFiles; $i++) {

        $entry = $zip->getNameIndex($i);

        if ($entry === false) {
            continue;
        }

        $entry = str_replace('\\', '/', $entry);

        $entry = ltrim($entry, '/');

        if ($entry === '') {
            continue;
        }

        $safe = safe_relative_path($entry);

        $parts = explode('/', $safe);

        if (
            $topFolder === null &&
            count($parts) > 0
        ) {
            $topFolder = $parts[0];
        }

        /*
         * ZIP traversal kontrolü.
         */
        if (
            strpos($safe, '../') === 0 ||
            strpos($safe, '/../') !== false ||
            substr($safe, -3) === '/..'
        ) {

            $zip->close();

            throw new Exception(
                'Guvenli olmayan ZIP yolu: ' .
                $entry
            );
        }
    }

    if ($topFolder === null) {

        $zip->close();

        throw new Exception(
            'ZIP dosyasinda dosya bulunamadi.'
        );
    }

    /*
     * Sadece kontrollü şekilde çıkar.
     */
    for ($i = 0; $i < $zip->numFiles; $i++) {

        $entry = $zip->getNameIndex($i);

        if ($entry === false) {
            continue;
        }

        $entry = str_replace('\\', '/', $entry);

        $entry = ltrim($entry, '/');

        if ($entry === '') {
            continue;
        }

        $safe = safe_relative_path($entry);

        /*
         * Ana klasörü kaldır.
         */
        $prefix = $topFolder . '/';

        if (
            strpos($safe, $prefix) === 0
        ) {

            $relative =
                substr(
                    $safe,
                    strlen($prefix)
                );

        } else {

            /*
             * Ana klasör dışındaki girdileri atla.
             */
            continue;
        }

        if ($relative === '') {
            continue;
        }

        $target =
            $destination .
            DIRECTORY_SEPARATOR .
            str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relative
            );

        /*
         * Directory ise oluştur.
         */
        if (
            substr($entry, -1) === '/'
        ) {

            ensure_directory($target);

            continue;
        }

        /*
         * Klasörü oluştur.
         */
        $targetDir = dirname($target);

        ensure_directory($targetDir);

        /*
         * Stream ile çıkar.
         */
        $stream =
            $zip->getStream($entry);

        if ($stream === false) {

            $zip->close();

            throw new Exception(
                'ZIP dosyasi okunamadi: ' .
                $entry
            );
        }

        $out =
            @fopen($target, 'wb');

        if ($out === false) {

            fclose($stream);
            $zip->close();

            throw new Exception(
                'ZIP dosyasi gecici klasore yazilamadi: ' .
                $target
            );
        }

        while (!feof($stream)) {

            $buffer =
                fread(
                    $stream,
                    1024 * 1024
                );

            if ($buffer === false) {

                fclose($stream);
                fclose($out);
                $zip->close();

                throw new Exception(
                    'ZIP dosyasi okunurken hata olustu: ' .
                    $entry
                );
            }

            if ($buffer !== '') {

                if (
                    fwrite(
                        $out,
                        $buffer
                    ) === false
                ) {

                    fclose($stream);
                    fclose($out);
                    $zip->close();

                    throw new Exception(
                        'Gecici dosyaya yazilamadi: ' .
                        $target
                    );
                }
            }
        }

        fclose($stream);
        fclose($out);

        @chmod($target, 0644);
    }

    $zip->close();

    return true;
}


/* ============================================================
   19. DİZİNİ RECURSIVE TEMİZLE
   ============================================================ */

function remove_directory_recursive($directory)
{
    if (!file_exists($directory)) {
        return;
    }

    if (is_file($directory) || is_link($directory)) {

        @unlink($directory);

        return;
    }

    $items =
        @scandir($directory);

    if ($items === false) {
        return;
    }

    foreach ($items as $item) {

        if (
            $item === '.' ||
            $item === '..'
        ) {
            continue;
        }

        $path =
            $directory .
            DIRECTORY_SEPARATOR .
            $item;

        remove_directory_recursive($path);
    }

    @rmdir($directory);
}


/* ============================================================
   20. YEDEK OLUŞTUR
   ============================================================ */

function create_backup(
    $root,
    $backupZip,
    $files
) {
    if (!class_exists('ZipArchive')) {

        throw new Exception(
            'Yedekleme icin ZipArchive gerekli.'
        );
    }

    $zip = new ZipArchive();

    $result =
        $zip->open(
            $backupZip,
            ZipArchive::CREATE |
            ZipArchive::OVERWRITE
        );

    if ($result !== true) {

        throw new Exception(
            'Yedek ZIP dosyasi olusturulamadi.'
        );
    }

    foreach ($files as $relative) {

        $relative =
            str_replace(
                '\\',
                '/',
                $relative
            );

        $source =
            $root .
            DIRECTORY_SEPARATOR .
            str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relative
            );

        if (!is_file($source)) {
            continue;
        }

        /*
         * Yedek ZIP'ine ekle.
         */
        if (!$zip->addFile($source, $relative)) {

            $zip->close();

            throw new Exception(
                'Yedekleme basarisiz: ' .
                $relative
            );
        }
    }

    /*
     * Manifest ekle.
     */
    $manifest = json_encode(
        array(
            'created_at' => date('Y-m-d H:i:s'),
            'version' => isset($GLOBALS['versiyon'])
                ? $GLOBALS['versiyon']
                : '',
            'files' => array_values($files)
        ),
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE
    );

    $zip->addFromString(
        '__ZIRAATBOX_BACKUP_MANIFEST.json',
        $manifest
    );

    $zip->close();

    if (!file_exists($backupZip)) {

        throw new Exception(
            'Yedek ZIP dosyasi olusturulamadi.'
        );
    }

    return true;
}


/* ============================================================
   21. YEDEKTEN GERİ YÜKLE
   ============================================================ */

function restore_backup(
    $backupZip,
    $root
) {
    if (
        !file_exists($backupZip) ||
        !class_exists('ZipArchive')
    ) {
        return false;
    }

    $zip = new ZipArchive();

    if (
        $zip->open($backupZip) !== true
    ) {
        return false;
    }

    for (
        $i = 0;
        $i < $zip->numFiles;
        $i++
    ) {

        $entry =
            $zip->getNameIndex($i);

        if ($entry === false) {
            continue;
        }

        if (
            $entry ===
            '__ZIRAATBOX_BACKUP_MANIFEST.json'
        ) {
            continue;
        }

        $safe =
            safe_relative_path($entry);

        if (
            $safe === '' ||
            is_protected_path($safe)
        ) {
            continue;
        }

        $destination =
            $root .
            DIRECTORY_SEPARATOR .
            str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $safe
            );

        ensure_directory(
            dirname($destination)
        );

        $stream =
            $zip->getStream($entry);

        if ($stream === false) {
            continue;
        }

        $out =
            @fopen(
                $destination,
                'wb'
            );

        if ($out === false) {

            fclose($stream);

            continue;
        }

        while (!feof($stream)) {

            $buffer =
                fread(
                    $stream,
                    1024 * 1024
                );

            if ($buffer === false) {
                break;
            }

            if ($buffer !== '') {
                @fwrite(
                    $out,
                    $buffer
                );
            }
        }

        fclose($stream);
        fclose($out);

        @chmod(
            $destination,
            0644
        );
    }

    $zip->close();

    return true;
}


/* ============================================================
   22. GEÇİCİ DİZİNDEKİ DOSYALARI BUL
   ============================================================ */

function get_update_files(
    $directory,
    $base = ''
) {
    $files = array();

    $items =
        @scandir($directory);

    if ($items === false) {
        return $files;
    }

    foreach ($items as $item) {

        if (
            $item === '.' ||
            $item === '..'
        ) {
            continue;
        }

        $full =
            $directory .
            DIRECTORY_SEPARATOR .
            $item;

        $relative =
            $base === ''
                ? $item
                : $base . '/' . $item;

        if (is_dir($full)) {

            $sub =
                get_update_files(
                    $full,
                    $relative
                );

            foreach ($sub as $file) {
                $files[] = $file;
            }

        } elseif (is_file($full)) {

            $files[] =
                str_replace(
                    '\\',
                    '/',
                    $relative
                );
        }
    }

    return $files;
}


/* ============================================================
   23. GÜNCELLEME BAŞLIYOR
   ============================================================ */

try {

    update_log(
        'Guncelleme basladi. Versiyon: ' .
        $versiyon .
        ' Force: ' .
        ($force ? '1' : '0')
    );


    /* --------------------------------------------------------
       TEMP ZIP
       -------------------------------------------------------- */

    $tmpZip =
        @tempnam(
            sys_get_temp_dir(),
            'ziraatbox_update_'
        );

    if ($tmpZip === false) {

        update_error(
            'Sunucuda gecici ZIP dosyasi olusturulamadi.'
        );
    }

    update_log(
        'Gecici ZIP: ' .
        $tmpZip
    );


    /* --------------------------------------------------------
       GITHUB TAG İNDİR
       -------------------------------------------------------- */

    try {

        update_log(
            'GitHub tag indiriliyor: ' .
            $GITHUB_TAG_URL
        );

        download_github_zip(
            $GITHUB_TAG_URL,
            $tmpZip
        );

    } catch (Exception $tagError) {

        update_log(
            'Tag indirilemedi: ' .
            $tagError->getMessage()
        );

        /*
         * Force ile istenmişse main denenebilir.
         */
        if (!$force) {

            throw $tagError;
        }

        update_log(
            'Force guncelleme nedeniyle main branch deneniyor.'
        );

        @unlink($tmpZip);

        $tmpZip =
            @tempnam(
                sys_get_temp_dir(),
                'ziraatbox_update_'
            );

        if ($tmpZip === false) {

            update_error(
                'Main branch icin gecici ZIP olusturulamadi.'
            );
        }

        download_github_zip(
            $GITHUB_MAIN_URL,
            $tmpZip
        );
    }


    /* --------------------------------------------------------
       ZIP KONTROL
       -------------------------------------------------------- */

    if (!class_exists('ZipArchive')) {

        update_error(
            'Sunucuda ZipArchive eklentisi bulunamadi.'
        );
    }

    $zipCheck =
        new ZipArchive();

    if (
        $zipCheck->open($tmpZip) !== true
    ) {

        update_error(
            'Indirilen ZIP dosyasi acilamiyor.'
        );
    }

    if ($zipCheck->numFiles < 1) {

        $zipCheck->close();

        update_error(
            'ZIP dosyasi bos.'
        );
    }

    $zipCheck->close();


    /* --------------------------------------------------------
       GEÇİCİ KLASÖR
       -------------------------------------------------------- */

    $tmpDir =
        sys_get_temp_dir() .
        DIRECTORY_SEPARATOR .
        'ziraatbox_update_' .
        date('Ymd_His') .
        '_' .
        bin2hex(random_bytes(4));

    ensure_directory($tmpDir);

    update_log(
        'Gecici klasor: ' .
        $tmpDir
    );


    /* --------------------------------------------------------
       ZIP'İ AÇ
       -------------------------------------------------------- */

    extract_github_zip(
        $tmpZip,
        $tmpDir
    );

    update_log(
        'ZIP gecici klasore acildi.'
    );

    /* --------------------------------------------------------
       SÜRÜM DOSYASINI (versiyon.php) OTOMATİK GÜNCELLE
       -------------------------------------------------------- */
    $hedef_versiyon = $_GET['version'] ?? $version ?? '1.0.18';
    $v_dosya_yolu = dirname(__DIR__, 2) . "/sistem/versiyon.php";
    $v_icerik = "<?php\n/**\n * ZiraatBox - Otomatik Sürüm Dosyası\n */\ndefine('SISTEM_VERSIYON', '{$hedef_versiyon}');\n";
    file_put_contents($v_dosya_yolu, $v_icerik);

    update_log(
        "sistem/versiyon.php dosyasi v{$hedef_versiyon} olarak guncellendi."
    );

    /* --------------------------------------------------------
       GÜNCELLEME DOSYALARINI BUL
       -------------------------------------------------------- */

    $files =
        get_update_files(
            $tmpDir
        );

    if (count($files) === 0) {

        update_error(
            'Guncellenecek dosya bulunamadi.'
        );
    }


    /* --------------------------------------------------------
       KORUNANLARI ÇIKAR
       -------------------------------------------------------- */

    $filesToUpdate =
        array();

    foreach ($files as $relative) {

        $relative =
            str_replace(
                '\\',
                '/',
                $relative
            );

        if (
            is_protected_path($relative)
        ) {

            update_log(
                'Korundu: ' .
                $relative
            );

            continue;
        }

        $filesToUpdate[] =
            $relative;
    }

    if (count($filesToUpdate) === 0) {

        update_error(
            'Guncellenecek dosya bulunamadi.'
        );
    }


    /* --------------------------------------------------------
       MEVCUT DOSYALARI YEDEKLE
       -------------------------------------------------------- */

    $existingFiles =
        array();

    foreach ($filesToUpdate as $relative) {

        $target =
            $ROOT_DIR .
            DIRECTORY_SEPARATOR .
            str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relative
            );

        if (is_file($target)) {

            $existingFiles[] =
                $relative;
        }
    }


    $backupName =
        'backup_' .
        date('Ymd_His') .
        '_v' .
        str_replace(
            '.',
            '_',
            $versiyon
        ) .
        '.zip';

    $backupZip =
        $YEDEK_DIR .
        DIRECTORY_SEPARATOR .
        $backupName;


    create_backup(
        $ROOT_DIR,
        $backupZip,
        $existingFiles
    );

    update_log(
        'Yedek olusturuldu: ' .
        $backupZip
    );


    /* --------------------------------------------------------
       GÜNCELLEME
       -------------------------------------------------------- */

    foreach ($filesToUpdate as $relative) {

        $source =
            $tmpDir .
            DIRECTORY_SEPARATOR .
            str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relative
            );

        $destination =
            $ROOT_DIR .
            DIRECTORY_SEPARATOR .
            str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relative
            );

        /*
         * Path güvenliği.
         */
        $safe =
            safe_relative_path(
                $relative
            );

        if (
            is_protected_path($safe)
        ) {
            continue;
        }

        /*
         * Yeni dosya mı?
         */
        if (!file_exists($destination)) {

            $newFiles[] =
                $safe;
        }

        /*
         * Kopyala.
         */
        copy_update_file(
            $source,
            $destination
        );

        $updatedFiles[] =
            $safe;

        update_log(
            'Guncellendi: ' .
            $safe
        );
    }


    /* --------------------------------------------------------
       VERSİYON DOSYASI
       -------------------------------------------------------- */

    $versionFile =
        $ROOT_DIR .
        DIRECTORY_SEPARATOR .
        'sistem' .
        DIRECTORY_SEPARATOR .
        'versiyon.php';

    $versionDir =
        dirname($versionFile);

    ensure_directory($versionDir);


    $versionContent =
        "<?php\n" .
        "\n" .
        "\$surum = " .
        var_export(
            $versiyon,
            true
        ) .
        ";\n" .
        "\n" .
        "\$versiyon = \$surum;\n";


    /*
     * Versiyon dosyasını geçici olarak oluştur.
     */
    $versionTemp =
        $versionFile .
        '.tmp_' .
        bin2hex(random_bytes(5));

    if (
        @file_put_contents(
            $versionTemp,
            $versionContent,
            LOCK_EX
        ) === false
    ) {

        update_error(
            'Versiyon dosyasi yazilamadi: ' .
            $versionFile
        );
    }

    @chmod(
        $versionTemp,
        0644
    );

    if (
        !@rename(
            $versionTemp,
            $versionFile
        )
    ) {

        @unlink($versionTemp);

        update_error(
            'Versiyon dosyasi degistirilemedi: ' .
            $versionFile
        );
    }


    /* --------------------------------------------------------
       GÜNCELLEME BAŞARILI
       -------------------------------------------------------- */

    $success = true;

    update_log(
        'GUNCELLEME BASARILI. Versiyon: ' .
        $versiyon .
        '. Degisen dosya: ' .
        count($updatedFiles)
    );


    /* --------------------------------------------------------
       TEMP TEMİZLE
       -------------------------------------------------------- */

    if ($tmpZip !== false) {
        @unlink($tmpZip);
    }

    if ($tmpDir !== false) {
        remove_directory_recursive($tmpDir);
    }


    /* --------------------------------------------------------
       KİLİDİ BIRAK
       -------------------------------------------------------- */

    @flock(
        $lockHandle,
        LOCK_UN
    );

    @fclose(
        $lockHandle
    );

    @unlink(
        $LOCK_FILE
    );


    /* --------------------------------------------------------
       BAŞARI YÖNLENDİRME
       -------------------------------------------------------- */

    $redirect =
        '../guncelleme.php?durum=basarili&version=' .
        urlencode($versiyon);

    
// 🚀 OTOMATİK VERİTABANI MİGRASYONU
$sql_dosyasi = __DIR__ . "/../../sistem/guncelleme.sql";
if (file_exists($sql_dosyasi)) {
    $sql_icerik = file_get_contents($sql_dosyasi);
    if (!empty(trim($sql_icerik))) {
        try { $db->exec($sql_icerik); } catch (PDOException $e) { error_log("SQL Error: " . $e->getMessage()); }
    }
}

header(
        'Location: ' .
        $redirect
    );

    exit;


} catch (Throwable $e) {

    /*
     * Hata logu.
     */
    update_log(
        'GUNCELLEME BASARISIZ: ' .
        $e->getMessage()
    );


    /* --------------------------------------------------------
       ROLLBACK
       -------------------------------------------------------- */

    if (
        !$success &&
        $backupZip !== false &&
        file_exists($backupZip)
    ) {

        update_log(
            'Rollback baslatiliyor.'
        );

        try {

            $rollbackResult =
                restore_backup(
                    $backupZip,
                    $ROOT_DIR
                );

            if ($rollbackResult) {

                update_log(
                    'Rollback tamamlandi.'
                );

            } else {

                update_log(
                    'Rollback basarisiz.'
                );
            }

        } catch (Throwable $rollbackError) {

            update_log(
                'Rollback hatasi: ' .
                $rollbackError->getMessage()
            );
        }
    }


    /* --------------------------------------------------------
       TEMP TEMİZLE
       -------------------------------------------------------- */

    if ($tmpZip !== false) {
        @unlink($tmpZip);
    }

    if ($tmpDir !== false) {
        remove_directory_recursive($tmpDir);
    }


    /* --------------------------------------------------------
       KİLİDİ BIRAK
       -------------------------------------------------------- */

    if (
        isset($lockHandle) &&
        is_resource($lockHandle)
    ) {

        @flock(
            $lockHandle,
            LOCK_UN
        );

        @fclose(
            $lockHandle
        );
    }

    @unlink(
        $LOCK_FILE
    );


    /* --------------------------------------------------------
       KULLANICIYA HATA
       -------------------------------------------------------- */

    $errorMessage =
        $e->getMessage();

    http_response_code(500);

    echo '<!DOCTYPE html>';
    echo '<html lang="tr">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Güncelleme Hatası</title>';

    echo '<style>';
    echo 'body{';
    echo 'font-family:Arial,sans-serif;';
    echo 'background:#f5f6f8;';
    echo 'padding:40px;';
    echo '}';

    echo '.box{';
    echo 'max-width:800px;';
    echo 'margin:auto;';
    echo 'background:#fff;';
    echo 'border-radius:12px;';
    echo 'padding:30px;';
    echo 'box-shadow:0 5px 25px rgba(0,0,0,.08);';
    echo '}';

    echo '.error{';
    echo 'color:#b42318;';
    echo 'font-size:20px;';
    echo 'font-weight:bold;';
    echo 'margin-bottom:15px;';
    echo '}';

    echo '.detail{';
    echo 'background:#f8f8f8;';
    echo 'border:1px solid #ddd;';
    echo 'border-radius:8px;';
    echo 'padding:15px;';
    echo 'font-family:monospace;';
    echo 'white-space:pre-wrap;';
    echo 'word-break:break-word;';
    echo '}';

    echo '.back{';
    echo 'display:inline-block;';
    echo 'margin-top:20px;';
    echo 'padding:10px 18px;';
    echo 'background:#222;';
    echo 'color:#fff;';
    echo 'text-decoration:none;';
    echo 'border-radius:7px;';
    echo '}';
    echo '</style>';

    echo '</head>';
    echo '<body>';

    echo '<div class="box">';

    echo '<div class="error">';
    echo '❌ Güncelleme Hatası';
    echo '</div>';

    echo '<div class="detail">';
    echo htmlspecialchars(
        $errorMessage,
        ENT_QUOTES,
        'UTF-8'
    );
    echo '</div>';

    echo '<a class="back" href="../guncelleme.php">';
    echo '← Güncelleme Sayfasına Dön';
    echo '</a>';

    echo '</div>';

    echo '</body>';
    echo '</html>';

    exit;
}
