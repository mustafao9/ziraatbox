<?php
/**
 * ZiraatBox - Otomatik Yedekleme Ayar Kayıt Motoru
 * Dosya Yolu: /home/ziraatbo/public_html/yonetim/islem/yedek-ayar-kaydet.php
 */
require_once "../../sistem/ayar.php";

// 🔐 YETKİ KONTROLÜ
if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    die("Yetkisiz erişim!");
}

if ($_POST) {
    // Formdan gelen verileri al
    $siklik  = htmlspecialchars($_POST['yedek_siklik']);
    $icerik  = htmlspecialchars($_POST['yedek_icerik']);
    $tarih   = date("Y-m-d H:i:s");

    // 📂 Ayarları kaydedeceğimiz PHP dosyasının içeriğini hazırlayalım
    // Bu dosya ileride Cron Job tarafından okunacak
    $config_icerik = "<?php\n";
    $config_icerik .= "// ZiraatBox Otomatik Yedekleme Yapılandırması\n";
    $config_icerik .= "// Son Güncelleme: $tarih\n\n";
    $config_icerik .= "define('YEDEK_SIKLIK', '$siklik');\n";
    $config_icerik .= "define('YEDEK_ICERIK', '$icerik');\n";
    $config_icerik .= "?>";

    // Ayar dosyasını sistem klasörüne yazalım
    $ayar_dosya_yolu = "../../sistem/yedek_config.php";
    
    if (file_put_contents($ayar_dosya_yolu, $config_icerik)) {
        // Başarılıysa yedekleme sayfasına geri dön
        header("Location: ../yedekleme.php?islem=ok&mesaj=ayar_kaydedildi");
        exit;
    } else {
        // Yazma hatası oluşursa (Klasör izni vb.)
        header("Location: ../yedekleme.php?islem=hata&kod=yazma_izni");
        exit;
    }
} else {
    header("Location: ../yedekleme.php");
    exit;
}