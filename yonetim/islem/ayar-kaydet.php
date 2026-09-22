<?php
require_once "../../sistem/ayar.php";

if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    header("Location: ../giris.php"); exit;
}

if(isset($_POST['ayarlari_kaydet'])){
    $eski_ayarlar = $db->query("SELECT * FROM ayarlar WHERE id = 1")->fetch();

    $site_baslik         = g($_POST['site_baslik']);
    $site_slogan         = g($_POST['site_slogan']);
    $site_desc           = g($_POST['site_desc']);
    $site_tel            = g($_POST['site_tel']);
    $site_eposta         = g($_POST['site_eposta']);
    $site_adres          = g($_POST['site_adres']);
    $ilan_suresi_gun     = intval($_POST['ilan_suresi_gun']);
    $ilan_onay_gerekiyor = intval($_POST['ilan_onay_gerekiyor']);
    $facebook            = g($_POST['facebook']);
    $instagram           = g($_POST['instagram']);
    $twitter             = isset($_POST['twitter']) ? g($_POST['twitter']) : '';
    $smtp_host           = g($_POST['smtp_host']);
    $smtp_user           = g($_POST['smtp_user']);
    $smtp_port           = g($_POST['smtp_port']);
    $smtp_secure         = g($_POST['smtp_secure']);
    $bakim_modu          = intval($_POST['bakim_modu']);
    
    // Yeni Eklenen Alanlar
    $hakkimizda          = $_POST['hakkimizda']; // Editor gelebileceği için g() fonksiyonundan geçirmedim
    $kvkk                = $_POST['kvkk'];

    $smtp_pass = !empty($_POST['smtp_pass']) ? g($_POST['smtp_pass']) : $eski_ayarlar['smtp_pass'];

    $guncelle = $db->prepare("UPDATE ayarlar SET 
        site_baslik = ?, site_slogan = ?, site_desc = ?, site_tel = ?, 
        site_eposta = ?, site_adres = ?, ilan_suresi_gun = ?, ilan_onay_gerekiyor = ?,
        facebook = ?, instagram = ?, twitter = ?, smtp_host = ?, 
        smtp_user = ?, smtp_pass = ?, smtp_port = ?, smtp_secure = ?, 
        bakim_modu = ?, hakkimizda = ?, kvkk = ?
        WHERE id = 1");
    
    $sonuc = $guncelle->execute([
        $site_baslik, $site_slogan, $site_desc, $site_tel, 
        $site_eposta, $site_adres, $ilan_suresi_gun, $ilan_onay_gerekiyor,
        $facebook, $instagram, $twitter, $smtp_host,
        $smtp_user, $smtp_pass, $smtp_port, $smtp_secure, 
        $bakim_modu, $hakkimizda, $kvkk
    ]);

    // Logo Yükleme
    if($_FILES['logo']['size'] > 0){
        $dizin = "../../dosyalar/resim/";
        $isim = "logo.png";
        move_uploaded_file($_FILES['logo']['tmp_name'], $dizin.$isim);
    }

    header("Location: ../ayarlar.php?durum=".($sonuc ? 'ok' : 'hata')); exit;
}