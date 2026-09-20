<?php
/**
 * ZiraatBox - İlan Silme Motoru v3.0 (.env & Güvenlik Güncellemesi)
 * Mustafa Satılmış - İncirliova / Aydın
 */

ob_start();
require_once __DIR__ . "/../sistem/ayar.php";

$app_url = function_exists('env') ? env('APP_URL', 'http://ziraatbox.com') : 'http://ziraatbox.com';

// 1. GÜVENLİK: Oturum ve Yetki Kontrolü
if (!isset($_SESSION['uye_id'])) {
    header("Location: " . $app_url . "/giris.php");
    exit;
}

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$uye_id = (int)$_SESSION['uye_id'];

if ($id > 0) {
    try {
        // 2. İLAN KONTROLÜ: İlan gerçekten bu üyeye mi ait?
        $ilan_kontrol = $db->prepare("SELECT id FROM ilanlar WHERE id = ? AND uye_id = ? LIMIT 1");
        $ilan_kontrol->execute([$id, $uye_id]);
        
        if ($ilan_kontrol->fetch()) {
            
            // 3. FİZİKSEL RESİMLERİ SİLME (Sunucu Temizliği)
            $resimler = $db->prepare("SELECT dosya_adi FROM ilan_resimleri WHERE ilan_id = ?");
            $resimler->execute([$id]);
            $resim_listesi = $resimler->fetchAll();

            $dizin = __DIR__ . "/../yuklemeler/ilanlar/";

            foreach ($resim_listesi as $r) {
                if (!empty($r['dosya_adi'])) {
                    $tam_yol = $dizin . $r['dosya_adi'];
                    if (file_exists($tam_yol)) {
                        @unlink($tam_yol); // Dosyayı klasörden kalıcı olarak siler
                    }
                }
            }

            // 4. VERİTABANI TEMİZLİĞİ (İlişkili tüm kayıtlar silinir)
            $db->prepare("DELETE FROM ilan_ozellik_verileri WHERE ilan_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM ilan_resimleri WHERE ilan_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM favoriler WHERE ilan_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM ilanlar WHERE id = ? AND uye_id = ?")->execute([$id, $uye_id]);

            $_SESSION['mesaj'] = "İlan ve bağlı tüm dosyalar kalıcı olarak silindi.";
            header("Location: " . $app_url . "/hesabim.php?durum=silindi");
            exit;
        } else {
            // İlan bu üyeye ait değilse veya bulunamadıysa
            header("Location: " . $app_url . "/hesabim.php?hata=yetkisiz");
            exit;
        }
    } catch (PDOException $e) {
        if (function_exists('env') && env('APP_DEBUG') === 'true') {
            die("İlan Silme Hatası: " . $e->getMessage());
        } else {
            header("Location: " . $app_url . "/hesabim.php?hata=sistem");
            exit;
        }
    }
} else {
    header("Location: " . $app_url . "/index.php");
    exit;
}
ob_end_flush();