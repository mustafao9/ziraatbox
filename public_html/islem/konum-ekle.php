<?php
require_once "../sistem/ayar.php";

// Sadece oturumu açık üyeler köy ekleyebilsin (Spam koruması)
if ($_POST && isset($_SESSION['uye_id'])) {
    
    // Verileri g() fonksiyonu ile süzerek alıyoruz
    $il_id       = intval($_POST['il_id']);
    $ilce_id     = intval($_POST['ilce_id']);
    $mahalle_adi = g(mb_convert_case($_POST['mahalle_adi'], MB_CASE_TITLE, "UTF-8"));

    if ($ilce_id > 0 && !empty($mahalle_adi)) {
        
        // 1. KONTROL: Mükerrer kayıt var mı? (SQL Injection Korumalı)
        $kontrol = $db->prepare("SELECT id FROM mahalleler WHERE ilce_id = ? AND mahalle_adi = ?");
        $kontrol->execute([$ilce_id, $mahalle_adi]);
        $mevcut = $kontrol->fetch();

        if ($mevcut) {
            // Zaten varsa mevcut ID'yi gönderiyoruz
            echo json_encode(['durum' => 'var', 'id' => $mevcut['id']]);
        } else {
            // 2. KAYIT: Yeni köyü ekle (Prepared Statement ile %100 Güvenli)
            $ekle = $db->prepare("INSERT INTO mahalleler (il_id, ilce_id, mahalle_adi) VALUES (?, ?, ?)");
            $sonuc = $ekle->execute([$il_id, $ilce_id, $mahalle_adi]);
            
            if($sonuc) {
                echo json_encode(['durum' => 'ok', 'id' => $db->lastInsertId()]);
            } else {
                echo json_encode(['durum' => 'hata']);
            }
        }
    } else {
        echo json_encode(['durum' => 'eksik_veri']);
    }
} else {
    echo json_encode(['durum' => 'yetkisiz']);
}
?>