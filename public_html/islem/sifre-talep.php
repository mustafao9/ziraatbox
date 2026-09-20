<?php
/**
 * ZiraatBox - Şifre Sıfırlama Talep İşlem Motoru
 * SQL Dökümüne Tam Uyumlu Versiyon
 */
require_once "../sistem/ayar.php";
require_once "../sistem/mail-motoru.php"; 

if (isset($_POST['sifre_sifirla_talep'])) {
    
    // Güvenlik: Gelen veriyi g() fonksiyonu ile temizleyelim
    $eposta_gelen = isset($_POST['eposta']) ? g($_POST['eposta']) : '';

    if (empty($eposta_gelen)) {
        header("Location: ../sifremi-unuttum.php?durum=bos");
        exit;
    }

    // 1. ADIM: Veritabanı dökümündeki 'email' sütununa göre sorgu yapıyoruz
    $sorgu = $db->prepare("SELECT id, ad_soyad FROM uyeler WHERE email = ? AND durum = 'aktif' LIMIT 1");
    $sorgu->execute([$eposta_gelen]);
    $uye = $sorgu->fetch();

    if ($uye) {
        // 2. ADIM: Güvenli ve benzersiz bir TOKEN üretelim
        $token = bin2hex(random_bytes(32));
        $son_kullanma = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // 3. ADIM: Eski bekleyen talepleri iptal et
        $db->prepare("UPDATE sifre_sifirlama SET durum = 'iptal' WHERE uye_id = ? AND durum = 'beklemede'")->execute([$uye['id']]);

        // Yeni talebi kaydet
        $ekle = $db->prepare("INSERT INTO sifre_sifirlama SET uye_id = ?, token = ?, son_kullanma = ?, durum = 'beklemede'");
        $islem = $ekle->execute([$uye['id'], $token, $son_kullanma]);

        if ($islem) {
            // 4. ADIM: Dinamik Link Oluşturma
            // ayar.php içinde define('URL', '...') varsa onu kullanır, yoksa manuel link oluşturur.
            $site_url = defined('URL') ? URL : 'https://ziraatbox.com';
            $sifre_link = $site_url . "/sifre-sifirla.php?token=" . $token;
            
            // Maili Gönder
            $mail_durum = ZiraatMailGonder($eposta_gelen, "ZiraatBox - Şifre Sıfırlama Talebi", "SIFRE_SIFIRLA", [
                'ad'    => $uye['ad_soyad'],
                'link'  => $sifre_link
            ]);

            if ($mail_durum) {
                header("Location: ../sifremi-unuttum.php?durum=ok");
            } else {
                header("Location: ../sifremi-unuttum.php?durum=mail_hata");
            }
        } else {
            header("Location: ../sifremi-unuttum.php?durum=sistem_hata");
        }
    } else {
        // E-posta bulunamadığında güvenlik için "yok" durumuna gönderiyoruz
        header("Location: ../sifremi-unuttum.php?durum=yok");
    }
    exit;

} else {
    header("Location: ../index.php");
    exit;
}