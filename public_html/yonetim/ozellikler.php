<?php 
require_once "../sistem/ayar.php"; 

/**
 * 🛡️ GÜVENLİK FONKSİYONU (Hata Veren Kısım)
 * Dışarıdan gelen verileri temizler.
 */
if (!function_exists('g')) {
    function g($par) {
        return strip_tags(trim($par));
    }
}

// 1. ADIM: YETKİ VE OTURUM KONTROLÜ
if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    header("Location: giris.php"); exit;
}

// Bildirim sayıları
$onay_bekleyen = $db->query("SELECT COUNT(*) FROM ilanlar WHERE durum = 'beklemede'")->fetchColumn();
$yeni_mesaj    = $db->query("SELECT COUNT(*) FROM mesajlar WHERE okundu = 0")->fetchColumn();

// --- ÖZELLİK KAYIT/DÜZENLEME/SİLME MANTIĞI ---
if(isset($_POST['ozellik_kaydet'])){
    $id    = intval($_POST['ozellik_id']);
    $ad    = g($_POST['ozellik_adi']);
    $tur   = g($_POST['veri_turu']);
    $birim = g($_POST['birim']);
    $liste = g($_POST['liste_icerik']);

    if($id > 0){
        $islem = $db->prepare("UPDATE ozellik_tanimlari SET ozellik_adi = ?, veri_turu = ?, birim = ?, liste_icerik = ? WHERE id = ?");
        $islem->execute([$ad, $tur, $birim, $liste, $id]);
        $durum = "guncellendi";
    } else {
        $islem = $db->prepare("INSERT INTO ozellik_tanimlari SET ozellik_adi = ?, veri_turu = ?, birim = ?, liste_icerik = ?");
        $islem->execute([$ad, $tur, $birim, $liste]);
        $durum = "eklendi";
    }
    header("Location: ozellikler.php?durum=$durum"); exit;
}

if(isset($_GET['sil'])){
    $sil_id = intval($_GET['sil']);
    // İlişkili tüm verileri temizleyerek siler (Veritabanı sağlığı için)
    $db->prepare("DELETE FROM kategori_ozellikleri WHERE ozellik_id = ?")->execute([$sil_id]);
    $db->prepare("DELETE FROM ilan_ozellik_verileri WHERE ozellik_id = ?")->execute([$sil_id]);
    $db->prepare("DELETE FROM ozellik_tanimlari WHERE id = ?")->execute([$sil_id]);
    header("Location: ozellikler.php?durum=silindi"); exit;
}

$duzenle_id = isset($_GET['duzenle']) ? intval($_GET['duzenle']) : 0;
$d_veri = ['id'=>0, 'ozellik_adi'=>'', 'veri_turu'=>'yazi', 'birim'=>'', 'liste_icerik'=>''];
if($duzenle_id > 0){
    $sorgu = $db->prepare("SELECT * FROM ozellik_tanimlari WHERE id = ?");
    $sorgu->execute([$duzenle_id]);
    $d_veri = $sorgu->fetch();
}

$ozellikler = $db->query("SELECT * FROM ozellik_tanimlari ORDER BY id DESC")->fetchAll();
$turler = ['yazi'=>'Kısa Yazı', 'sayi'=>'Sayı', 'ondalik'=>'Ondalık', 'yil'=>'Yıl', 'onay'=>'Evet/Hayır', 'liste'=>'Liste'];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Özellik Havuzu | ZiraatBox Yönetim</title>
    <style>
        :root { --admin-dark: #1a202c; --admin-green: #27ae60; --admin-orange: #ed8936; --admin-blue: #3182ce; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7f6; display: flex; }
        .sidebar { width: 260px; background: var(--admin-dark); color: #fff; min-height: 100vh; padding: 20px; box-sizing: border-box; position: sticky; top: 0; }
        .content { flex: 1; padding: 30px; box-sizing: border-box; }
        .view-site-btn { display: flex; align-items: center; justify-content: center; background: var(--admin-green); color: #fff; text-decoration: none; padding: 12px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; font-size: 14px; transition: 0.3s; border: 1px solid #219150; }
        .sidebar a { display: block; color: #cbd5e0; padding: 12px; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #2d3748; color: #fff; }
        .card { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .form-row { display: grid; grid-template-columns: 2fr 1fr 1fr 2fr 1fr; gap: 15px; align-items: flex-end; }
        @media (max-width: 1000px) { .form-row { grid-template-columns: 1fr; } }
        .form-group label { display: block; font-size: 13px; font-weight: 700; color: #4a5568; margin-bottom: 8px; }
        .form-input { width: 100%; padding: 12px; border: 2px solid #edf2f7; border-radius: 10px; font-size: 14px; outline: none; box-sizing: border-box; }
        .form-input:focus { border-color: var(--admin-green); }
        .btn-submit { background: var(--admin-green); color: #fff; border: 0; padding: 12px; border-radius: 10px; font-weight: 800; cursor: pointer; transition: 0.3s; width: 100%; height: 46px; }
        .btn-update { background: var(--admin-blue); }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 15px; overflow: hidden; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #edf2f7; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2 style="color: #48bb78; margin-bottom: 30px;">Ziraat<span style="color: #f39c12;">Box</span></h2>
    <a href="../index.php" target="_blank" class="view-site-btn">🌐 Siteyi Görüntüle</a>
    <nav>
        <a href="index.php">🏠 Panel Özeti</a>
        <a href="ilanlar.php">📦 İlan Yönetimi (<b><?php echo $onay_bekleyen; ?></b>)</a>
        <a href="kategoriler.php">📂 Kategori Yönetimi</a>
        <a href="ozellikler.php" class="active">🛠️ Özellik Havuzu</a> 
        <a href="uyeler.php">👥 Üye Yönetimi</a>
        <a href="mesajlar.php">💬 Mesajlar (<b><?php echo $yeni_mesaj; ?></b>)</a>
        <a href="ayarlar.php">⚙️ Genel Ayarlar</a>
        <hr style="border: 0; border-top: 1px solid #2d3748; margin: 20px 0;">
        <a href="cikis.php" style="color: #fc8181;">🚪 Güvenli Çıkış</a>
    </nav>
</div>

<div class="content">
    <div style="margin-bottom: 35px;">
        <h1 style="margin: 0; color: #1a202c; font-size: 32px; font-weight: 800;">🛠️ Özellik Havuzu</h1>
        <p style="margin: 5px 0 0; color: #718096;">Dede-Baba-Torun kategorilerine atanacak teknik özellikleri buradan tanımlayın.</p>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; color: #2d3748; font-size: 18px;">
            <?php echo ($duzenle_id > 0) ? "📝 Özelliği Düzenle" : "✨ Yeni Özellik Ekle"; ?>
        </h3>
        <form action="" method="POST" class="form-row">
            <input type="hidden" name="ozellik_id" value="<?php echo $d_veri['id']; ?>">
            
            <div class="form-group">
                <label>Özellik Adı</label>
                <input type="text" name="ozellik_adi" class="form-input" value="<?php echo htmlspecialchars($d_veri['ozellik_adi']); ?>" placeholder="Örn: Motor Gücü" required>
            </div>
            
            <div class="form-group">
                <label>Alan Türü</label>
                <select name="veri_turu" class="form-input">
                    <?php foreach($turler as $k => $v) echo "<option value='$k' ".($d_veri['veri_turu']==$k?'selected':'').">$v</option>"; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Birim</label>
                <input type="text" name="birim" class="form-input" value="<?php echo htmlspecialchars($d_veri['birim']); ?>" placeholder="HP, Saat vb.">
            </div>

            <div class="form-group">
                <label>Liste İçeriği (Liste seçiliyse)</label>
                <input type="text" name="liste_icerik" class="form-input" value="<?php echo htmlspecialchars($d_veri['liste_icerik']); ?>" placeholder="Seçenekleri virgülle ayırın">
            </div>

            <div class="form-group">
                <button type="submit" name="ozellik_kaydet" class="btn-submit <?php echo ($duzenle_id > 0) ? 'btn-update' : ''; ?>">
                    <?php echo ($duzenle_id > 0) ? "GÜNCELLE" : "SİSTEME EKLE"; ?>
                </button>
            </div>
        </form>
        <?php if($duzenle_id > 0): ?>
            <a href="ozellikler.php" style="display:inline-block; margin-top:10px; color:#a0aec0; font-size:12px; text-decoration:none;">❌ Düzenlemeyi İptal Et</a>
        <?php endif; ?>
    </div>

    <div class="card" style="padding: 0;">
        <table>
            <thead>
                <tr>
                    <th width="80">ID</th>
                    <th>Özellik Adı</th>
                    <th>Veri Türü</th>
                    <th>Birim</th>
                    <th style="text-align: right;">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($ozellikler as $o): ?>
                <tr>
                    <td>#<?php echo $o['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($o['ozellik_adi']); ?></strong></td>
                    <td><span style="background:#edf2f7; padding:4px 8px; border-radius:6px; font-size:12px;"><?php echo $turler[$o['veri_turu']]; ?></span></td>
                    <td><?php echo $o['birim'] ?: '<span style="color:#cbd5e0;">—</span>'; ?></td>
                    <td style="text-align: right;">
                        <a href="?duzenle=<?php echo $o['id']; ?>" style="color: var(--admin-blue); text-decoration: none; font-weight: 700; margin-right: 15px;">📝 Düzenle</a>
                        <a href="?sil=<?php echo $o['id']; ?>" onclick="return confirm('Bu özelliği sildiğinizde bağlı olan tüm ilan verileri de silinir. Emin misiniz?')" style="color: #f56565; text-decoration: none; font-weight: 700;">❌ Sil</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>