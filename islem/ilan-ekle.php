<?php
/**
 * ZiraatBox - İlan Ekleme Motoru v4.2 (WebP, Thumbnail & SQL Şema Onarımlı)
 * Mustafa Satılmış - İncirliova / Aydın
 */

ob_start();
require_once __DIR__ . "/../sistem/ayar.php";

$app_url = defined('URL') ? URL : (function_exists('env') ? env('APP_URL', 'http://localhost/ziraatbox/public_html') : 'http://localhost/ziraatbox/public_html');

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

/**
 * 🖼️ WEBP & THUMBNAIL DÖNÜŞTÜRÜCÜ YARDIMCI FONKSİYONLAR
 */
if (!function_exists('resimWebpIslem')) {
    function resimWebpIslem($tmpName, $hedefKlasor, $maxGenislik = 1200, $kalite = 80) {
        if (!file_exists($tmpName)) return false;
        
        $imageInfo = @getimagesize($tmpName);
        if (!$imageInfo) return false;

        $mime = $imageInfo['mime'] ?? '';
        switch ($mime) {
            case 'image/jpeg': $srcImage = @imagecreatefromjpeg($tmpName); break;
            case 'image/png':  $srcImage = @imagecreatefrompng($tmpName); break;
            case 'image/webp': $srcImage = @imagecreatefromwebp($tmpName); break;
            default: return false;
        }

        if (!$srcImage) return false;

        $genislik = $imageInfo[0];
        $yukseklik = $imageInfo[1];

        if ($genislik > $maxGenislik) {
            $yeniGenislik = $maxGenislik;
            $yeniYukseklik = (int)(($yukseklik / $genislik) * $maxGenislik);
        } else {
            $yeniGenislik = $genislik;
            $yeniYukseklik = $yukseklik;
        }

        $dstImage = imagecreatetruecolor($yeniGenislik, $yeniYukseklik);
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $yeniGenislik, $yeniYukseklik, $genislik, $yukseklik);

        if (!file_exists($hedefKlasor)) {
            @mkdir($hedefKlasor, 0755, true);
        }

        $yeniDosyaAdi = "zbox_" . uniqid() . "_" . time() . ".webp";
        $kayitYolu = rtrim($hedefKlasor, '/') . '/' . $yeniDosyaAdi;

        $basarili = imagewebp($dstImage, $kayitYolu, $kalite);

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return $basarili ? $yeniDosyaAdi : false;
    }
}

if (!function_exists('thumbOlustur')) {
    function thumbOlustur($kaynakYolu, $hedefKlasor, $genislik = 400, $yukseklik = 300, $kalite = 75) {
        if (!file_exists($kaynakYolu)) return false;

        $srcImage = @imagecreatefromwebp($kaynakYolu);
        if (!$srcImage) return false;

        $orjGenislik = imagesx($srcImage);
        $orjYukseklik = imagesy($srcImage);

        $dstImage = imagecreatetruecolor($genislik, $yukseklik);
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);

        $orjOran = $orjGenislik / $orjYukseklik;
        $hedefOran = $genislik / $yukseklik;

        if ($orjOran >= $hedefOran) {
            $cropYukseklik = $orjYukseklik;
            $cropGenislik = (int)($orjYukseklik * $hedefOran);
            $srcX = (int)(($orjGenislik - $cropGenislik) / 2);
            $srcY = 0;
        } else {
            $cropGenislik = $orjGenislik;
            $cropYukseklik = (int)($orjGenislik / $hedefOran);
            $srcX = 0;
            $srcY = (int)(($orjYukseklik - $cropYukseklik) / 2);
        }

        imagecopyresampled($dstImage, $srcImage, 0, 0, $srcX, $srcY, $genislik, $yukseklik, $cropGenislik, $cropYukseklik);

        if (!file_exists($hedefKlasor)) {
            @mkdir($hedefKlasor, 0755, true);
        }

        $dosyaAdi = basename($kaynakYolu);
        $kayitYolu = rtrim($hedefKlasor, '/') . '/thumb_' . $dosyaAdi;

        $basarili = imagewebp($dstImage, $kayitYolu, $kalite);

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return $basarili;
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
    
    // Konum Verileri (SQL tablosundaki il, ilce, mahalle sütunlarıyla tam uyum)
    $il          = trim($_POST['il_id'] ?? '');
    $ilce        = trim($_POST['ilce_id'] ?? '');
    $mahalle     = !empty($_POST['mahalle_id']) ? trim($_POST['mahalle_id']) : null;
    $aciklama    = trim($_POST['aciklama'] ?? '');
    
    // 🚀 GİZLİLİK VE İLETİŞİM AYARLARI (ENUM DOĞRULAMASI)
    $iletisim_tercihi = trim($_POST['iletisim_tercihi'] ?? 'hepsi');
    $gecerli_tercihler = ['hepsi', 'sadece_telefon', 'sadece_mesaj'];
    if (!in_array($iletisim_tercihi, $gecerli_tercihler)) {
        $iletisim_tercihi = 'hepsi';
    }

    $isim_gizle = isset($_POST['isim_gizle']) ? 1 : 0;
    
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
        // 2. VERİTABANINA ANA KAYIT (SQL Şemasına Tam Uyumlu Prepared Statement)
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
            $il, $ilce, $mahalle, $iletisim_tercihi, $isim_gizle, $ip_adresi, $user_agent
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

            // 📸 RESİMLERİ WEBP & THUMBNAIL İLE GÜVENLİ YÜKLE (uploads/ilanlar Standartı)
            if (isset($_FILES['ilan_resimleri']['name'][0]) && !empty($_FILES['ilan_resimleri']['name'][0])) {
                $hedef_yol = dirname(__DIR__) . "/uploads/ilanlar/";
                $thumb_yol = dirname(__DIR__) . "/uploads/ilanlar/thumbs/";
                
                if (!file_exists($hedef_yol)) { 
                    @mkdir($hedef_yol, 0755, true); 
                }
                if (!file_exists($thumb_yol)) { 
                    @mkdir($thumb_yol, 0755, true); 
                }

                $gecerli_mime_tipleri = ['image/jpeg', 'image/png', 'image/webp'];

                foreach ($_FILES['ilan_resimleri']['name'] as $i => $ad) {
                    if ($_FILES['ilan_resimleri']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmp_yol = $_FILES['ilan_resimleri']['tmp_name'][$i];

                        // MIME Tipi Kontrolü (Güvenlik)
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $tmp_yol);
                        finfo_close($finfo);

                        if (in_array($mime_type, $gecerli_mime_tipleri)) {
                            // 1. Ana Resmi WebP yap (Max 1200px genişlik, %80 kalite)
                            $yeni_webp_adi = resimWebpIslem($tmp_yol, $hedef_yol, 1200, 80);

                            if ($yeni_webp_adi) {
                                // 2. Önizleme için Thumbnail (400x300px) Oluştur
                                $ana_resim_tam_yol = $hedef_yol . $yeni_webp_adi;
                                thumbOlustur($ana_resim_tam_yol, $thumb_yol, 400, 300, 75);

                                // 3. Veritabanına Kaydet (ilan_resimleri tablosuna uyumlu)
                                $ana_resim_durumu = ($i == 0) ? 1 : 0;
                                $db->prepare("INSERT INTO ilan_resimleri (ilan_id, dosya_adi, ana_resim) VALUES (?, ?, ?)")
                                   ->execute([$last_id, $yeni_webp_adi, $ana_resim_durumu]);
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