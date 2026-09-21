<?php
require_once "../sistem/ayar.php";
$kat_id = isset($_POST['kat_id']) ? intval($_POST['kat_id']) : 0;

if($kat_id > 0) {
    $sorgu = $db->prepare("SELECT ot.* FROM kategori_ozellikleri ko 
                           JOIN ozellik_tanimlari ot ON ko.ozellik_id = ot.id 
                           WHERE ko.kategori_id = ? ORDER BY ko.sira ASC");
    $sorgu->execute([$kat_id]);
    $ozellikler = $sorgu->fetchAll();

    foreach($ozellikler as $oz) {
        echo '<div style="margin-bottom:15px;">
                <label style="display:block; font-size:12px; font-weight:800; color:#1b4332; margin-bottom:5px; text-transform:uppercase;">'.$oz['ozellik_adi'].' '.($oz['birim'] ? "({$oz['birim']})" : '').'</label>';
        
        if($oz['veri_turu'] == 'liste') {
            echo '<select name="ozellik['.$oz['id'].']" style="width:100%; padding:12px; border:2px solid #edf2f7; border-radius:10px; outline:none;">';
            echo '<option value="">Seçiniz...</option>';
            $dizi = explode(',', $oz['liste_icerik']);
            foreach($dizi as $s) { $s = trim($s); echo "<option value='$s'>$s</option>"; }
            echo '</select>';
        } elseif($oz['veri_turu'] == 'onay') {
            echo '<select name="ozellik['.$oz['id'].']" style="width:100%; padding:12px; border:2px solid #edf2f7; border-radius:10px;">
                    <option value="Evet">Evet</option>
                    <option value="Hayır">Hayır</option>
                  </select>';
        } else {
            $type = ($oz['veri_turu'] == 'sayi') ? 'number' : 'text';
            echo '<input type="'.$type.'" name="ozellik['.$oz['id'].']" placeholder="'.$oz['ozellik_adi'].' girin" style="width:100%; padding:12px; border:2px solid #edf2f7; border-radius:10px; outline:none;">';
        }
        echo '</div>';
    }
}
?>