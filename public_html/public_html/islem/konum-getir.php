<?php
require_once "../sistem/ayar.php";

// İLÇELERİ GETİR
if (isset($_POST['il_id'])) {
    $il_id = intval($_POST['il_id']);
    $sorgu = $db->prepare("SELECT * FROM ilceler WHERE il_id = ? ORDER BY ilce_adi ASC");
    $sorgu->execute([$il_id]);
    $ilceler = $sorgu->fetchAll();

    if ($ilceler) {
        echo '<option value="">İlçe Seçin</option>';
        foreach ($ilceler as $ilce) {
            echo '<option value="'.$ilce['id'].'">'.$ilce['ilce_adi'].'</option>';
        }
    } else {
        echo '<option value="">İlçe Bulunamadı</option>';
    }
}

// MAHALLELERİ GETİR
if (isset($_POST['ilce_id'])) {
    $ilce_id = intval($_POST['ilce_id']);
    $sorgu = $db->prepare("SELECT * FROM mahalleler WHERE ilce_id = ? ORDER BY mahalle_adi ASC");
    $sorgu->execute([$ilce_id]);
    $mahalleler = $sorgu->fetchAll();

    if ($mahalleler) {
        echo '<option value="">Mahalle Seçin</option>';
        foreach ($mahalleler as $mahalle) {
            echo '<option value="'.$mahalle['id'].'">'.$mahalle['mahalle_adi'].'</option>';
        }
    } else {
        echo '<option value="">Mahalle Bulunamadı</option>';
    }
}
?>