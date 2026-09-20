<?php
/**
 * ZiraatBox - Profil Güncelleme Motoru (Google Resim Kayıt Versiyonu)
 */
require_once "../sistem/ayar.php";

if (isset($_POST['profil_guncelle'])) {
    if (!isset($_SESSION['uye_id'])) {
        header("Location: ../giris.php");
        exit;
    }

    $uye_id       = $_SESSION['uye_id'];
    $ad_soyad     = trim($_POST['ad_soyad']);
    $telefon      = trim($_POST['telefon']);
    $mevcut_sifre = isset($_POST['mevcut_sifre']) ? $_POST['mevcut_sifre'] : null;
    $yeni_sifre   = $_POST['yeni_sifre'];
    $yeni_sifre_t = $_POST['yeni_sifre_tekrar'];

    // 1. KULLANICI VERİLERİNİ ÇEK
    $sorgu = $db->prepare("SELECT sifre, google_id, profil_foto FROM uyeler WHERE id = ?");
    $sorgu->execute([$uye_id]);
    $uye = $sorgu->fetch();

    // 🚀 GOOGLE KONTROLÜ
    $is_google = (!empty($uye['google_id']));

    if (!$is_google) {
        if (empty($mevcut_sifre) || !password_verify($mevcut_sifre, $uye['sifre'])) {
            $_SESSION['hata'] = "Güvenlik onayı için mevcut şifreniz hatalı.";
            header("Location: ../profil-ayarlarim.php");
            exit;
        }
    }

    // 2. AD SOYAD KONTROLÜ
    if (empty($ad_soyad)) {
        $_SESSION['hata'] = "Ad Soyad alanı boş bırakılamaz.";
        header("Location: ../profil-ayarlarim.php");
        exit;
    }

    $sifre_sql = "";
    $params = [$ad_soyad, $telefon];

    // 3. ŞİFRE GÜNCELLEME
    if (!empty($yeni_sifre)) {
        if ($yeni_sifre === $yeni_sifre_t && strlen($yeni_sifre) >= 6) {
            $sifre_sql = ", sifre = ?";
            $params[] = password_hash($yeni_sifre, PASSWORD_DEFAULT);
        } else {
            $_SESSION['hata'] = "Şifreler uyuşmuyor veya 6 karakterden az.";
            header("Location: ../profil-ayarlarim.php");
            exit;
        }
    }

    // 4. PROFİL RESMİ YÜKLEME VE GOOGLE RESMİNİ ÇEKME
    $foto_sql = "";
    $klasor = "../yuklemeler/profil/";

    if (isset($_FILES['profil_foto']) && $_FILES['profil_foto']['error'] == 0) {
        // Kullanıcı manuel resim yüklüyor
        $yeni_ad = "profil_" . $uye_id . "_" . rand(1000, 9999) . ".jpg";
        if (move_uploaded_file($_FILES['profil_foto']['tmp_name'], $klasor . $yeni_ad)) {
            if (!empty($uye['profil_foto']) && !filter_var($uye['profil_foto'], FILTER_VALIDATE_URL)) {
                @unlink($klasor . $uye['profil_foto']);
            }
            $foto_sql = ", profil_foto = ?";
            $params[] = $yeni_ad;
        }
    } 
    elseif (filter_var($uye['profil_foto'], FILTER_VALIDATE_URL)) {
        // Kullanıcı resim seçmedi ama mevcut resmi hala Google URL'si ise; Sunucuya indir!
        $yeni_ad = "google_user_" . $uye_id . "_" . time() . ".jpg";
        $img_content = @file_get_contents($uye['profil_foto']);
        if($img_content) {
            file_put_contents($klasor . $yeni_ad, $img_content);
            $foto_sql = ", profil_foto = ?";
            $params[] = $yeni_ad;
        }
    }

    // 5. GÜNCELLEME
    $params[] = $uye_id;
    $sql = "UPDATE uyeler SET ad_soyad = ?, telefon = ? $sifre_sql $foto_sql WHERE id = ?";
    $db->prepare($sql)->execute($params);

    $_SESSION['ad_soyad'] = $ad_soyad;
    $_SESSION['mesaj'] = "Profil bilgileriniz başarıyla güncellendi.";
    header("Location: ../profil-ayarlarim.php?durum=basarili");
    exit;
}