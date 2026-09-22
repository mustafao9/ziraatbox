<?php 
require_once "../sistem/ayar.php"; 

/**
 * 🛡️ GÜVENLİK VE YARDIMCI FONKSİYONLAR
 * Eğer sistem genelinde tanımlı değilse burada tanımlıyoruz.
 */
if (!function_exists('g')) {
    function g($par) {
        return strip_tags(trim($par));
    }
}

// sef_link fonksiyonu sistemde yoksa hata vermemesi için basit bir yedeği
if (!function_exists('sef_link')) {
    function sef_link($str) {
        $preg = array('Ç', 'Ş', 'Ğ', 'Ü', 'İ', 'Ö', 'ç', 'ş', 'ğ', 'ü', 'ö', 'ı');
        $repl = array('c', 's', 'g', 'u', 'i', 'o', 'c', 's', 'g', 'u', 'o', 'i');
        $str = str_replace($preg, $repl, $str);
        $str = mb_strtolower($str, 'UTF-8');
        $str = preg_replace('/[^a-z0-9]/', '-', $str);
        $str = preg_replace('/-+/', '-', $str);
        return trim($str, '-');
    }
}

// 1. ADIM: YETKİ VE OTURUM KONTROLÜ
if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    header("Location: giris.php"); exit;
}

// Sidebar bildirimleri
$onay_bekleyen = $db->query("SELECT COUNT(*) FROM ilanlar WHERE durum = 'beklemede'")->fetchColumn();
$yeni_mesaj    = $db->query("SELECT COUNT(*) FROM mesajlar WHERE okundu = 0")->fetchColumn();

/**
 * 🌳 REKÜRSİF SELECT FONKSİYONU
 */
function adminKategoriSelect($db, $ust_id = 0, $derinlik = 0, $secili = 0) {
    $sorgu = $db->prepare("SELECT id, adi FROM kategoriler WHERE ust_id = ? ORDER BY sira ASC, adi ASC");
    $sorgu->execute([$ust_id]);
    foreach($sorgu->fetchAll() as $kat) {
        $sel = ($kat['id'] == $secili) ? 'selected' : '';
        $girinti = str_repeat('&nbsp;&nbsp;&nbsp;', $derinlik);
        $simge = ($derinlik == 0) ? "📂 " : "└─ ";
        echo '<option value="'.$kat['id'].'" '.$sel.'>'.$girinti.$simge.$kat['adi'].'</option>';
        adminKategoriSelect($db, $kat['id'], $derinlik + 1, $secili);
    }
}

// --- GÜNCELLEME VEYA EKLEME İŞLEMİ ---
if(isset($_POST['kategori_kaydet'])){
    $id     = intval($_POST['kategori_id']); 
    $ust_id = intval($_POST['ust_id']);
    $adi    = g($_POST['adi']);
    $ikon   = g($_POST['ikon']);
    $sira   = intval($_POST['sira']);
    $slug   = sef_link($adi);

    if($id > 0){
        $islem = $db->prepare("UPDATE kategoriler SET ust_id = ?, adi = ?, ikon = ?, slug = ?, sira = ? WHERE id = ?");
        $islem->execute([$ust_id, $adi, $ikon, $slug, $sira, $id]);
        $mesaj = "guncellendi";
    } else {
        $islem = $db->prepare("INSERT INTO kategoriler SET ust_id = ?, adi = ?, ikon = ?, slug = ?, sira = ?");
        $islem->execute([$ust_id, $adi, $ikon, $slug, $sira]);
        $id = $db->lastInsertId();
        $mesaj = "eklendi";
    }

    // Teknik Özellik Bağlantıları
    $db->prepare("DELETE FROM kategori_ozellikleri WHERE kategori_id = ?")->execute([$id]);
    if(isset($_POST['kat_ozellikler']) && is_array($_POST['kat_ozellikler'])){
        $oz_bagla = $db->prepare("INSERT INTO kategori_ozellikleri (kategori_id, ozellik_id) VALUES (?, ?)");
        foreach($_POST['kat_ozellikler'] as $oz_id){
            $oz_bagla->execute([$id, intval($oz_id)]);
        }
    }
    header("Location: kategoriler.php?durum=$mesaj"); exit;
}

// --- SİLME İŞLEMİ ---
if(isset($_GET['sil'])){
    $sil_id = intval($_GET['sil']);
    $stmt_alt = $db->prepare("SELECT id FROM kategoriler WHERE ust_id = ? LIMIT 1"); $stmt_alt->execute([(int)$sil_id]); $alt_var_mi = $stmt_alt->fetch();
    if($alt_var_mi){
        header("Location: kategoriler.php?durum=hata_alt_var"); exit;
    }
    $db->prepare("DELETE FROM kategoriler WHERE id = ?")->execute([$sil_id]);
    header("Location: kategoriler.php?durum=silindi"); exit;
}

// --- DÜZENLENECEK VERİYİ ÇEK ---
$duzenle_id = isset($_GET['duzenle']) ? intval($_GET['duzenle']) : 0;
$duzenle_veri = ['id'=>0, 'ust_id'=>0, 'adi'=>'', 'ikon'=>'', 'sira'=>0];
$bagli_ozellikler = [];

if($duzenle_id > 0){
    $stmt_duz = $db->prepare("SELECT * FROM kategoriler WHERE id = ?"); $stmt_duz->execute([(int)$duzenle_id]); $duzenle_veri = $stmt_duz->fetch();
    $stmt_goz = $db->prepare("SELECT ozellik_id FROM kategori_ozellikleri WHERE kategori_id = ?"); $stmt_goz->execute([(int)$duzenle_id]); $bagli_ozellikler = $stmt_goz->fetchAll(PDO::FETCH_COLUMN);
}

$ozellik_havuzu = $db->query("SELECT * FROM ozellik_tanimlari ORDER BY ozellik_adi ASC")->fetchAll();

/**
 * 📋 LİSTELEME İÇİN REKÜRSİF FONKSİYON
 */
function adminKategoriTabloListele($db, $ust_id = 0, $derinlik = 0) {
    $sorgu = $db->prepare("SELECT * FROM kategoriler WHERE ust_id = ? ORDER BY sira ASC, adi ASC");
    $sorgu->execute([$ust_id]);
    foreach($sorgu->fetchAll() as $kat) {
        $girinti = str_repeat('— ', $derinlik);
        $bold = ($derinlik == 0) ? 'style="font-weight:800; color:#1b4332;"' : '';
        echo '<tr>
                <td>'.$kat['sira'].'</td>
                <td style="font-size:20px;">'.($kat['ikon'] ?: '—').'</td>
                <td '.$bold.'>'.$girinti . htmlspecialchars($kat['adi']).'</td>
                <td style="text-align:right;">
                    <a href="?duzenle='.$kat['id'].'" class="btn-duzenle">📝 Düzenle</a>
                    <a href="?sil='.$kat['id'].'" onclick="return confirm(\'Bu kategoriyi silmek istediğinize emin misiniz?\')" class="btn-sil">❌ Sil</a>
                </td>
              </tr>';
        adminKategoriTabloListele($db, $kat['id'], $derinlik + 1);
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kategori Yönetimi | ZiraatBox</title>
    <style>
        :root { --admin-dark: #1a202c; --admin-green: #27ae60; --admin-orange: #ed8936; --admin-blue: #3182ce; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7f6; display: flex; }
        .sidebar { width: 260px; background: var(--admin-dark); color: #fff; min-height: 100vh; padding: 20px; box-sizing: border-box; position: sticky; top: 0; }
        .view-site-btn { display: flex; align-items: center; justify-content: center; background: var(--admin-green); color: #fff; text-decoration: none; padding: 12px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; font-size: 14px; transition: 0.3s; border: 1px solid #219150; }
        .sidebar a { display: block; color: #cbd5e0; padding: 12px; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a.active { background: #2d3748; color: #fff; }
        .content { flex: 1; padding: 30px; box-sizing: border-box; }
        .card { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .form-input { width: 100%; padding: 12px; border: 2px solid #edf2f7; border-radius: 10px; margin-bottom: 15px; box-sizing: border-box; outline: none; }
        .btn-kaydet { background: var(--admin-green); color: #fff; border: 0; padding: 15px 30px; border-radius: 10px; font-weight: 800; cursor: pointer; width: 100%; transition: 0.3s; }
        .btn-kaydet:hover { background: #219150; }
        .ozellik-box { background: #f8fafc; border: 2px solid #edf2f7; border-radius: 12px; padding: 15px; max-height: 250px; overflow-y: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .ozellik-item { display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; padding: 8px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 15px; overflow: hidden; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; font-size: 13px; border-bottom: 2px solid #edf2f7; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; }
        .btn-duzenle { color: var(--admin-blue); text-decoration: none; font-weight: 700; margin-right: 15px; }
        .btn-sil { color: #f56565; text-decoration: none; font-weight: 700; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; text-align: center; }
        .alert-success { background: #c6f6d5; color: #22543d; }
        .alert-error { background: #fed7d7; color: #822727; }
    </style>
</head>
<body>
<?php include "sol_menu.php"; ?>

<div class="content">
    <div style="margin-bottom: 35px;">
        <h1 style="margin: 0; color: #1a202c; font-size: 32px; font-weight: 800;">📂 Kategori Yönetimi</h1>
        <p style="margin: 5px 0 0; color: #718096;">Dede-Baba-Torun hiyerarşisiyle sınırsız kategori oluşturun.</p>
    </div>

    <?php if(isset($_GET['durum'])): ?>
        <?php if($_GET['durum'] == 'eklendi'): ?><div class="alert alert-success">✅ Yeni kategori başarıyla oluşturuldu.</div><?php endif; ?>
        <?php if($_GET['durum'] == 'guncellendi'): ?><div class="alert alert-success">✅ Kategori güncellendi.</div><?php endif; ?>
        <?php if($_GET['durum'] == 'silindi'): ?><div class="alert alert-success">🗑️ Kategori silindi.</div><?php endif; ?>
        <?php if($_GET['durum'] == 'hata_alt_var'): ?><div class="alert alert-error">❌ HATA: Bu kategorinin altında alt kategoriler var! Önce onları silmelisiniz.</div><?php endif; ?>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top:0; color: #2d3748;">
            <?php echo ($duzenle_id > 0) ? "🛠️ Kategoriyi Düzenle: ".$duzenle_veri['adi'] : "✨ Yeni Kategori Ekle"; ?>
        </h3>
        <form action="" method="POST">
            <input type="hidden" name="kategori_id" value="<?php echo $duzenle_veri['id']; ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 30px;">
                <div>
                    <label style="font-size:13px; font-weight:700; color:#4a5568; display:block; margin-bottom:8px;">Üst Kategori (Soyağacı Seçimi)</label>
                    <select name="ust_id" class="form-input" style="font-family: monospace;">
                        <option value="0">🏁 ANA KATEGORİ (Dede)</option>
                        <?php adminKategoriSelect($db, 0, 0, $duzenle_veri['ust_id']); ?>
                    </select>

                    <div style="display: flex; gap: 15px;">
                        <div style="flex: 2;">
                            <label style="font-size:13px; font-weight:700; color:#4a5568; display:block; margin-bottom:8px;">Kategori Adı</label>
                            <input type="text" name="adi" class="form-input" value="<?php echo $duzenle_veri['adi']; ?>" placeholder="Örn: Arpa Samanı" required>
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size:13px; font-weight:700; color:#4a5568; display:block; margin-bottom:8px;">İkon</label>
                            <input type="text" name="ikon" class="form-input" value="<?php echo $duzenle_veri['ikon']; ?>" placeholder="Örn: 🌾">
                        </div>
                    </div>

                    <label style="font-size:13px; font-weight:700; color:#4a5568; display:block; margin-bottom:8px;">Sıralama (Küçükten Büyüğe)</label>
                    <input type="number" name="sira" class="form-input" value="<?php echo $duzenle_veri['sira']; ?>">
                </div>

                <div>
                    <label style="font-size:13px; font-weight:700; color:#1b4332; display:block; margin-bottom:8px;">🛠️ Teknik Özellikleri Bu Kategoriye Bağla</label>
                    <div class="ozellik-box">
                        <?php foreach($ozellik_havuzu as $oz): ?>
                            <label class="ozellik-item">
                                <input type="checkbox" name="kat_ozellikler[]" value="<?php echo $oz['id']; ?>" 
                                    <?php echo in_array($oz['id'], $bagli_ozellikler) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($oz['ozellik_adi']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p style="font-size:11px; color:#718096; margin-top:10px;">* Burada seçtiğiniz özellikler, bu kategoride ilan verilirken kullanıcıya sorulur.</p>
                </div>
            </div>
            
            <button type="submit" name="kategori_kaydet" class="btn-kaydet" style="margin-top:20px;">
                <?php echo ($duzenle_id > 0) ? "💾 DEĞİŞİKLİKLERİ KAYDET" : "➕ KATEGORİYİ SİSTEME EKLE"; ?>
            </button>
            <?php if($duzenle_id > 0): ?>
                <a href="kategoriler.php" style="display:block; text-align:center; margin-top:10px; color:#718096; text-decoration:none; font-size:13px;">Vazgeç / Yeni Ekle</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card" style="padding: 0;">
        <table>
            <thead>
                <tr>
                    <th width="80">Sıra</th>
                    <th width="60">İkon</th>
                    <th>Kategori Yapısı (Hiyerarşi)</th>
                    <th style="text-align: right;">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php adminKategoriTabloListele($db); ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>