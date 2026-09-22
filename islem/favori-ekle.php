<?php
require_once "../sistem/ayar.php";
if ($_POST && isset($_SESSION['uye_id'])) {
    $uye_id = $_SESSION['uye_id'];
    $ilan_id = intval($_POST['ilan_id']);

    $kontrol = $db->prepare("SELECT id FROM favoriler WHERE uye_id = ? AND ilan_id = ?");
    $kontrol->execute([$uye_id, $ilan_id]);
    
    if ($kontrol->fetch()) {
        $db->prepare("DELETE FROM favoriler WHERE uye_id = ? AND ilan_id = ?")->execute([$uye_id, $ilan_id]);
        echo "silindi";
    } else {
        $db->prepare("INSERT INTO favoriler SET uye_id = ?, ilan_id = ?")->execute([$uye_id, $ilan_id]);
        echo "eklendi";
    }
}