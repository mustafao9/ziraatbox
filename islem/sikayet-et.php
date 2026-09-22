<?php
/**
 * ZiraatBox - İlan Şikayet Motoru (5651 Uyumlu)
 */
require_once "../sistem/ayar.php";

if ($_POST) {
    if (!isset($_SESSION['uye_id'])) {
        die(json_encode(['durum' => 'hata', 'mesaj' => 'Yetkisiz erişim!']));
    }

    $sikayetci_id = $_SESSION['uye_id'];
    $ilan_id      = intval($_POST['ilan_id']);
    $neden        = isset($_POST['neden']) ? htmlspecialchars($_POST['neden']) : '';
    $not          = isset($_POST['ek_not']) ? htmlspecialchars($_POST['ek_not']) : '';

    if(empty($neden)) {
        die(json_encode(['durum' => 'hata', 'mesaj' => 'Lütfen bir neden seçin.']));
    }

    $tam_mesaj = "Neden: " . $neden;
    if(!empty($not)) { $tam_mesaj .= " | Ek Not: " . $not; }

    // SQL yapınıza göre (sikayetci_id, ilan_id, mesaj, durum)
    $sorgu = $db->prepare("INSERT INTO sikayetler SET 
        sikayetci_id = ?, 
        ilan_id      = ?, 
        mesaj        = ?, 
        durum        = 'beklemede',
        created_at   = NOW()");
    
    $islem = $sorgu->execute([$sikayetci_id, $ilan_id, $tam_mesaj]);

    if ($islem) {
        // Buradan yöneticiye mail veya farklı bir bildirim de tetiklenebilir.
        echo json_encode(['durum' => 'basarili']);
    } else {
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Kayıt sırasında teknik bir hata oluştu.']);
    }
} else {
    header("Location: ../index.php"); exit;
}