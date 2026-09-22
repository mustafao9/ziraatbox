<?php
require_once "../sistem/ayar.php";

// Güvenlik: Giriş yapılmamışsa engelle
if (!isset($_SESSION['uye_id'])) { 
    $_SESSION['hata'] = "Mesaj göndermek için giriş yapmalısınız.";
    header("Location: ../giris.php");
    exit; 
}

if ($_POST) {
    $uye_id      = $_SESSION['uye_id']; // Gönderen
    $mesaj_metni = g($_POST['mesaj']);
    $konusma_id  = isset($_POST['konusma_id']) ? intval($_POST['konusma_id']) : 0;

    // Mesaj boş olmamalı
    if(empty($mesaj_metni)) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // EĞER İLK KEZ MESAJ ATILIYORSA (Konuşma ID yoksa)
    if ($konusma_id == 0) {
        $ilan_id  = intval($_POST['ilan_id']);
        $karsi_id = intval($_POST['alici_id']);

        // Kendi kendine mesaj atmayı engelle
        if($uye_id == $karsi_id) { exit("Kendinize mesaj gönderemezsiniz."); }

        // Konuşma var mı kontrolü (Aynı ilan için iki kişi arasında)
        $kontrol = $db->prepare("SELECT id FROM mesaj_konusmalari WHERE ilan_id = ? AND ((baslatan_uye_id = ? AND karsi_uye_id = ?) OR (baslatan_uye_id = ? AND karsi_uye_id = ?))");
        $kontrol->execute([$ilan_id, $uye_id, $karsi_id, $karsi_id, $uye_id]);
        $mevcut = $kontrol->fetch();

        if ($mevcut) {
            $konusma_id = $mevcut['id'];
        } else {
            // Yeni başlık aç (mesaj_konusmalari tablosuna)
            $yeni = $db->prepare("INSERT INTO mesaj_konusmalari SET ilan_id = ?, baslatan_uye_id = ?, karsi_uye_id = ?");
            $yeni->execute([$ilan_id, $uye_id, $karsi_id]);
            $konusma_id = $db->lastInsertId();
        }
    }

    // 1. MESAJI KAYDET (mesajlar tablosuna)
    $ekle = $db->prepare("INSERT INTO mesajlar SET konusma_id = ?, gonderen_id = ?, mesaj = ?, okundu = 0");
    $ekle->execute([$konusma_id, $uye_id, $mesaj_metni]);

    // 2. KONUŞMAYI GÜNCELLE (Listede üste çıksın ve bildirim tetiklensin)
    $db->prepare("UPDATE mesaj_konusmalari SET son_mesaj_zamani = CURRENT_TIMESTAMP WHERE id = ?")->execute([$konusma_id]);

    // 3. YÖNLENDİR (Mesaj detayına)
    header("Location: ../mesaj-detay.php?id=" . $konusma_id);
    exit;
} else {
    header("Location: ../index.php");
    exit;
}