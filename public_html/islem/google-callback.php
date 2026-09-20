<?php
/**
 * ZiraatBox - Google OAuth Callback Motoru (Sürüm Uyumlu Temiz Kod)
 * Mustafa Satılmış - İncirliova / Aydın
 */

ob_start();
require_once __DIR__ . "/../sistem/ayar.php";
require_once __DIR__ . "/google-ayarlar.php"; 

$app_url = defined('URL') ? URL : 'https://ziraatbox.com';

if (isset($_GET['code'])) {
    try {
        // 1. Kütüphane Sürümüne Uyumlu Token Alma
        $token = $google_client->authenticate($_GET['code']);
        
        if (is_array($token) && isset($token['error'])) {
            throw new Exception("Google doğrulama hatası: " . ($token['error_description'] ?? $token['error']));
        }
        
        if (is_array($token)) {
            $google_client->setAccessToken($token);
        }

        // 2. Profil Bilgilerini Çekme
        $google_oauth = new Google_Service_Oauth2($google_client);
        $user_info    = $google_oauth->userinfo->get();
        
        $email    = filter_var($user_info->email, FILTER_VALIDATE_EMAIL);
        $ad_soyad = strip_tags($user_info->name);
        $g_id     = $user_info->id;

        if (!$email) {
            $_SESSION['hata'] = "Google hesabınızdan e-posta adresi alınamadı.";
            header("Location: " . $app_url . "/giris.php");
            exit;
        }

        // 3. Veritabanı Kontrolü (Sadece 'email' Sütunu)
        $sorgu = $db->prepare("SELECT * FROM uyeler WHERE email = ? LIMIT 1");
        $sorgu->execute([$email]);
        $uye = $sorgu->fetch(PDO::FETCH_ASSOC);

        if ($uye) {
            // Mevcut Kullanıcı Oturumu
            session_regenerate_id(true);
            $_SESSION['uye_id']   = $uye['id'];
            $_SESSION['ad_soyad'] = !empty($uye['ad_soyad']) ? $uye['ad_soyad'] : $ad_soyad;
            $_SESSION['yetki']    = $uye['yetki'] ?? 'uye';
        } else {
            // Yeni Kullanıcı Kaydı
            $kullanici_adi = explode('@', $email)[0] . rand(100, 999);
            
            $kaydet = $db->prepare("INSERT INTO uyeler (kullanici_adi, email, ad_soyad, google_id, durum) VALUES (?, ?, ?, ?, 'aktif')");
            $kaydet->execute([$kullanici_adi, $email, $ad_soyad, $g_id]);

            $yeni_id = $db->lastInsertId();

            session_regenerate_id(true);
            $_SESSION['uye_id']   = $yeni_id;
            $_SESSION['ad_soyad'] = $ad_soyad;
            $_SESSION['yetki']    = 'uye';
        }

        // Başarılı Giriş -> Ana Sayfa
        header("Location: " . $app_url . "/index.php");
        exit;

    } catch (Exception $e) {
        $_SESSION['hata'] = "Google oturumu doğrulanamadı. Lütfen tekrar deneyin.";
        header("Location: " . $app_url . "/giris.php");
        exit;
    }
} else {
    header("Location: " . $app_url . "/giris.php");
    exit;
}
ob_end_flush();