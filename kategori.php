<?php 
require_once "parcalar/ust.php"; 

/**
 * ZiraatBox - PLUS PREMIUM KATEGORİ v1.0
 * %100 Hizalama Garantili - Sahibinden Nizamı
 */

if (!function_exists('g')) {
    function g($par) {
        return strip_tags(trim($par));
    }
}

// 1. KATEGORİ BİLGİSİNİ ÇEKELİM
$slug = isset($_GET['slug']) ? g($_GET['slug']) : '';
$katSorgu = $db->prepare("SELECT * FROM kategoriler WHERE slug = ? AND aktif = 1");
$katSorgu->execute([$slug]);
$kategori = $katSorgu->fetch();

if (!$kategori) { header("Location: index.php"); exit; }
$kat_id = $kategori['id'];

// Alt Kategori Toplayıcı
if (!function_exists('altKategoriIDleriniGetir')) {
    function altKategoriIDleriniGetir($db, $ust_id) {
        $ids = [(int)$ust_id];
        $sorgu = $db->prepare("SELECT id FROM kategoriler WHERE ust_id = ? AND aktif = 1");
        $sorgu->execute([$ust_id]);
        $altlar = $sorgu->fetchAll(PDO::FETCH_COLUMN);
        foreach ($altlar as $id) { $ids = array_merge($ids, altKategoriIDleriniGetir($db, $id)); }
        return array_unique($ids);
    }
}

// Soyağacı Yolu
function katSoyagaciYolu($db, $current_id) {
    $yol = [];
    $temp_id = $current_id;
    while ($temp_id > 0) {
        $s = $db->prepare("SELECT id, adi, slug, ust_id FROM kategoriler WHERE id = ?");
        $s->execute([$temp_id]);
        $k = $s->fetch();
        if ($k) {
            array_unshift($yol, '<a href="kategori.php?slug='.$k['slug'].'" style="color: #27ae60; text-decoration: none; font-weight: 700;">'.$k['adi'].'</a>');
            $temp_id = $k['ust_id'];
        } else { break; }
    }
    return implode(' <span style="color: #cbd5e0;">&gt;</span> ', $yol);
}

$kategori_havuzu = altKategoriIDleriniGetir($db, $kat_id);
$in_query = implode(',', array_fill(0, count($kategori_havuzu), '?'));
?>

<style>
    /* 🛠️ KATEGORİ SAYFASI PLUS TASARIM SİSTEMİ */
    :root {
        --z-yesil: #1b4332;
        --z-sari: #f39c12;
        --z-border: #e4e4e4;
        --z-bg: #fdfdfd;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Open Sans', Arial, sans-serif; }
    
    /* İŞARETLEDİĞİNİZ BOŞLUĞU YOK EDEN ANA YAPI */
    .plus-full-wrapper {
        display: grid;
        grid-template-columns: 280px 1fr; /* Index.php ile aynı nizam */
        min-height: 100vh;
        width: 100%;
    }

    /* SOL MENÜ (Sidebar Yaslı) */
    .plus-sidebar { 
        background: #fff; 
        border-right: 1px solid var(--z-border); 
        padding: 25px 15px; 
        height: 100%;
    }
    .plus-sidebar h3 { font-size: 14px; font-weight: 800; color: #000; margin-bottom: 15px; border-bottom: 2px solid var(--z-sari); padding-bottom: 8px; text-transform: uppercase; }
    .plus-side-list { list-style: none; }
    .plus-side-list li { margin-bottom: 10px; }
    .plus-side-list a { display: flex; justify-content: space-between; font-size: 13px; color: #333; font-weight: 600; padding: 5px 0; }
    .plus-side-list a:hover { color: var(--z-yesil); text-decoration: underline; }

    /* SAĞ İÇERİK ALANI */
    .plus-content-area {
        padding: 25px 30px;
        background: var(--z-bg);
    }

    /* BREADCRUMB */
    .breadcrumb-box {
        background: #fff;
        padding: 15px 20px;
        border: 1px solid var(--z-border);
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 12px;
    }

    /* İLAN VİTRİNİ BAŞLIK */
    .plus-vitrin-header {
        display: flex; justify-content: space-between; align-items: center;
        background: #fff; padding: 12px 20px; border: 1px solid var(--z-border);
        border-bottom: 3px solid var(--z-sari); font-weight: 800; font-size: 15px;
        margin-bottom: 20px; border-radius: 4px;
    }

    /* SAHİBİNDEN TIPI KARE İLANLAR */
    .plus-ilan-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
        gap: 15px;
    }

    .plus-card {
        background: #fff;
        border: 1px solid var(--z-border);
        padding: 8px;
        transition: 0.2s;
        border-radius: 4px;
        display: flex;
        flex-direction: column;
    }
    .plus-card:hover { border-color: var(--z-sari); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }

    .plus-img { height: 140px; width: 100%; background: #f9f9f9; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .plus-img img { width: 100%; height: 100%; object-fit: cover; }

    .plus-fiyat { font-size: 15px; font-weight: 800; color: var(--z-yesil); margin-top: 10px; }
    .plus-baslik { font-size: 11.5px; color: #333; height: 32px; overflow: hidden; margin-top: 5px; line-height: 1.3; font-weight: 700; }
    .plus-loc { font-size: 10px; color: #999; margin-top: 8px; border-top: 1px solid #f5f5f5; padding-top: 5px; }

    @media (max-width: 992px) {
        .plus-full-wrapper { grid-template-columns: 1fr; }
        .plus-sidebar { display: none; }
        .plus-content-area { padding: 15px; }
        .plus-ilan-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
    }
</style>

<div class="plus-full-wrapper">
    <aside class="plus-sidebar">
        <h3>Alt Kategoriler</h3>
        <ul class="plus-side-list">
            <?php
            $altKatlar = $db->prepare("SELECT id, adi, slug FROM kategoriler WHERE ust_id = ? AND aktif = 1 ORDER BY sira ASC");
            $altKatlar->execute([$kat_id]);
            $liste = $altKatlar->fetchAll();
            
            if($liste):
                foreach($liste as $lk):
                    $say = $db->prepare("SELECT COUNT(id) FROM ilanlar WHERE kategori_id = ? AND durum = 'aktif'");
                    $say->execute([$lk['id']]);
                    echo '<li><a href="kategori.php?slug='.$lk['slug'].'"><span>'.$lk['adi'].'</span><span style="color:#999; font-size:11px;">('.$say->fetchColumn().')</span></a></li>';
                endforeach;
            else:
                echo '<p style="font-size:12px; color:#999;">Bu kategori en alt seviyededir.</p>';
            endif;
            ?>
        </ul>

        <div style="margin-top: 30px; padding: 15px; background: #f9f9f9; border-radius: 8px; border: 1px solid #eee;">
            <a href="ilan-ver.php" style="display: block; text-align: center; background: #27ae60; color: #fff; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: 700; font-size: 13px;">Hızlı İlan Ver</a>
        </div>
    </aside>

    <main class="plus-content-area">
        <div class="breadcrumb-box">
            🏠 <a href="index.php" style="color: #94a3b8;">Anasayfa</a> 
            <span style="color: #cbd5e0;">&gt;</span> 
            <?php echo katSoyagaciYolu($db, $kategori['id']); ?>
        </div>

        <div class="plus-vitrin-header">
            <span>🚜 <?php echo htmlspecialchars($kategori['adi']); ?> İlanları</span>
        </div>

        <div class="plus-ilan-grid">
            <?php
            $ilan_sorgu = $db->prepare("SELECT i.*, il.il_adi, 
                (SELECT dosya_adi FROM ilan_resimleri WHERE ilan_id = i.id ORDER BY ana_resim DESC LIMIT 1) as resim 
                FROM ilanlar i 
                LEFT JOIN iller il ON i.il = il.id
                WHERE i.durum = 'aktif' AND i.kategori_id IN ($in_query) 
                ORDER BY i.id DESC");
            
            $ilan_sorgu->execute($kategori_havuzu);
            $ilanlar = $ilan_sorgu->fetchAll();

            if (count($ilanlar) > 0):
                foreach ($ilanlar as $ilan): 
                    $res = !empty($ilan['resim']) ? URL."/uploads/ilanlar/".$ilan['resim'] : URL."/dosyalar/resim/yok.png";
            ?>
            <a href="ilan-detay.php?id=<?php echo $ilan['id']; ?>" class="plus-card">
                <div class="plus-img">
                    <img src="<?php echo $res; ?>" alt="<?php echo htmlspecialchars($ilan['baslik']); ?>" loading="lazy">
                </div>
                <div class="plus-fiyat"><?php echo number_format($ilan['fiyat'], 0, ',', '.'); ?> TL</div>
                <h4 class="plus-baslik"><?php echo htmlspecialchars($ilan['baslik']); ?></h4>
                <div class="plus-loc">📍 <?php echo $ilan['il_adi']; ?></div>
            </a>
            <?php endforeach; else: ?>
                <div style="grid-column: 1 / -1; padding: 60px; text-align: center; background: #fff; border-radius: 12px; border: 2px dashed #eee; color: #999;">
                    <span style="font-size: 40px; display: block; margin-bottom: 10px;">🚜</span>
                    <b>Bu kategoride henüz ilan bulunmuyor.</b>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once "parcalar/alt.php"; ?>