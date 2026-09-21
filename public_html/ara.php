<?php 
require_once "parcalar/ust.php"; 

/**
 * ZiraatBox - PLUS PREMIUM ARA v23.5
 * %100 Stabil İlan No, Konum ve Dinamik Özellik Filtreleme
 */

// 1. REKÜRSİF KATEGORİ FONKSİYONU (Hiyerarşiyi bozmaz)
if (!function_exists('altKategoriBul_Sistem')) {
    function altKategoriBul_Sistem($db, $ust_id) {
        $ids = [(int)$ust_id];
        $sorgu = $db->prepare("SELECT id FROM kategoriler WHERE ust_id = ? AND aktif = 1");
        $sorgu->execute([$ust_id]);
        $altlar = $sorgu->fetchAll(PDO::FETCH_COLUMN);
        foreach ($altlar as $id) { $ids = array_merge($ids, altKategoriBul_Sistem($db, $id)); }
        return array_unique($ids);
    }
}

// 2. PARAMETRE GÜVENLİĞİ
$q         = isset($_GET['q']) ? g($_GET['q']) : '';
$kat_id    = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;
$il_id     = isset($_GET['il']) ? intval($_GET['il']) : 0;
$ilce_id   = isset($_GET['ilce']) ? intval($_GET['ilce']) : 0;
$min       = isset($_GET['min']) ? floatval($_GET['min']) : 0;
$max       = isset($_GET['max']) ? floatval($_GET['max']) : 0;
$dinamik_filtreler = isset($_GET['filtre']) ? $_GET['filtre'] : [];

// 3. SQL İNŞASI (Mükemmel Hizalanmış)
$sql = "SELECT i.*, il.il_adi, ilc.ilce_adi, 
        (SELECT dosya_adi FROM ilan_resimleri WHERE ilan_id = i.id ORDER BY ana_resim DESC LIMIT 1) as ana_resim 
        FROM ilanlar i 
        LEFT JOIN iller il ON i.il = il.id 
        LEFT JOIN ilceler ilc ON i.ilce = ilc.id 
        WHERE i.durum = 'aktif'";

$params = [];

// 🚀 İLAN NO & KELİME KONTROLÜ (İyileştirildi)
if (!empty($q)) {
    if (is_numeric($q)) {
        // Sayıysa: İlan No (id), Başlık veya ilan_no sütununda ara
        $sql .= " AND (i.baslik LIKE ? OR i.id = ? OR i.ilan_no = ?)";
        $params[] = "%$q%"; 
        $params[] = (int)$q;
        $params[] = $q;
    } else {
        // Metinse: Başlık ve Açıklamada ara
        $sql .= " AND (i.baslik LIKE ? OR i.aciklama LIKE ?)";
        $params[] = "%$q%"; 
        $params[] = "%$q%";
    }
}

// Kategori Filtresi
if ($kat_id > 0) {
    $ilgili_idler = altKategoriBul_Sistem($db, $kat_id);
    $in_q = implode(',', array_fill(0, count($ilgili_idler), '?'));
    $sql .= " AND i.kategori_id IN ($in_q)";
    foreach($ilgili_idler as $id) $params[] = $id;
}

// Konum & Fiyat
if ($il_id > 0) { $sql .= " AND i.il = ?"; $params[] = $il_id; }
if ($ilce_id > 0) { $sql .= " AND i.ilce = ?"; $params[] = $ilce_id; }
if ($min > 0) { $sql .= " AND i.fiyat >= ?"; $params[] = $min; }
if ($max > 0) { $sql .= " AND i.fiyat <= ?"; $params[] = $max; }

// Dinamik Özellik Filtreleme
if (!empty($dinamik_filtreler)) {
    foreach ($dinamik_filtreler as $oz_id => $deger) {
        if (!empty($deger)) {
            $sql .= " AND i.id IN (SELECT ilan_id FROM ilan_ozellik_verileri WHERE ozellik_id = ? AND deger LIKE ?)";
            $params[] = (int)$oz_id;
            $params[] = "%$deger%";
        }
    }
}

$sql .= " ORDER BY i.id DESC";
$sorgu = $db->prepare($sql);
$sorgu->execute($params);
$sonuclar = $sorgu->fetchAll();

// Kategori Özelliklerini Çek
$kat_ozellikleri = [];
if ($kat_id > 0) {
    $ozSorgu = $db->prepare("SELECT ot.* FROM kategori_ozellikleri ko JOIN ozellik_tanimlari ot ON ko.ozellik_id = ot.id WHERE ko.kategori_id = ? ORDER BY ko.sira ASC");
    $ozSorgu->execute([$kat_id]);
    $kat_ozellikleri = $ozSorgu->fetchAll();
}
?>

<style>
    :root { --z-yesil: #1b4332; --z-sari: #f39c12; --z-border: #e4e4e4; --z-bg: #fdfdfd; }
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Open Sans', Arial, sans-serif; }
    
    .plus-full-wrapper { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; width: 100%; }

    /* SOL SIDEBAR (Filtre Paneli) */
    .plus-sidebar { background: #fff; border-right: 1px solid var(--z-border); padding: 25px 15px; height: 100%; }
    .sidebar-header { font-size: 14px; font-weight: 800; border-bottom: 2px solid var(--z-sari); padding-bottom: 8px; margin-bottom: 20px; text-transform: uppercase; color: #000; }
    
    .filter-group { margin-bottom: 15px; }
    .filter-group label { display: block; font-size: 10px; font-weight: 800; color: #777; margin-bottom: 5px; text-transform: uppercase; }
    .filter-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; outline: none; background: #fafafa; }
    
    .filter-btn { width: 100%; padding: 12px; background: var(--z-yesil); color:#fff; border:none; border-radius:8px; font-weight:800; cursor:pointer; margin-top: 10px; transition: 0.3s; box-shadow: 0 4px 0 #143226; }
    .filter-btn:active { transform: translateY(2px); box-shadow: 0 2px 0 #143226; }

    /* SAĞ İÇERİK */
    .plus-content-area { padding: 20px 30px; background: var(--z-bg); }
    .search-summary { 
        background: #fff; padding: 15px 20px; border: 1px solid var(--z-border); 
        border-radius: 8px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;
    }

    .plus-ilan-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
    .plus-card { background: #fff; border: 1px solid var(--z-border); padding: 8px; border-radius: 8px; transition: 0.2s; text-decoration: none; color: inherit; }
    .plus-card:hover { border-color: var(--z-sari); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
    
    .plus-img { height: 150px; width: 100%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 6px; }
    .plus-img img { width: 100%; height: 100%; object-fit: cover; }
    
    .plus-fiyat { font-size: 16px; font-weight: 800; color: var(--z-yesil); margin-top: 10px; }
    .plus-baslik { font-size: 12px; color: #333; height: 34px; overflow: hidden; margin-top: 5px; line-height: 1.4; font-weight: 700; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .plus-loc { font-size: 11px; color: #999; margin-top: 8px; border-top: 1px solid #f5f5f5; padding-top: 5px; }

    @media (max-width: 992px) {
        .plus-full-wrapper { grid-template-columns: 1fr; }
        .plus-sidebar { display: none; }
    }
</style>

<div class="plus-full-wrapper">
    <aside class="plus-sidebar">
        <div class="sidebar-header">🛠️ Detaylı Filtre</div>
        <form action="ara.php" method="GET">
            <div class="filter-group">
                <label>Kelime / İlan No</label>
                <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" class="filter-input" placeholder="Ne aramıştınız?">
            </div>

            <div class="filter-group">
                <label>Kategori</label>
                <select name="kategori" class="filter-input" onchange="this.form.submit()">
                    <option value="0">Tüm Kategoriler</option>
                    <?php 
                    function katListe($db, $ust=0, $d=0, $s=0){
                        $sorgu = $db->prepare("SELECT id, adi FROM kategoriler WHERE ust_id = ? AND aktif = 1 ORDER BY sira ASC");
                        $sorgu->execute([$ust]);
                        foreach($sorgu->fetchAll() as $k){
                            echo '<option value="'.$k['id'].'" '.($s == $k['id'] ? 'selected':'').'>'.str_repeat('-', $d).' '.$k['adi'].'</option>';
                            katListe($db, $k['id'], $d+1, $s);
                        }
                    }
                    katListe($db, 0, 0, $kat_id);
                    ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Konum</label>
                <select name="il" id="il_sec" class="filter-input" style="margin-bottom:5px;">
                    <option value="0">İl Seçin</option>
                    <?php 
                    $iller = $db->query("SELECT * FROM iller ORDER BY il_adi ASC")->fetchAll();
                    foreach($iller as $il) echo '<option value="'.$il['id'].'" '.($il_id == $il['id'] ? 'selected':'').'>'.$il['il_adi'].'</option>'; 
                    ?>
                </select>
                <select name="ilce" id="ilce_sec" class="filter-input" <?php echo $il_id > 0 ? '' : 'disabled'; ?>>
                    <option value="0">İlçe Seçin</option>
                    <?php 
                    if($il_id > 0){
                        $ilceler = $db->prepare("SELECT * FROM ilceler WHERE il_id = ? ORDER BY ilce_adi ASC");
                        $ilceler->execute([$il_id]);
                        foreach($ilceler->fetchAll() as $ic) echo '<option value="'.$ic['id'].'" '.($ilce_id == $ic['id'] ? 'selected':'').'>'.$ic['ilce_adi'].'</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Fiyat Aralığı</label>
                <div style="display: flex; gap: 5px;">
                    <input type="number" name="min" value="<?php echo $min > 0 ? $min : ''; ?>" placeholder="Min" class="filter-input">
                    <input type="number" name="max" value="<?php echo $max > 0 ? $max : ''; ?>" placeholder="Max" class="filter-input">
                </div>
            </div>

            <?php foreach ($kat_ozellikleri as $oz): ?>
                <div class="filter-group" style="border-top:1px solid #eee; padding-top:10px;">
                    <label><?php echo mb_strtoupper($oz['ozellik_adi']); ?></label>
                    <?php if ($oz['veri_turu'] == 'liste'): ?>
                        <select name="filtre[<?php echo $oz['id']; ?>]" class="filter-input">
                            <option value="">Tümü</option>
                            <?php 
                            $dizi = explode(',', $oz['liste_icerik']);
                            foreach ($dizi as $item): 
                                $item = trim($item);
                                $secili = (isset($dinamik_filtreler[$oz['id']]) && $dinamik_filtreler[$oz['id']] == $item) ? 'selected' : '';
                                echo "<option value='$item' $secili>$item</option>";
                            endforeach; 
                            ?>
                        </select>
                    <?php else: ?>
                        <input type="text" name="filtre[<?php echo $oz['id']; ?>]" value="<?php echo $dinamik_filtreler[$oz['id']] ?? ''; ?>" class="filter-input">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="filter-btn">SONUÇLARI GÖSTER</button>
            <a href="ara.php" style="display:block; text-align:center; font-size:11px; color:#999; margin-top:10px; text-decoration:none;">Filtreleri Temizle</a>
        </form>
    </aside>

    <main class="plus-content-area">
        <div class="search-summary">
            <div>
                <span style="color: #999;">Sonuç:</span> 
                <strong style="color: var(--z-yesil);"><?php echo count($sonuclar); ?> İlan Bulundu</strong>
            </div>
            <div style="font-size: 12px; font-weight: 700; color: #666;">
                <?php if(!empty($q)) echo '"'.htmlspecialchars($q).'" araması | '; ?>
                Sıralama: Yeni İlanlar
            </div>
        </div>

        <div class="plus-ilan-grid">
            <?php if(count($sonuclar) > 0): foreach($sonuclar as $i): 
                $img = !empty($i['ana_resim']) ? URL."/uploads/ilanlar/".$i['ana_resim'] : URL."/dosyalar/resim/yok.png";
            ?>
                <a href="ilan-detay.php?id=<?php echo $i['id']; ?>" class="plus-card">
                    <div class="plus-img">
                        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($i['baslik']); ?>" loading="lazy">
                    </div>
                    <div class="plus-fiyat"><?php echo number_format($i['fiyat'], 0, ',', '.'); ?> TL</div>
                    <h4 class="plus-baslik"><?php echo htmlspecialchars($i['baslik']); ?></h4>
                    <div class="plus-loc">📍 <?php echo $i['il_adi']; ?> / <?php echo $i['ilce_adi']; ?></div>
                </a>
            <?php endforeach; else: ?>
                <div style="grid-column: 1/-1; padding: 100px 20px; text-align: center; background: #fff; border-radius: 12px; border: 2px dashed #eee;">
                    <div style="font-size: 40px; margin-bottom: 20px;">🚜</div>
                    <h3 style="color: var(--z-yesil); font-weight: 800;">Kriterlere uygun ilan bulunamadı.</h3>
                    <p style="color: #999;">Filtreleri değiştirerek tekrar deneyebilirsiniz.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    $('#il_sec').change(function(){
        var id = $(this).val();
        if(id > 0){
            $('#ilce_sec').prop('disabled', false).html('<option>Yükleniyor...</option>');
            $.post('islem/konum-getir.php', {il_id: id}, function(r){
                $('#ilce_sec').html('<option value="0">Tüm İlçeler</option>' + r);
            });
        } else {
            $('#ilce_sec').prop('disabled', true).html('<option value="0">Tüm İlçeler</option>');
        }
    });
});
</script>

<?php require_once "parcalar/alt.php"; ?>