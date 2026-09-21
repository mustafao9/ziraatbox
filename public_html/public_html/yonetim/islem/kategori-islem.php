<?php
require_once "../../sistem/ayar.php";

/**
 * 🔐 GÜVENLİK KONTROLÜ
 * Sadece admin yetkisi olanlar ve giriş yapmış olanlar işlem yapabilir.
 */
if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    header("Location: ../giris.php"); 
    exit;
}

// 🚀 İŞLEM TETİKLENDİ Mİ? (Formdan 'kategori_kaydet' veya 'kategori_ekle' gelmiş olabilir)
if (isset($_POST['kategori_kaydet']) || isset($_POST['kategori_ekle'])) {
    
    // Verileri Güvenli Şekilde Yakalayalım
    $id      = intval($_POST['kategori_id'] ?? 0);
    $ust_id  = intval($_POST['ust_id']);
    $adi     = g($_POST['adi'] ?? $_POST['kategori_adi']);
    $ikon    = g($_POST['ikon']);
    $sira    = intval($_POST['sira']);
    $slug    = sef_link($adi); // sef_link fonksiyonunun sistemde tanımlı olduğunu varsayıyoruz.

    if (!empty($adi)) {
        
        if($id > 0){
            // 📝 MEVCUT KATEGORİYİ GÜNCELLE
            $sorgu = $db->prepare("UPDATE kategoriler SET ust_id = ?, adi = ?, ikon = ?, slug = ?, sira = ? WHERE id = ?");
            $sonuc = $sorgu->execute([$ust_id, $adi, $ikon, $slug, $sira, $id]);
            $mesaj = "guncellendi";
            $kategori_id = $id;
        } else {
            // ✨ YENİ KATEGORİ EKLE
            $sorgu = $db->prepare("INSERT INTO kategoriler SET ust_id = ?, adi = ?, ikon = ?, slug = ?, sira = ?, aktif = 1");
            $sonuc = $sorgu->execute([$ust_id, $adi, $ikon, $slug, $sira]);
            $kategori_id = $db->lastInsertId();
            $mesaj = "eklendi";
        }

        if ($sonuc) {
            // 🛠️ TEKNİK ÖZELLİK BAĞLANTILARINI YÖNET
            // Önce bu kategoriye ait eski özellikleri temizleyelim (Güncelleme durumu için önemli)
            $db->prepare("DELETE FROM kategori_ozellikleri WHERE kategori_id = ?")->execute([$kategori_id]);

            // Formdan özellikler seçilmişse (Array olarak gelir)
            if(isset($_POST['kat_ozellikler']) && is_array($_POST['kat_ozellikler'])){
                $oz_bagla = $db->prepare("INSERT INTO kategori_ozellikleri (kategori_id, ozellik_id) VALUES (?, ?)");
                foreach($_POST['kat_ozellikler'] as $oz_id){
                    $oz_bagla->execute([$kategori_id, intval($oz_id)]);
                }
            }

            header("Location: ../kategoriler.php?durum=$mesaj");
            exit;
        } else {
            header("Location: ../kategoriler.php?durum=hata");
            exit;
        }
    } else {
        header("Location: ../kategoriler.php?durum=bos");
        exit;
    }

} elseif (isset($_GET['sil'])) {
    // 🗑️ SİLME İŞLEMİ
    $sil_id = intval($_GET['sil']);

    // KRİTİK: Eğer altında alt kategoriler varsa silmeyi engelle!
    $kontrol = $db->prepare("SELECT id FROM kategoriler WHERE ust_id = ? LIMIT 1");
    $kontrol->execute([$sil_id]);
    if($kontrol->fetch()){
        header("Location: ../kategoriler.php?durum=hata_alt_var");
        exit;
    }

    $sil = $db->prepare("DELETE FROM kategoriler WHERE id = ?");
    if($sil->execute([$sil_id])){
        // Kategoriye bağlı özellik tanımlarını da temizle
        $db->prepare("DELETE FROM kategori_ozellikleri WHERE kategori_id = ?")->execute([$sil_id]);
        header("Location: ../kategoriler.php?durum=silindi");
    } else {
        header("Location: ../kategoriler.php?durum=hata");
    }
    exit;

} else {
    header("Location: ../index.php");
    exit;
}
?>