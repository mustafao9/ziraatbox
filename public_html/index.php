<?php
// Hata raporlama
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 
require_once "parcalar/ust.php"; 

/**
 * ZiraatBox - PLUS PREMIUM v21.5
 * Veritabanı Şemasına %100 Uyumlu, Performans & Güvenlik Onarımlı
 */

// 1. TEK SORGUDA TÜM KATEGORİ HARİTASINI BELLEĞE ALMA (N+1 Sorgu Engellendi)
if (!function_exists('kategoriHaritasiGetir')) {
    function kategoriHaritasiGetir($db) {
        static $harita = null;
        if ($harita === null) {
            $sorgu = $db->query("SELECT id, ust_id, adi, slug, sira FROM kategoriler WHERE aktif = 1 ORDER BY sira ASC");
            $tum_katlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
            $harita = ['tum' => [], 'ust_baglanti' => []];
            foreach ($tum_katlar as $k) {
                $harita['tum'][$k['id']] = $k;
                $harita['ust_baglanti'][$k['ust_id']][] = $k['id'];
            }
        }
        return $harita;
    }
}

if (!function_exists('altKategoriIDleriniGetirHizli')) {
    function altKategoriIDleriniGetirHizli($harita, $ust_id) {
        $ids = [(int)$ust_id];
        if (isset($harita['ust_baglanti'][$ust_id])) {
            foreach ($harita['ust_baglanti'][$ust_id] as $alt_id) {
                $ids = array_merge($ids, altKategoriIDleriniGetirHizli($harita, $alt_id));
            }
        }
        return array_unique($ids);
    }
}

$kat_haritasi = kategoriHaritasiGetir($db);
?>

<style>
    :root {
        --z-yesil: #1b4332;
        --z-sari: #f39c12;
        --z-border: #e4e4e4;
        --z-bg: #fdfdfd;
        --z-beyaz: #ffffff;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Open Sans', Arial, sans-serif; }
    body { background: var(--z-bg); color: #333; overflow-x: hidden; }
    
    .plus-full-wrapper {
        display: grid;
        grid-template-columns: 280px 1fr;
        min-height: 100vh;
        width: 100%;
    }

    .plus-sidebar { 
        background: var(--z-beyaz); 
        border-right: 1px solid var(--z-border); 
        padding: 25px 15px; 
        height: 100%;
    }
    .sidebar-section-title { font-size: 13px; font-weight: 800; color: #000; margin-bottom: 12px; border-bottom: 2px solid var(--z-sari); padding-bottom: 8px; text-transform: uppercase; }
    .plus-side-list { list-style: none; margin-bottom: 30px; }
    .plus-side-list a { display: flex; justify-content: space-between; font-size: 12.5px; color: #444; font-weight: 600; padding: 4px 0; text-decoration: none; transition: 0.2s; }
    .plus-count { color: #999; font-size: 11px; font-weight: 400; }

    .filter-box { background: #fff; border-radius: 12px; border: 1px solid #e4e4e4; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.04); position: sticky; top: 20px; }
    .filter-header { background: var(--z-yesil); padding: 12px 15px; color: #fff; font-size: 12px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
    .filter-form { padding: 15px; }
    .filter-form select, .filter-form input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; outline: none; background: #fafafa; margin-bottom: 10px; }
    .filter-btn { width: 100%; padding: 12px; background: var(--z-sari); color:#fff; border:none; border-radius:8px; font-weight:800; cursor:pointer; font-size:12px; transition: 0.3s; box-shadow: 0 4px 0 #d38312; }

    .plus-content-area { padding: 20px 30px; background: var(--z-bg); }
    .plus-smart-search-box {
        background: #fff; border: 2px solid var(--z-yesil); border-radius: 8px;
        display: flex; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden;
    }
    .plus-smart-search-box input { flex: 1; border: none; padding: 15px 20px; font-size: 15px; outline: none; }
    .plus-smart-search-box button { background: var(--z-yesil); color: #fff; border: none; padding: 0 35px; font-weight: 800; cursor: pointer; }

    .plus-vitrin-header {
        display: flex; justify-content: space-between; align-items: center;
        background: #fff; padding: 12px 20px; border: 1px solid var(--z-border);
        border-bottom: 3px solid var(--z-sari); font-weight: 800; font-size: 15px;
        margin-bottom: 20px; border-radius: 4px;
    }

    /* 🖥️ MASAÜSTÜ GRID */
    @media (min-width: 993px) {
        .plus-ilan-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .plus-card { background: #fff; border: 1px solid var(--z-border); padding: 8px; transition: 0.2s; border-radius: 8px; text-decoration: none; color: inherit; display: flex; flex-direction: column; }
        .plus-img { height: 150px; width: 100%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 6px; }
        .plus-img img { width: 100%; height: 100%; object-fit: cover; }
        .plus-fiyat { font-size: 16px; font-weight: 800; color: var(--z-yesil); margin-top: 10px; }
        .plus-baslik { font-size: 12px; color: #333; height: 34px; overflow: hidden; margin-top: 5px; line-height: 1.4; font-weight: 700; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .plus-loc { font-size: 11px; color: #999; margin-top: 8px; border-top: 1px solid #f5f5f5; padding-top: 5px; }
        .mobile-category-list { display: none; }
    }

    /* 📱 MOBİL LİSTE */
    @media (max-width: 992px) {
        .plus-full-wrapper { grid-template-columns: 1fr; }
        .plus-sidebar { display: none; }
        .plus-content-area { padding: 10px; }
        
        .mobile-category-list { display: block; background: #fff; border-bottom: 1px solid #eee; margin-bottom: 15px; }
        .mobile-cat-item { display: flex; align-items: center; padding: 12px 15px; border-bottom: 1px solid #f8f8f8; text-decoration: none; color: #333; }
        .cat-icon-circle { width: 32px; height: 32px; border-radius: 50%; background: #f0fdf4; color: var(--z-yesil); display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 14px; border: 1px solid #dcfce7; }
        .cat-name-text { flex: 1; font-size: 14px; font-weight: 700; }

        .plus-ilan-grid { display: flex; flex-direction: column; gap: 0; }
        .plus-card { 
            display: flex; flex-direction: row; padding: 12px; border: none; border-bottom: 1px solid #f0f0f0; 
            border-radius: 0; align-items: center; gap: 12px; background: #fff; text-decoration: none; color: inherit;
        }
        .plus-img { 
            width: 100px; 
            height: 80px; 
            flex-shrink: 0; 
            background: #f8fafc; 
            border-radius: 6px; 
            overflow: hidden;
            border: 1px solid #f0f0f0;
        }
        .plus-img img { 
            width: 100%; 
            height: 100%; 
            object-fit: contain; 
        }
        
        .plus-card-right { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .plus-fiyat { font-size: 15px; font-weight: 800; color: #2b6cb0; margin-bottom: 2px; }
        .plus-baslik { font-size: 13px; font-weight: 700; color: #4a5568; line-height: 1.3; height: auto; -webkit-line-clamp: 2; display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden; }
        .plus-loc { font-size: 10px; color: #a0aec0; margin-top: 5px; border: none; padding: 0; display: flex; justify-content: space-between; }
        .m-date { display: inline !important; }
    }
</style>

<div class="plus-full-wrapper">
    
    <aside class="plus-sidebar">
        <h3 class="sidebar-section-title">Hızlı Gezinti</h3>
        <ul class="plus-side-list">
            <?php
            $side_kat_ids = $kat_haritasi['ust_baglanti'][0] ?? [];
            $side_kat = [];
            foreach (array_slice($side_kat_ids, 0, 15) as $kid) {
                $side_kat[] = $kat_haritasi['tum'][$kid];
            }

            foreach($side_kat as $sk):
                $kat_havuzu = altKategoriIDleriniGetirHizli($kat_haritasi, $sk['id']);
                $in_q = implode(',', array_fill(0, count($kat_havuzu), '?'));
                $say_sorgu = $db->prepare("SELECT COUNT(id) FROM ilanlar WHERE kategori_id IN ($in_q) AND durum = 'aktif'");
                $say_sorgu->execute($kat_havuzu);
                $sayi = $say_sorgu->fetchColumn();
            ?>
            <li>
                <a href="kategori.php?slug=<?php echo htmlspecialchars($sk['slug']); ?>">
                    <span><?php echo htmlspecialchars($sk['adi']); ?></span>
                    <span class="plus-count">(<?php echo $sayi; ?>)</span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="filter-box">
            <div class="filter-header">🔎 DETAYLI FİLTRELEME</div>
            <form action="ara.php" method="GET" class="filter-form">
                <div class="filter-group">
                    <label>📁 Kategori</label>
                    <select name="kategori">
                        <option value="0">Tümü</option>
                        <?php 
                        function hiyerarsikFiltreListesiOptimum($harita, $ust_id = 0, $derinlik = 0) {
                            if (isset($harita['ust_baglanti'][$ust_id])) {
                                foreach ($harita['ust_baglanti'][$ust_id] as $kat_id) {
                                    $kat = $harita['tum'][$kat_id];
                                    $prefix = str_repeat('&nbsp;', $derinlik * 3) . ($derinlik == 0 ? '' : '└ ');
                                    echo '<option value="'.(int)$kat['id'].'">'.$prefix.htmlspecialchars($kat['adi']).'</option>';
                                    hiyerarsikFiltreListesiOptimum($harita, $kat['id'], $derinlik + 1);
                                }
                            }
                        }
                        hiyerarsikFiltreListesiOptimum($kat_haritasi); 
                        ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>📍 Şehir</label>
                    <select name="il">
                        <option value="0">Tüm Türkiye</option>
                        <?php 
                        $iller = $db->query("SELECT id, il_adi FROM iller ORDER BY il_adi ASC")->fetchAll(PDO::FETCH_ASSOC);
                        foreach($iller as $il) echo '<option value="'.(int)$il['id'].'">'.htmlspecialchars($il['il_adi']).'</option>';
                        ?>
                    </select>
                </div>
                <button type="submit" class="filter-btn">İLANLARI FİLTRELE</button>
            </form>
        </div>
    </aside>

    <main class="plus-content-area">
        
        <div class="mobile-category-list">
            <?php foreach(array_slice($side_kat, 0, 6) as $sk): ?>
            <a href="kategori.php?slug=<?php echo htmlspecialchars($sk['slug']); ?>" class="mobile-cat-item">
                <div class="cat-icon-circle"><i class="fa-solid fa-layer-group"></i></div>
                <div class="cat-name-text"><?php echo htmlspecialchars($sk['adi']); ?></div>
                <div class="cat-arrow-right">›</div>
            </a>
            <?php endforeach; ?>
        </div>

        <form action="ara.php" method="GET" class="plus-smart-search-box">
            <input type="text" name="q" placeholder="Ürün veya ilan no ara..." required>
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>

        <div class="plus-vitrin-header">
            <span>🌟 Vitrin İlanları</span>
            <a href="ara.php" style="color:var(--z-yesil); font-size:11px;">Tümü ➔</a>
        </div>

        <div class="plus-ilan-grid">
            <?php
            // SQL Şemasına Tam Uyumlu Sorgu (created_at ve il tablosu eşleşmesi)
            $ilanlar = $db->query("
                SELECT i.id, i.baslik, i.fiyat, i.created_at, i.il as il_id, il.il_adi, 
                (SELECT dosya_adi FROM ilan_resimleri WHERE ilan_id = i.id ORDER BY ana_resim DESC LIMIT 1) as ana_resim 
                FROM ilanlar i 
                LEFT JOIN iller il ON (i.il = il.id OR i.il = il.il_adi) 
                WHERE i.durum = 'aktif' 
                ORDER BY i.id DESC LIMIT 48
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($ilanlar as $ilan):
                // Standart resim yolu (uploads/ilanlar/)
                if (!empty($ilan['ana_resim']) && file_exists("uploads/ilanlar/" . $ilan['ana_resim'])) {
                    $resim = "uploads/ilanlar/" . $ilan['ana_resim'];
                } else {
                    $resim = "dosyalar/resim/yok.png";
                }

                $tarih_ham = $ilan['created_at'] ?? date('Y-m-d');
                $tarih = date("d.m.Y", strtotime($tarih_ham));
                $sehir_adi = !empty($ilan['il_adi']) ? $ilan['il_adi'] : (!empty($ilan['il_id']) ? $ilan['il_id'] : 'Belirtilmedi');
            ?>
            <a href="ilan-detay.php?id=<?php echo (int)$ilan['id']; ?>" class="plus-card">
                <div class="plus-img">
                    <img src="<?php echo htmlspecialchars($resim); ?>" alt="<?php echo htmlspecialchars($ilan['baslik']); ?>" loading="lazy">
                </div>
                
                <div class="plus-card-right"> 
                    <div class="plus-fiyat"><?php echo number_format($ilan['fiyat'], 0, ',', '.'); ?> TL</div>
                    <h4 class="plus-baslik"><?php echo htmlspecialchars($ilan['baslik']); ?></h4>
                    <div class="plus-loc">
                        <span>📍 <?php echo htmlspecialchars($sehir_adi); ?></span>
                        <span class="m-date" style="display:none;">📅 <?php echo $tarih; ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php require_once "parcalar/alt.php"; ?>
