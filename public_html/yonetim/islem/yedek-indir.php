<?php
/**
 * ZiraatBox - Güvenli Yedek İndirme Motoru
 * Dosya Yolu: /home/ziraatbo/public_html/yonetim/islem/yedek-indir.php
 */
require_once "../../sistem/ayar.php";

// 🔐 GÜVENLİK KONTROLÜ: Sadece admin indirebilir
if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    die("Yetkisiz erişim!");
}

if (isset($_GET['dosya'])) {
    // 🛡️ Güvenlik: Dosya yolunda manipülasyonu engelle (../ gibi dizin atlamaları temizle)
    $dosya_adi = basename($_GET['dosya']); 
    $yedek_dizini = dirname(__DIR__) . "/yedekler/";
    $tam_yol = $yedek_dizini . $dosya_adi;

    // Dosya gerçekten var mı kontrol et
    if (file_exists($tam_yol)) {
        
        // 🚀 Tarayıcıya dosya indirme başlıklarını gönder
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $dosya_adi . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($tam_yol));
        
        // Belleği yormadan dosyayı oku ve gönder
        readfile($tam_yol);
        exit;
        
    } else {
        die("Hata: Aradığınız yedek dosyası sunucuda bulunamadı.");
    }
} else {
    die("Hata: Geçersiz dosya isteği.");
}