<?php 
require_once "parcalar/ust.php"; 

// 1. PARAMETRELERİ TEMİZLEYEREK ALALIM
$q        = isset($_GET['ara']) ? trim(strip_tags($_GET['ara'])) : '';
$kat_id   = isset($_GET['kat']) ? intval($_GET['kat']) : 0;
$min      = isset($_GET['min']) ? floatval($_GET['min']) : 0;
$max      = isset($_GET['max']) ? floatval($_GET['max']) : 0;

// 2. SQL SORGU ALTYAPISI
$sql = "SELECT i.*, il.il_adi, ilc.ilce_adi, 
        (SELECT dosya_adi FROM ilan_resimleri WHERE ilan_id = i.id ORDER BY ana_resim DESC LIMIT 1) as ana_resim 
        FROM ilanlar i 
        LEFT JOIN iller il ON (i.il = il.id OR i.il = il.il_adi) 
        LEFT JOIN ilceler ilc ON (i.ilce = ilc.id OR i.ilce = ilc.ilce_adi) 
        WHERE i.durum = 'aktif'";

$params = [];

if (!empty($q)) {
    if (ctype_digit($q)) {
        $sql .= " AND (i.baslik LIKE ? OR i.ilan_no = ?)";
        $params[] = "%$q%"; 
        $params[] = $q;
    } else {
        $sql .= " AND i.baslik LIKE ?";
        $params[] = "%$q%";
    }
}

if ($kat_id > 0) { $sql .= " AND i.kategori_id = ?"; $params[] = $kat_id; }
if ($min > 0)    { $sql .= " AND i.fiyat >= ?"; $params[] = $min; }
if ($max > 0)    { $sql .= " AND i.fiyat <= ?"; $params[] = $max; }

$sql .= " ORDER BY i.id DESC";
$sorgu = $db->prepare($sql);
$sorgu->execute($params);
$ilanlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    :root { 
        --z-green: #1b4332; 
        --z-accent: #27ae60; 
        --z-orange: #f39c12;
        --z-bg: #f8fafc; 
        --z-card-border: #e2e8f0;
    }
    
    body { background-color: var(--z-bg); }

    .page-full-width {
        width: 100% !important;
        max-width: 98% !important; /* Masaüstünde tam ekran alanını maksimuma çıkarır */
        margin: 0 auto !important;
        padding: 0 10px !important;
        box-sizing: border-box !important;
    }

    .ilanlar-wrapper { 
        display: grid; 
        grid-template-columns: 250px 1fr; 
        gap: 20px; 
        margin-top: 20px; 
        margin-bottom: 50px; 
        align-items: start;
    }
    
    /* SOL FİLTRE PANELİ */
    .filter-card { 
        background: #fff; 
        padding: 18px; 
        border-radius: 14px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.03); 
        border: 1px solid var(--z-card-border); 
        position: sticky; 
        top: 20px; 
    }
    
    .filter-card h3 {
        font-size: 14px;
        font-weight: 800;
        color: var(--z-green);
        margin: 0 0 15px 0;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--z-orange);
    }

    .form-group-custom { margin-bottom: 14px; }
    .form-group-custom label { font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 5px; }
    .input-custom { width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; font-size: 12.5px; color: #1e293b; outline: none; box-sizing: border-box; }

    .btn-filter-apply { width: 100%; background: var(--z-green); color: #fff; border: 0; padding: 11px; border-radius: 8px; font-weight: 800; font-size: 12.5px; cursor: pointer; }

    /* 🖥️ MASAÜSTÜ: MASAÜSTÜNDE MKSİMUM İLAN SIĞDIRMA (180px - 200px Kartlar -> 5'li/6'lı Dizilim) */
    .ilan-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); 
        gap: 15px; 
    }
    
    .ilan-card-item { 
        text-decoration: none; 
        color: inherit; 
        background: #fff; 
        border-radius: 12px; 
        overflow: hidden; 
        border: 1px solid var(--z-card-border); 
        transition: all 0.2s ease; 
        display: flex; 
        flex-direction: column; 
    }
    .ilan-card-item:hover { 
        transform: translateY(-3px); 
        box-shadow: 0 8px 18px rgba(0,0,0,0.06); 
        border-color: var(--z-accent); 
    }
    
    .ilan-card-img-wrapper { 
        width: 100%; 
        aspect-ratio: 4 / 3; 
        background: #f1f5f9; 
        overflow: hidden; 
    }
    .ilan-card-img-wrapper img { width: 100%; height: 100%; object-fit: cover; }

    .ilan-card-body { padding: 10px; display: flex; flex-direction: column; flex: 1; justify-content: space-between; }
    .ilan-no-badge { font-size: 10px; color: #64748b; font-weight: 700; margin-bottom: 3px; }
    .ilan-title-text { font-weight: 700; color: #0f172a; height: 34px; overflow: hidden; margin-bottom: 6px; font-size: 12.5px; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    
    .ilan-card-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 6px; margin-top: 4px; }
    .ilan-price-text { font-size: 14px; font-weight: 900; color: var(--z-accent); }
    .ilan-loc-text { font-size: 10.5px; color: #64748b; font-weight: 600; }

    /* 📱 MOBİL GÖRÜNÜM: SATIR SATIR (LISTE) DİZİLİMİ */
    @media (max-width: 768px) {
        .ilanlar-wrapper { grid-template-columns: 1fr; gap: 15px; }
        .filter-card { position: static; }
        
        /* Mobilde Grid Kırılır -> Liste Düzenine Geçer */
        .ilan-grid { display: flex; flex-direction: column; gap: 10px; }
        
        .ilan-card-item { 
            flex-direction: row; /* Yan yana satır düzeni */
            height: 100px;
            align-items: center;
        }
        
        .ilan-card-img-wrapper { 
            width: 120px; 
            height: 100%; 
            flex-shrink: 0;
            aspect-ratio: auto;
        }
        
        .ilan-card-body { 
            padding: 10px; 
            height: 100%;
            box-sizing: border-box;
        }

        .ilan-title-text { 
            height: 32px; 
            font-size: 13px; 
            margin-bottom: 4px;
        }

        .ilan-card-footer { 
            border-top: 0; 
            padding-top: 0; 
            margin-top: 0; 
        }
        
        .ilan-price-text { font-size: 15px; }
    }
</style>

<div class="page-full-width">
    <div class="ilanlar-wrapper">
        
        <!-- SOL FİLTRE PANELİ -->
        <aside>
            <div class="filter-card">
                <h3>🔍 Detaylı Filtreleme</h3>
                
                <form action="ilanlar.php" method="GET">
                    <div class="form-group-custom">
                        <label>Arama / İlan No</label>
                        <input type="text" name="ara" value="<?php echo htmlspecialchars($q); ?>" placeholder="Kelime veya ilan no..." class="input-custom">
                    </div>

                    <div class="form-group-custom">
                        <label>Kategori</label>
                        <select name="kat" class="input-custom">
                            <option value="">Tüm Kategoriler</option>
                            <?php 
                            $kategoriler = $db->query("SELECT id, adi FROM kategoriler WHERE aktif = 1 ORDER BY sira ASC")->fetchAll(PDO::FETCH_ASSOC);
                            foreach($kategoriler as $k) {
                                $secili = ($kat_id == $k['id']) ? 'selected' : '';
                                echo "<option value='".(int)$k['id']."' $secili>".htmlspecialchars($k['adi'])."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group-custom">
                        <label>Fiyat Aralığı (TL)</label>
                        <div style="display: flex; gap: 6px;">
                            <input type="number" name="min" value="<?php echo $min > 0 ? $min : ''; ?>" placeholder="Min" class="input-custom">
                            <input type="number" name="max" value="<?php echo $max > 0 ? $max : ''; ?>" placeholder="Max" class="input-custom">
                        </div>
                    </div>

                    <button type="submit" class="btn-filter-apply">UYGULA</button>
                    <a href="ilanlar.php" style="display: block; text-align: center; margin-top: 10px; color: #94a3b8; font-size: 11.5px; text-decoration: none; font-weight:700;">Temizle</a>
                </form>
            </div>
        </aside>

        <!-- İLAN LİSTESİ -->
        <main>
            <div style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--z-card-border);">
                <span style="font-size: 13px; color: var(--z-green); font-weight: 800;">Toplam <b><?php echo count($ilanlar); ?></b> İlan Bulundu</span>
            </div>

            <div class="ilan-grid">
                <?php if(!empty($ilanlar)): foreach($ilanlar as $ilan): 
                    if (!empty($ilan['ana_resim']) && file_exists("uploads/ilanlar/" . $ilan['ana_resim'])) {
                        $foto = URL . "/uploads/ilanlar/" . $ilan['ana_resim'];
                    } else {
                        $foto = URL . "/dosyalar/resim/yok.png";
                    }
                    $sehir_adi = !empty($ilan['il_adi']) ? $ilan['il_adi'] : (!empty($ilan['il']) ? $ilan['il'] : 'Belirtilmedi');
                ?>
                    <a href="ilan-detay.php?id=<?php echo (int)$ilan['id']; ?>" class="ilan-card-item">
                        <div class="ilan-card-img-wrapper">
                            <img src="<?php echo htmlspecialchars($foto); ?>" alt="<?php echo htmlspecialchars($ilan['baslik']); ?>" loading="lazy">
                        </div>
                        <div class="ilan-card-body">
                            <div>
                                <div class="ilan-no-badge">#<?php echo htmlspecialchars($ilan['ilan_no'] ?? $ilan['id']); ?></div>
                                <div class="ilan-title-text"><?php echo htmlspecialchars($ilan['baslik']); ?></div>
                            </div>
                            <div class="ilan-card-footer">
                                <span class="ilan-price-text"><?php echo number_format($ilan['fiyat'], 0, ',', '.'); ?> TL</span>
                                <span class="ilan-loc-text">📍 <?php echo htmlspecialchars($sehir_adi); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; background: #fff; border-radius: 14px; border: 2px dashed #e2e8f0;">
                        <div style="font-size: 40px; margin-bottom: 10px;">🚜</div>
                        <h3 style="color: var(--z-green); font-weight: 800; margin: 0 0 8px; font-size:15px;">Kriterlere uygun ilan bulunamadı.</h3>
                    </div>
                <?php endif; ?>
            </div>
        </main>

    </div>
</div>

<?php require_once "parcalar/alt.php"; ?>