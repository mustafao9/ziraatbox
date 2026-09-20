<?php
require_once "../sistem/ayar.php";

$kat_id  = isset($_POST['kategori_id']) ? intval($_POST['kategori_id']) : 0;
$ilan_id = isset($_POST['ilan_id']) ? intval($_POST['ilan_id']) : 0;

if($kat_id > 0) {
    // 1. Bu kategoriye bağlı özellikleri bulalım
    $sorgu = $db->prepare("SELECT ot.* FROM kategori_ozellikleri ko 
                           JOIN ozellik_tanimlari ot ON ko.ozellik_id = ot.id 
                           WHERE ko.kategori_id = ? ORDER BY ko.sira ASC");
    $sorgu->execute([$kat_id]);
    $ozellikler = $sorgu->fetchAll();

    foreach($ozellikler as $oz) {
        // 2. Bu ilanın bu özellik için daha önce girdiği DEĞERİ bulalım
        $degerSorgu = $db->prepare("SELECT deger FROM ilan_ozellik_verileri WHERE ilan_id = ? AND ozellik_id = ?");
        $degerSorgu->execute([$ilan_id, $oz['id']]);
        $mevcut_deger = $degerSorgu->fetchColumn() ?: '';

        echo '<div class="form-group">
                <label class="form-label">'.$oz['ozellik_adi'].' '.($oz['birim'] ? "({$oz['birim']})" : '').'</label>';

        if($oz['veri_turu'] == 'liste') {
            echo '<select name="ozellik['.$oz['id'].']" class="form-control">';
            echo '<option value="">Seçiniz...</option>';
            $secenekler = explode(',', $oz['liste_icerik']);
            foreach($secenekler as $s) {
                $s = trim($s);
                $sel = ($mevcut_deger == $s) ? 'selected' : '';
                echo "<option value='$s' $sel>$s</option>";
            }
            echo '</select>';
        } 
        elseif($oz['veri_turu'] == 'onay') {
            $sel1 = ($mevcut_deger == 'Evet') ? 'selected' : '';
            $sel2 = ($mevcut_deger == 'Hayır') ? 'selected' : '';
            echo '<select name="ozellik['.$oz['id'].']" class="form-control">
                    <option value="Evet" '.$sel1.'>Evet</option>
                    <option value="Hayır" '.$sel2.'>Hayır</option>
                  </select>';
        }
        else {
            // Yazı, Sayı, Yıl vb. için normal input
            $type = ($oz['veri_turu'] == 'sayi') ? 'number' : 'text';
            echo '<input type="'.$type.'" name="ozellik['.$oz['id'].']" value="'.$mevcut_deger.'" class="form-control" placeholder="'.$oz['ozellik_adi'].' giriniz">';
        }
        echo '</div>';
    }
    
    if(empty($ozellikler)) {
        echo '<p style="color:#a0aec0; font-size:13px; grid-column: 1/-1;">Bu kategori için ek teknik özellik tanımlanmamış.</p>';
    }
}
?>