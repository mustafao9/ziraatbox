<?php
/**
 * ZiraatBox - İlan Güncelleme Motoru v3.0 (.env & Güvenlik Güncellemesi)
 * Mustafa Satılmış - İncirliova / Aydın
 */

ob_start();
require_once __DIR__ . "/../sistem/ayar.php";

$app_url = function_exists('env') ? env('APP_URL', 'http://ziraatbox.com') : 'http://ziraatbox.com';

// 1. OTURUM VE YETKİ KONTROLÜ
if (!isset($_SESSION['uye_id'])) { 
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        die(json_encode(['durum' => 'hata', 'mesaj' => 'Yetkisiz erişim!'])); 
    } else {
        header("Location: " . $app_url . "/giris.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formdan gelen ID ve Üye Bilgisi
    $id          = (int)($_POST['id'] ?? 0);
    $uye_id      = (int)$_SESSION['uye_id'];
    
    if ($id <= 0) {
        header("Location: " . $app_url . "/ilanlarim.php?hata=gecersiz_id");
        exit;
    }

    // Temel Bilgiler
    $baslik      = trim($_POST['baslik'] ?? '');
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    
    // Fiyat Temizleme (1.500,00 -> 1500.00)
    $fiyat_ham   = $_POST['fiyat'] ?? '0';
    $fiyat       = (float)str_replace(['.', ','], ['', '.'], $fiyat_ham);
    
    $satici_tipi = trim($_POST['satici_tipi'] ?? 'Uretici'); 
    $aciklama    = trim($_POST['aciklama'] ?? '');
    
    // Konum Bilgileri
    $il_id       = (int)($_POST['il_id'] ?? 0);
    $ilce_id     = (int)($_POST['ilce_id'] ?? 0);
    $mahalle_id  = (int)($_POST['mahalle_id'] ?? 0);
    
    // Güvenlik ve Log
    $user_agent  = $_SERVER['HTTP_USER_AGENT'] ?? 'Bilinmiyor';

    try {
        // 2. SAHİPLİK DOĞRULAMA (Güvenlik)
        $kontrol = $db->prepare("SELECT id FROM ilanlar WHERE id = ? AND uye_id = ? LIMIT 1");
        $kontrol->execute([$id, $uye_id]);
        if (!$kontrol->fetch()) {
            header("Location: " . $app_url . "/ilanlarim.php?hata=yetkisiz");
            exit;
        }

        // 3. ANA GÜNCELLEME SORGU (PDO Prepared Statement)
        $guncelle = $db->prepare("UPDATE ilanlar SET 
            baslik = ?, 
            fiyat = ?, 
            kategori_id = ?, 
            satici_tipi = ?, 
            aciklama = ?, 
            il = ?, 
            ilce = ?, 
            mahalle = ?, 
            durum = 'beklemede',
            user_agent = ?
            WHERE id = ? AND uye_id = ?");
        
        $sonuc = $guncelle->execute([
            $baslik, 
            $fiyat, 
            $kategori_id, 
            $satici_tipi, 
            $aciklama, 
            $il_id, 
            $ilce_id, 
            $mahalle_id, 
            $user_agent,
            $id, 
            $uye_id
        ]);

        if ($sonuc) {
            // 4. TEKNİK ÖZELLİKLERİ GÜNCELLE
            $db->prepare("DELETE FROM ilan_ozellik_verileri WHERE ilan_id = ?")->execute([$id]);
            if (isset($_POST['ozellik']) && is_array($_POST['ozellik'])) {
                $oz_kaydet = $db->prepare("INSERT INTO ilan_ozellik_verileri (ilan_id, ozellik_id, deger) VALUES (?, ?, ?)");
                foreach ($_POST['ozellik'] as $oz_id => $deger) {
                    if (trim($deger) !== "") {
                        $oz_kaydet->execute([$id, (int)$oz_id, trim($deger)]);
                    }
                }
            }

            $dizin = __DIR__ . "/../uploads/ilanlar/";

            // 5. RESİM SİLME (Checkbox: silinecek_resimler[]) - Dizin Yolu Düzeltildi
            if (isset($_POST['silinecek_resimler']) && is_array($_POST['silinecek_resimler'])) {
                foreach ($_POST['silinecek_resimler'] as $resim_id) {
                    $resim_id_int = (int)$resim_id;
                    $r_sorgu = $db->prepare("SELECT dosya_adi FROM ilan_resimleri WHERE id = ? AND ilan_id = ?");
                    $r_sorgu->execute([$resim_id_int, $id]);
                    $r_data = $r_sorgu->fetch();
                    
                    if ($r_data) {
                        $tam_yol = $dizin . $r_data['dosya_adi'];
                        if (file_exists($tam_yol)) { 
                            @unlink($tam_yol); 
                        }
                        $db->prepare("DELETE FROM ilan_resimleri WHERE id = ? AND ilan_id = ?")->execute([$resim_id_int, $id]);
                    }
                }
            }

            // 6. YENİ RESİM YÜKLEME (File: yeni_resimler[])
            if (isset($_FILES['yeni_resimler']['name'][0]) && !empty($_FILES['yeni_resimler']['name'][0])) {
                $mevcut_sorgu = $db->prepare("SELECT COUNT(*) FROM ilan_resimleri WHERE ilan_id = ?");
                $mevcut_sorgu->execute([$id]);
                $mevcut_sayi = (int)$mevcut_sorgu->fetchColumn();

                $kalan_hak = 5 - $mevcut_sayi;

                if (!file_exists($dizin)) { 
                    @mkdir($dizin, 0755, true); 
                }

                $gecerli_uzantilar = ['jpg', 'jpeg', 'png', 'webp'];
                $gecerli_mime_tipleri = ['image/jpeg', 'image/png', 'image/webp'];

                foreach ($_FILES['yeni_resimler']['name'] as $key => $name) {
                    if ($kalan_hak <= 0) break;

                    if ($_FILES['yeni_resimler']['error'][$key] === UPLOAD_ERR_OK) {
                        $gecici_yol = $_FILES['yeni_resimler']['tmp_name'][$key];
                        $uzanti     = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                        // MIME Tipi Kontrolü (Güvenlik)
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $gecici_yol);
                        finfo_close($finfo);

                        if (in_array($uzanti, $gecerli_uzantilar) && in_array($mime_type, $gecerli_mime_tipleri)) {
                            $yeni_ad = "zbox_" . uniqid() . "." . $uzanti;
                            if (move_uploaded_file($gecici_yol, $dizin . $yeni_ad)) {
                                $db->prepare("INSERT INTO ilan_resimleri (ilan_id, dosya_adi, ana_resim) VALUES (?, ?, 0)")
                                   ->execute([$id, $yeni_ad]);
                                $kalan_hak--;
                            }
                        }
                    }
                }
            }

            header("Location: " . $app_url . "/ilanlarim.php?islem=guncellendi");
            exit;

        } else {
            header("Location: " . $app_url . "/ilanlarim.php?hata=sistem");
            exit;
        }

    } catch (PDOException $e) {
        if (function_exists('env') && env('APP_DEBUG') === 'true') {
            die("İlan Güncelleme Hatası: " . $e->getMessage());
        } else {
            header("Location: " . $app_url . "/ilanlarim.php?hata=sistem");
            exit;
        }
    }
} else {
    header("Location: " . $app_url . "/index.php");
    exit;
}
ob_end_flush();