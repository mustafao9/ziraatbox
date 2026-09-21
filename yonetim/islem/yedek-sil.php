<?php
// yedek-sil.php
require_once "../../sistem/ayar.php";
if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){ exit; }

if (isset($_GET['dosya'])) {
    $dosya = basename($_GET['dosya']);
    $yol = "../yedekler/" . $dosya;
    if (file_exists($yol)) {
        unlink($yol);
        header("Location: ../yedekleme.php?islem=silindi");
        exit;
    }
}
header("Location: ../yedekleme.php?islem=hata");