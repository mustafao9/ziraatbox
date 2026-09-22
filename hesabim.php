<?php
require_once 'sistem/ayar.php';
require_once 'sistem/fonksiyonlar.php';

// Oturum kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php");
    exit;
}

$uye_id = (int)$_SESSION['kullanici_id'];

// Güvenli Prepared Statement Sorguları
$stmt1 = $db->prepare("SELECT COUNT(*) FROM ilanlar WHERE uye_id = ?");
$stmt1->execute([$uye_id]);
$toplam_ilan = $stmt1->fetchColumn();

$stmt2 = $db->prepare("SELECT COUNT(*) FROM favoriler WHERE uye_id = ?");
$stmt2->execute([$uye_id]);
$favori_say = $stmt2->fetchColumn();

$stmt3 = $db->prepare("SELECT COUNT(*) FROM sikayetler WHERE sikayetci_id = ?");
$stmt3->execute([$uye_id]);
$aktif_sikayet_say = $stmt3->fetchColumn();

$stmt4 = $db->prepare("SELECT s.*, i.baslik FROM sikayetler s LEFT JOIN ilanlar i ON s.ilan_id = i.id WHERE s.sikayetci_id = ? ORDER BY s.id DESC");
$stmt4->execute([$uye_id]);
$sikayet_takip = $stmt4->fetchAll(PDO::FETCH_ASSOC);

include 'parcalar/ust.php';
?>

<!-- Sayfa içeriği aynı şekilde devam eder -->
