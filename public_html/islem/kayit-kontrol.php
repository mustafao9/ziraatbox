<?php
/**
 * ZiraatBox - Kayıt Kontrol Motoru v3.0 (.env & Güvenlik Güncellemesi)
 * Mustafa Satılmış - İncirliova / Aydın
 */

ob_start();
require_once __DIR__ . "/../sistem/ayar.php";

$app_url = function_exists('env') ? env('APP_URL', 'http://ziraatbox.com') : 'http://ziraatbox.com';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. RECAPTCHA KONTROLÜ (cURL & .env Destekli)
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    if (!empty($recaptcha_response)) {
        $secret = function_exists('env') ? env('RECAPTCHA_SECRET_KEY') : (defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : '');

        if (!empty($secret)) {
            $ch = curl_init("https://www.google.com/recaptcha/api/siteverify");
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'secret'   => $secret,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            $verify_response = curl_exec($ch);
            curl_close($ch);

            $responseData = json_decode($verify_response);

            if (!$responseData || !$responseData->success) {
                $_SESSION['mesaj'] = "Lütfen robot olmadığınızı doğrulayın.";
                header("Location: " . $app_url . "/kayit.php");
                exit;
            }
        }
    } else {
        $_SESSION['mesaj'] = "Lütfen güvenlik doğrulamasını tamamlayın.";
        header("Location: " . $app_url . "/kayit.php");
        exit;
    }

    // 2. VERİLERİ AL VE FİLTRELE
    $ad_soyad     = strip_tags(trim($_POST['ad_soyad'] ?? ''));
    $eposta       = filter_var(trim($_POST['eposta'] ?? ''), FILTER_VALIDATE_EMAIL);
    $telefon      = preg_replace('/[^0-9]/', '', trim($_POST['telefon'] ?? ''));
    $sifre        = $_POST['sifre'] ?? '';
    $sifre_tekrar = $_POST['sifre_tekrar'] ?? '';

    // 3. BOŞ ALAN VE ŞİFRE DOĞRULAMA
    if (empty($ad_soyad) || !$eposta || empty($sifre)) {
        $_SESSION['mesaj'] = "Lütfen geçerli bir e-posta adresi ve zorunlu alanları doldurun.";
        header("Location: " . $app_url . "/kayit.php");
        exit;
    }

    if ($sifre !== $sifre_tekrar) {
        $_SESSION['mesaj'] = "Girdiğiniz şifreler birbiriyle eşleşmiyor.";
        header("Location: " . $app_url . "/kayit.php");
        exit;
    }

    if (strlen($sifre) < 6) {
        $_SESSION['mesaj'] = "Şifreniz en az 6 karakter olmalıdır.";
        header("Location: " . $app_url . "/kayit.php");
        exit;
    }

    try {
        // 4. MÜKERRER E-POSTA KONTROLÜ (eposta / email Çift Sütun Uyumlu)
        $kontrol = $db->prepare("SELECT id FROM uyeler WHERE eposta = ? OR email = ? LIMIT 1");
        $kontrol->execute([$eposta, $eposta]);
        if ($kontrol->fetch()) {
            $_SESSION['mesaj'] = "Bu e-posta adresi zaten sisteme kayıtlı.";
            header("Location: " . $app_url . "/kayit.php");
            exit;
        }

        // 5. KAYIT İŞLEMİ
        $sifre_hash = password_hash($sifre, PASSWORD_BCRYPT);
        $kullanici_adi = explode('@', $eposta)[0] . rand(100, 999); 

        $ekle = $db->prepare("INSERT INTO uyeler (kullanici_adi, eposta, email, ad_soyad, telefon, sifre, yetki, durum) 
                              VALUES (?, ?, ?, ?, ?, ?, 'uye', 'aktif')");
        
        $sonuc = $ekle->execute([
            $kullanici_adi, 
            $eposta, 
            $eposta,
            $ad_soyad, 
            $telefon, 
            $sifre_hash
        ]);

        if ($sonuc) {
            // Session Hijacking / Fixation Koruması
            session_regenerate_id(true);

            $yeni_id = $db->lastInsertId();
            $_SESSION['uye_id']   = $yeni_id;
            $_SESSION['ad_soyad'] = $ad_soyad;
            $_SESSION['yetki']    = 'uye';

            $_SESSION['mesaj'] = "ZiraatBox ailesine hoş geldiniz! Kaydınız başarıyla tamamlandı.";
            header("Location: " . $app_url . "/hesabim.php");
            exit;
        } else {
            $_SESSION['mesaj'] = "Teknik bir sorun oluştu, lütfen tekrar deneyin.";
            header("Location: " . $app_url . "/kayit.php");
            exit;
        }

    } catch (PDOException $e) {
        if (function_exists('env') && env('APP_DEBUG') === 'true') {
            $_SESSION['mesaj'] = "Sistem Hatası: " . $e->getMessage();
        } else {
            $_SESSION['mesaj'] = "Kayıt sırasında teknik bir hata oluştu. Lütfen tekrar deneyiniz.";
        }
        header("Location: " . $app_url . "/kayit.php");
        exit;
    }
} else {
    header("Location: " . $app_url . "/index.php");
    exit;
}
ob_end_flush();