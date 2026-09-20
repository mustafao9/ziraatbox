<?php 
require_once "parcalar/ust.php"; 

// 1. PARAMETRELERİ TEMİZLEYEREK ALALIM
$q        = isset($_GET['ara']) ? g($_GET['ara']) : '';
$kat_id   = isset($_GET['kat']) ? intval($_GET['kat']) : 0;
$min      = isset($_GET['min']) ? floatval($_GET['min']) : 0;
$max      = isset($_GET['max']) ? floatval($_GET['max']) : 0;

// 2. SQL SORGU ALTYAPISI
$sql = "SELECT i.*, il.il_adi, ilc.ilce_adi, 
        (SELECT dosya_adi FROM ilan_resimleri WHERE ilan_id = i.id ORDER BY ana_resim DESC LIMIT 1) as ana_resim 
        FROM ilanlar i 
        LEFT JOIN iller il ON i.il = il.id 
        LEFT JOIN ilceler ilc ON i.ilce = ilc.id 
        WHERE i.durum = 'aktif'";

$params = [];

// Akıllı Arama (Kelime veya İlan No)
if (!empty($q)) {
    if (is_numeric($q)) {
        $sql .= " AND (i.baslik LIKE ? OR i.ilan_no = ?)";
        $params[] = "%$q%"; $params[] = $q;
    } else {
        $sql .= " AND i.baslik LIKE ?";
        $params[] = "%$q%";
    }
}

// Filtreler
if ($kat_id > 0) { $sql .= " AND i.kategori_id = ?"; $params[] = $kat_id; }
if ($min > 0)    { $sql .= " AND i.fiyat >= ?"; $params[] = $min; }
if ($max > 0)    { $sql .= " AND i.fiyat <= ?"; $params[] = $max; }

$sql .= " ORDER BY i.id DESC";
$sorgu = $db->prepare($sql);
$sorgu->execute($params);
$ilanlar = $sorgu->fetchAll();
?>

<div class="konteynir" style="margin-top: 30px;">
    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
        
        <aside style="flex: 1; min-width: 280px;">
            <div style="background: #fff; padding: 25px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #f0f4f2; position: sticky; top: 20px;">
                <h3 style="color: #1b4332; font-weight: 900; margin-bottom: 20px; border-bottom: 2px solid #27ae60; padding-bottom: 10px;">🔍 Detaylı Ara</h3>
                
                <form action="ilanlar.php" method="GET">
                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 12px; font-weight: 800; color: #718096; text-transform: uppercase;">Kelime veya İlan No</label>
                        <input type="text" name="ara" value="<?php echo htmlspecialchars($q); ?>" placeholder="Ne aramıştınız?" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #edf2f7; background: #f8fafc; margin-top: 8px;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 12px; font-weight: 800; color: #718096; text-transform: uppercase;">Kategori</label>
                        <select name="kat" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #edf2f7; background: #f8fafc; margin-top: 8px;">
                            <option value="">Tümü</option>
                            <?php 
                            $kategoriler = $db->query("SELECT id, adi FROM kategoriler WHERE aktif = 1 ORDER BY sira ASC")->fetchAll();
                            foreach($kategoriler as $k) {
                                $secili = ($kat_id == $k['id']) ? 'selected' : '';
                                echo "<option value='{$k['id']}' $secili>{$k['adi']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 12px; font-weight: 800; color: #718096; text-transform: uppercase;">Fiyat Aralığı</label>
                        <div style="display: flex; gap: 10px; margin-top: 8px;">
                            <input type="number" name="min" value="<?php echo $min > 0 ? $min : ''; ?>" placeholder="Min" style="width: 50%; padding: 10px; border-radius: 8px; border: 1px solid #edf2f7;">
                            <input type="number" name="max" value="<?php echo $max > 0 ? $max : ''; ?>" placeholder="Max" style="width: 50%; padding: 10px; border-radius: 8px; border: 1px solid #edf2f7;">
                        </div>
                    </div>

                    <button type="submit" style="width: 100%; background: #27ae60; color: #fff; border: 0; padding: 15px; border-radius: 12px; font-weight: 900; cursor: pointer; transition: 0.3s;">FİLTRELERİ UYGULA</button>
                    <a href="ilanlar.php" style="display: block; text-align: center; margin-top: 15px; color: #94a3b8; font-size: 13px; text-decoration: none;">Sıfırla</a>
                </form>
            </div>
        </aside>

        <section style="flex: 3; min-width: 350px;">
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 22px; color: #1b4332; font-weight: 900;"><?php echo count($ilanlar); ?> İlan Listeleniyor</h2>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px;">
                <?php if($ilanlar): foreach($ilanlar as $ilan): 
                    $foto = !empty($ilan['ana_resim']) ? URL."/yuklemeler/ilanlar/".$ilan['ana_resim'] : URL."/dosyalar/resim/yok.png";
                ?>
                    <a href="ilan-detay.php?id=<?php echo $ilan['id']; ?>" style="text-decoration: none; color: inherit; background: #fff; border-radius: 20px; overflow: hidden; border: 1px solid #f0f4f2; transition: 0.3s; display: block;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                        <img src="<?php echo $foto; ?>" style="width: 100%; height: 180px; object-fit: cover;">
                        <div style="padding: 15px;">
                            <div style="font-size: 11px; color: #27ae60; font-weight: 800; margin-bottom: 5px;">#<?php echo $ilan['ilan_no']; ?></div>
                            <div style="font-weight: 700; color: #1b4332; height: 40px; overflow: hidden; margin-bottom: 10px;"><?php echo $ilan['baslik']; ?></div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 18px; font-weight: 900; color: #27ae60;"><?php echo number_format($ilan['fiyat'], 0, ',', '.'); ?> TL</span>
                                <span style="font-size: 11px; color: #94a3b8;">📍 <?php echo $ilan['il_adi']; ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 100px; background: #fff; border-radius: 20px; border: 2px dashed #edf2f7;">
                        <div style="font-size: 50px;">🚜</div>
                        <h3 style="color: #1b4332; font-weight: 900; margin-top: 20px;">Kriterlere uygun ilan bulunamadı.</h3>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php require_once "parcalar/alt.php"; ?>