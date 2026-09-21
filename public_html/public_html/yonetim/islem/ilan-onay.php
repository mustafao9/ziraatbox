<?php
require_once "../../sistem/ayar.php";

// Yetki kontrolü buraya eklenebilir
$id    = intval($_GET['id']);
$islem = g($_GET['islem']); // 'aktif' veya 'reddedildi'

if ($id > 0 && ($islem == 'aktif' || $islem == 'reddedildi')) {
    $guncelle = $db->prepare("UPDATE ilanlar SET durum = ? WHERE id = ?");
    $sonuc = $guncelle->execute([$islem, $id]);

    if ($sonuc) {
        // İsteğe bağlı: Kullanıcıya "İlanınız onaylandı" bildirimi gönderilebilir
        header("Location: ../index.php?durum=basarili");
    } else {
        header("Location: ../index.php?durum=hata");
    }
} else {
    header("Location: ../index.php");
}
?>