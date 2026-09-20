<?php
/**
 * ZiraatBox - İlan Ekleme Motoru v3.0 (.env & Güvenlik Güncellemesi)
 * Mustafa Satılmış - İncirliova / Aydın
 */

ob_start();
require_once __DIR__ . "/../sistem/ayar.php";

$app_url = function_exists('env') ? env('APP_URL', 'http://ziraatbox.com') : 'http://ziraatbox.com';

/**
 * 🔗 MODERN SEO URL FONKSİYONU
 */
if (!function_exists('seolink')) {
    function seolink($val) {
        $yol = array('Ç', 'Ş', 'Ğ', 'Ü', 'İ', 'Ö', 'ç', 'ş', 'ğ', 'ü', 'ö', 'ı', '+', '#', '.');
        $yap = array('c', 's', 'g', 'u', 'i', 'o', 'c', 's', 'g', 'u', 'o', 'i', 'plus', 'sharp', '-');
        $val = str_replace($yol, $yap, $val);
        $val = mb_strtolower($val, 'UTF-8');
        $val = preg_replace('#[^-a-zA-Z0-9_ ]#', '', $val);
        $val = trim($val);
        $val = str_replace(' ', '-', $val);
        while (strpos($val, '--') !== false) { $val = str_replace('--', '-', $val); }
        return $val;
    }
}

/**
 * ⚖️ GERÇEK IP YAKALAMA FONKSİYONU (5651 Uyumlu)
 */
if (!function_exists('getRealIP')) {
    function getRealIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}

// 🔐 OTURUM KONTROLÜ
if (!isset($_SESSION['uye_id'])) { 
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        die(json_encode(['durum' => 'hata', 'mesaj' => 'Yetkisiz erişim!'])); 
    } else {
        header("Location: " . $app_url . "/giris.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. FORM VERİLERİNİ GÜVENLİCE ALALIM
    $uye_id      = (int)$_SESSION['uye_id'];
    $baslik      = trim($_POST['baslik'] ?? '');
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $satici_tipi = trim($_POST['satici_tipi'] ?? 'Uretici');
    
    // Fiyat Temizleme (1.500,00 -> 1500.00)
    $fiyat_ham   = $_POST['fiyat'] ?? '0';
    $fiyat       = (float)str_replace(['.', ','], ['', '.'], $fiyat_ham); 
    
    $il_id       = (int)($_POST['il_id'] ?? 0);
    $ilce_id     = (int)($_POST['ilce_id'] ?? 0);
    $mahalle_id  = (int)($_POST['mahalle_id'] ?? 0);
    $aciklama    = trim($_POST['aciklama'] ?? '');
    
    // 🚀 GİZLİLİK VE İLETİŞİM AYARLARI
    $iletisim_tercihi = trim($_POST['iletisim_tercihi'] ?? 'hepsi');
    $isim_gizle       = isset($_POST['isim_gizle']) ? 1 : 0;
    
    // ⚖️ LOG BİLGİLERİ (5651 Yer Sağlayıcı Sorumluluğu)
    $ip_adresi  = getRealIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Bilinmiyor';

    if (empty($baslik) || $kategori_id <= 0) {
        $_SESSION['mesaj'] = "Lütfen ilan başlığını ve kategorisini seçiniz.";
        header("Location: " . $app_url . "/ilan-ver.php");
        exit;
    }

    // SEO URL & Akıllı İlan No
    $slug = seolink($baslik) . "-" . time() . rand(10, 99);

    $yil          = date('y');
    $yilun_gunu   = str_pad(date('z') + 1, 3, '0', STR_PAD_LEFT); 
    $saat_dakika  = date('Hi');             
    $saniye       = date('s');              
    $ozel_ilan_no = $yil . $yilun_gunu . $saat_dakika . $saniye;

    try {
        // 2. VERİTABANINA ANA KAYIT (PDO Prepared Statement)
        $ilan_ekle = $db->prepare("INSERT INTO ilanlar SET 
            ilan_no          = ?, 
            uye_id           = ?, 
            kategori_id      = ?, 
            satici_tipi      = ?, 
            baslik           = ?, 
            slug             = ?, 
            aciklama         = ?, 
            fiyat            = ?, 
            il               = ?, 
            ilce             = ?, 
            mahalle          = ?, 
            iletisim_tercihi = ?, 
            isim_gizle       = ?, 
            ip_adresi        = ?, 
            user_agent       = ?, 
            durum            = 'beklemede'");

        $kontrol = $ilan_ekle->execute([
            $ozel_ilan_no, $uye_id, $kategori_id, $satici_tipi, $baslik, $slug, $aciklama, $fiyat, 
            $il_id, $ilce_id, $mahalle_id, $iletisim_tercihi, $isim_gizle, $ip_adresi, $user_agent
        ]);

        if ($kontrol) {
            $last_id = $db->lastInsertId();

            // 🛠️ TEKNİK ÖZELLİKLERİ KAYDET
            if (isset($_POST['ozellik']) && is_array($_POST['ozellik'])) {
                $oz_kaydet = $db->prepare("INSERT INTO ilan_ozellik_verileri (ilan_id, ozellik_id, deger) VALUES (?, ?, ?)");
                foreach ($_POST['ozellik'] as $oz_id => $deger) {
                    if (trim($deger) !== '') {
                        $oz_kaydet->execute([$last_id, (int)$oz_id, trim($deger)]);
                    }
                }
            }

            // 📸 RESİMLERİ GÜVENLİ YÜKLE
            if (isset($_FILES['ilan_resimleri']['name'][0]) && !empty($_FILES['ilan_resimleri']['name'][0])) {
                $hedef_yol = __DIR__ . "/../yuklemeler/ilanlar/";
                if (!file_exists($hedef_yol)) { 
                    @mkdir($hedef_yol, 0755, true); 
                }

                $gecerli_uzantilar = ['jpg', 'jpeg', 'png', 'webp'];
                $gecerli_mime_tipleri = ['image/jpeg', 'image/png', 'image/webp'];

                foreach ($_FILES['ilan_resimleri']['name'] as $i => $ad) {
                    if ($_FILES['ilan_resimleri']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmp_yol = $_FILES['ilan_resimleri']['tmp_name'][$i];
                        $uzanti  = strtolower(pathinfo($ad, PATHINFO_EXTENSION));

                        // MIME Tipi Kontrolü (Güvenlik)
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $tmp_yol);
                        finfo_close($finfo);

                        if (in_array($uzanti, $gecerli_uzantilar) && in_array($mime_type, $gecerli_mime_tipleri)) {
                            $yeni_ad = "zbox_" . uniqid() . "_" . $i . "." . $uzanti;
                            if (move_uploaded_file($tmp_yol, $hedef_yol . $yeni_ad)) {
                                $ana_resim = ($i == 0) ? 1 : 0;
                                $db->prepare("INSERT INTO ilan_resimleri (ilan_id, dosya_adi, ana_resim) VALUES (?, ?, ?)")
                                   ->execute([$last_id, $yeni_ad, $ana_resim]);
                            }
                        }
                    }
                }
            }

            header("Location: " . $app_url . "/ilanlarim.php?islem=basarili&onay=bekliyor");
            exit;
        } else {
            $_SESSION['mesaj'] = "İlan eklenirken veritabanı hatası oluştu.";
            header("Location: " . $app_url . "/ilan-ver.php");
            exit;
        }
    } catch (PDOException $e) {
        if (function_exists('env') && env('APP_DEBUG') === 'true') {
            die("İlan Ekleme Hatası: " . $e->getMessage());
        } else {
            $_SESSION['mesaj'] = "Sistemde teknik bir hata oluştu. Lütfen tekrar deneyiniz.";
            header("Location: " . $app_url . "/ilan-ver.php");
            exit;
        }
    }
} else {
    header("Location: " . $app_url . "/index.php");
    exit;
}
ob_end_flush();