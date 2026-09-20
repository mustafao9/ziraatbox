<?php
/**
 * ZiraatBox Otomatik Kurulum Betiği
 */

// 1. Oluşturulacak Klasörler
$klasorler = [
    'sistem',
    'parcalar',
    'yuklemeler',
    'yuklemeler/ilanlar',
    'yuklemeler/profil',
    'islem',
    'dosyalar',
    'dosyalar/css',
    'dosyalar/js',
    'dosyalar/resim',
    'yonetim',
    'yonetim/islem'
];

// 2. Oluşturulacak Dosyalar
$dosyalar = [
    'index.php',
    '.htaccess',
    '404.php',
    'robots.txt',
    'ilanlar.php',
    'ilan-detay.php',
    'kategoriler.php',
    'arama.php',
    'giris.php',
    'kayit.php',
    'sifremi-unuttum.php',
    'cik-yap.php',
    'hesabim.php',
    'ilanlarim.php',
    'ilan-ver.php',
    'ilan-duzenle.php',
    'mesajlarim.php',
    'mesaj-detay.php',
    'favorilerim.php',
    'ayarlarim.php',
    'hakkimizda.php',
    'iletisim.php',
    'kvkk.php',
    'kullanim-kosullari.php',
    'yardim.php',
    'sistem/ayar.php',
    'sistem/fonksiyonlar.php',
    'sistem/.htaccess',
    'parcalar/ust.php',
    'parcalar/alt.php',
    'islem/giris-kontrol.php',
    'islem/kayit-kontrol.php',
    'islem/ilan-ekle.php',
    'islem/profil-guncelle.php',
    'islem/mesaj-gonder.php',
    'yonetim/index.php',
    'yonetim/ilanlar.php',
    'yonetim/uyeler.php',
    'yonetim/kategoriler.php',
    'yonetim/mesajlar.php',
    'yonetim/sikayetler.php',
    'yonetim/ayarlar.php',
    'yonetim/giris.php',
    'yonetim/cikis.php',
    'yonetim/islem/ilan-onay.php',
    'yonetim/islem/uye-durum.php',
    'yonetim/islem/ayar-kaydet.php',
    'dosyalar/css/stil.css',
    'dosyalar/css/admin.css',
    'dosyalar/js/script.js'
];

echo "<h2>ZiraatBox Kurulumu Başladı...</h2>";

// Klasörleri Oluştur
foreach ($klasorler as $klasor) {
    if (!file_exists($klasor)) {
        mkdir($klasor, 0777, true);
        echo "Klasör Oluşturuldu: $klasor <br>";
    }
}

// Dosyaları Oluştur
foreach ($dosyalar as $dosya) {
    if (!file_exists($dosya)) {
        touch($dosya);
        echo "Dosya Oluşturuldu: $dosya <br>";
    }
}

// Güvenlik İçin sistem/.htaccess içeriğini doldur
$htaccess_icerik = "Deny from all";
file_put_contents('sistem/.htaccess', $htaccess_icerik);

echo "<h3>Kurulum Tamamlandı! Lütfen yukle.php dosyasını silin.</h3>";
?>