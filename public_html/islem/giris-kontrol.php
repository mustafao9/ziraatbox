<?php
/**
 * ZiraatBox - Giriş Kontrol Motoru v7.1 (.env & Güvenlik Güncellemesi)
 * Mustafa Satılmış - İncirliova / Aydın
 */
ob_start();
require_once __DIR__ . "/../sistem/ayar.php";

$app_url = function_exists('env') ? env('APP_URL', 'http://ziraatbox.com') : 'http://ziraatbox.com';

if (isset($_POST['giris_yap'])) {
    
    $giris_bilgisi = trim($_POST['giris_bilgisi'] ?? '');
    $giris_sifre   = $_POST['giris_sifre'] ?? '';
    $captcha       = $_POST['g-recaptcha-response'] ?? '';

    // 1. RECAPTCHA DOĞRULAMA (.env / cURL Tabanlı)
    $recaptcha_secret = function_exists('env') ? env('RECAPTCHA_SECRET_KEY') : (defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : '');

    if (empty($captcha)) {
        $_SESSION['hata'] = "Lütfen robot olmadığınızı doğrulayın.";
        header("Location: " . $app_url . "/giris.php"); 
        exit;
    }

    if (!empty($recaptcha_secret)) {
        $ch = curl_init("https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret'   => $recaptcha_secret,
            'response' => $captcha,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $verify_response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($verify_response);

        if (!$response || !$response->success) {
            $_SESSION['hata'] = "Güvenlik doğrulaması başarısız.";
            header("Location: " . $app_url . "/giris.php"); 
            exit;
        }
    }

    try {
        // 2. VERİTABANI SORGUSU (PDO Prepared Statement - eposta / email & telefon)
        $sorgu = $db->prepare("SELECT * FROM uyeler WHERE (email = ? OR eposta = ? OR telefon = ?) LIMIT 1");
        $sorgu->execute([$giris_bilgisi, $giris_bilgisi, $giris_bilgisi]);
        $uye = $sorgu->fetch();

        // 3. ŞİFRE DOĞRULAMA & OTURUM GÜVENLİĞİ
        if ($uye && password_verify($giris_sifre, $uye['sifre'])) {
            
            // Session Hijacking / Fixation Koruması
            session_regenerate_id(true);

            $_SESSION['uye_id']   = $uye['id'];
            $_SESSION['ad_soyad'] = $uye['ad_soyad'];
            $_SESSION['yetki']    = $uye['yetki'] ?? 'uye';

            header("Location: " . $app_url . "/index.php"); 
            exit;

        } else {
            $_SESSION['hata'] = "Giriş bilgileri veya şifre hatalı.";
            header("Location: " . $app_url . "/giris.php"); 
            exit;
        }

    } catch (PDOException $e) {
        if (function_exists('env') && env('APP_DEBUG') === 'true') {
            $_SESSION['hata'] = "Teknik Hata: " . $e->getMessage();
        } else {
            $_SESSION['hata'] = "Giriş yapılırken teknik bir hata oluştu.";
        }
        
        header("Location: " . $app_url . "/giris.php"); 
        exit;
    }

} else {
    header("Location: " . $app_url . "/index.php"); 
    exit;
}
ob_end_flush();