<?php
require_once "../sistem/ayar.php";

// Sadece giriş yapmış kullanıcılar favori ekleyebilir
if ($_POST && isset($_SESSION['uye_id'])) {
    $uye_id = $_SESSION['uye_id'];
    $ilan_id = intval($_POST['ilan_id']);

    if ($ilan_id > 0) {
        // 1. DAHA ÖNCE EKLENMİŞ Mİ?
        $kontrol = $db->prepare("SELECT id FROM favoriler WHERE uye_id = ? AND ilan_id = ?");
        $kontrol->execute([$uye_id, $ilan_id]);
        $favori = $kontrol->fetch();

        if ($favori) {
            // ❌ VARSA: SİSTEMDEN ÇIKAR
            $db->prepare("DELETE FROM favoriler WHERE id = ?")->execute([$favori['id']]);
            
            // İlan tablosundaki beğeni sayısını düşür
            $db->prepare("UPDATE ilanlar SET begeni_sayisi = begeni_sayisi - 1 WHERE id = ? AND begeni_sayisi > 0")->execute([$ilan_id]);
            
            echo json_encode(['durum' => 'cikarildi']);
        } else {
            // 🚀 2. İLANIN GÜNCEL FİYATINI ALALIM (Fiyat Takibi İçin Şart)
            $ilan_sorgu = $db->prepare("SELECT fiyat FROM ilanlar WHERE id = ?");
            $ilan_sorgu->execute([$ilan_id]);
            $ilan_fiyat = $ilan_sorgu->fetchColumn();

            // ✅ 3. SİSTEME EKLE (eklenen_fiyat alanı ile birlikte)
            $ekle = $db->prepare("INSERT INTO favoriler SET uye_id = ?, ilan_id = ?, eklenen_fiyat = ?");
            $ekle->execute([$uye_id, $ilan_id, $ilan_fiyat]);
            
            // İlan tablosundaki beğeni sayısını artır
            $db->prepare("UPDATE ilanlar SET begeni_sayisi = begeni_sayisi + 1 WHERE id = ?")->execute([$ilan_id]);
            
            echo json_encode(['durum' => 'eklendi']);
        }
    }
} else {
    // Oturum yoksa SweetAlert tarafında giriş sayfasına yönlendirme yapılabilir
    echo json_encode(['durum' => 'oturum_yok']);
}
?>