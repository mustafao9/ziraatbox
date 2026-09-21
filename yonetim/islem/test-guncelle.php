<?php
/**
 * ZiraatBox - Güncelleme Hata Yakalama Test Dosyası
 * Dosya Yolu: public_html/yonetim/islem/test-guncelle.php
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "1. PHP Çalışıyor...<br>";

// Ayar dosyasını kontrol et
$ayar_yolu = __DIR__ . "/../../sistem/ayar.php";
if (file_exists($ayar_yolu)) {
    echo "2. ayar.php bulundu, dahil ediliyor...<br>";
    require_once $ayar_yolu;
    echo "3. ayar.php dahil edildi.<br>";
} else {
    die("❌ HATA: ayar.php bulunamadı! Aranan Yol: " . $ayar_yolu);
}

// Session Kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "4. Session aktif. Admin yetkisi: " . ($_SESSION['yetki'] ?? 'Tanımsız') . "<br>";

// ZipArchive Kontrolü
if (class_exists('ZipArchive')) {
    echo "5. ZipArchive eklentisi aktif.<br>";
} else {
    echo "❌ HATA: ZipArchive eklentisi sunucuda kapalı!<br>";
}

// cURL Kontrolü
if (function_exists('curl_init')) {
    echo "6. cURL eklentisi aktif.<br>";
} else {
    echo "❌ HATA: cURL eklentisi bulunamadı!<br>";
}

echo "<b>✅ Temel testler tamamlandı. Eğer bu yazıları görüyorsanız 500 hatası sistem bağımlılıklarından değil, mantık akışından kaynaklanıyor demektir.</b>";