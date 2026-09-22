<?php 
require_once "../sistem/ayar.php"; 

if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    header("Location: giris.php"); exit;
}

// 1. ÖZELLİĞİ KATEGORİYE BAĞLA
if(isset($_POST['bagla'])){
    $kat_id = intval($_POST['kategori_id']);
    $oz_id  = intval($_POST['ozellik_id']);
    $sira   = intval($_POST['sira']);

    if($kat_id > 0 && $oz_id > 0){
        $ekle = $db->prepare("INSERT INTO kategori_ozellikleri SET kategori_id = ?, ozellik_id = ?, sira = ?");
        $ekle->execute([$kat_id, $oz_id, $sira]);
        header("Location: kategori-ozellikleri.php?kat=$kat_id&durum=ok"); exit;
    }
}

// 2. BAĞLANTIYI KOPAR (SİL)
if(isset($_GET['sil'])){
    $id = intval($_GET['sil']);
    $kat = intval($_GET['kat']);
    $db->prepare("DELETE FROM kategori_ozellikleri WHERE id = ?")->execute([$id]);
    header("Location: kategori-ozellikleri.php?kat=$kat&durum=silindi"); exit;
}

// Kategorileri ve Özellikleri Çek
$kategoriler = $db->query("SELECT * FROM kategoriler ORDER BY ust_id, sira ASC")->fetchAll();
$tum_ozellikler = $db->query("SELECT * FROM ozellik_tanimlari ORDER BY ozellik_adi ASC")->fetchAll();

$secili_kat = isset($_GET['kat']) ? intval($_GET['kat']) : 0;
$bagli_ozellikler = [];
if($secili_kat > 0){
    $bagli_ozellikler = $db->prepare("SELECT ko.id, ot.ozellik_adi, ot.veri_turu, ko.sira 
                                     FROM kategori_ozellikleri ko
                                     JOIN ozellik_tanimlari ot ON ko.ozellik_id = ot.id
                                     WHERE ko.kategori_id = ? ORDER BY ko.sira ASC");
    $bagli_ozellikler->execute([$secili_kat]);
    $bagli_ozellikler = $bagli_ozellikler->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kategori Özellik Eşleştirme</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; display: flex; }
        .sidebar { width: 260px; background: #1a202c; color: #fff; min-height: 100vh; padding: 20px; }
        .content { flex: 1; padding: 30px; }
        .card { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; }
        select, input { padding: 10px; border-radius: 8px; border: 1px solid #ddd; width: 100%; margin-bottom: 10px; }
        .btn { background: #27ae60; color: #fff; border: 0; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .kat-liste { background: #fff; padding: 10px; border-radius: 8px; border: 1px solid #eee; margin-bottom: 20px; }
    </style>
</head>
<body>

<?php include "sol_menu.php"; ?>

<div class="content">
    <h1>📂 Kategoriye Özellik Ata</h1>
    <p>Hangi kategoride hangi teknik bilgilerin sorulacağını buradan ayarlayın.</p>

    <div class="kat-liste">
        <label><strong>Önce Kategori Seçin:</strong></label>
        <select onchange="window.location.href='?kat='+this.value">
            <option value="">-- Kategori Seçiniz --</option>
            <?php foreach($kategoriler as $k): ?>
                <option value="<?php echo $k['id']; ?>" <?php echo ($secili_kat == $k['id']) ? 'selected' : ''; ?>>
                    <?php echo ($k['ust_id'] > 0 ? "— " : "") . $k['adi']; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if($secili_kat > 0): ?>
    <div class="card">
        <h3>✨ Bu Kategoriye Yeni Özellik Ekle</h3>
        <form method="POST">
            <input type="hidden" name="kategori_id" value="<?php echo $secili_kat; ?>">
            <div style="display: flex; gap: 15px;">
                <select name="ozellik_id" required>
                    <option value="">-- Özellik Seçin --</option>
                    <?php foreach($tum_ozellikler as $o): ?>
                        <option value="<?php echo $o['id']; ?>"><?php echo $o['ozellik_adi']; ?> (<?php echo $o['veri_turu']; ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="sira" placeholder="Sıra (Örn: 1)" style="width: 100px;">
                <button type="submit" name="bagla" class="btn">KATEGORİYE EKLE</button>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Sıra</th>
                    <th>Özellik Adı</th>
                    <th>Tür</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($bagli_ozellikler as $b): ?>
                <tr>
                    <td><?php echo $b['sira']; ?></td>
                    <td><strong><?php echo $b['ozellik_adi']; ?></strong></td>
                    <td><?php echo $b['veri_turu']; ?></td>
                    <td><a href="?sil=<?php echo $b['id']; ?>&kat=<?php echo $secili_kat; ?>" style="color:red; text-decoration:none;">🗑️ Kaldır</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="card" style="text-align: center; color: #718096;">
            Yukarıdaki listeden bir kategori seçerek işlemlere başlayabilirsiniz.
        </div>
    <?php endif; ?>
</div>

</body>
</html>