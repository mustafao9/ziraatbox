<?php 
require_once "../sistem/ayar.php"; 

if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    header("Location: giris.php"); exit;
}

// Sidebar bildirimleri
$onay_bekleyen = $db->query("SELECT COUNT(*) FROM ilanlar WHERE durum = 'beklemede'")->fetchColumn();
$yeni_mesaj    = $db->query("SELECT COUNT(*) FROM mesajlar WHERE okundu = 0")->fetchColumn();

// --- ÜYE GÜNCELLEME İŞLEMİ ---
if(isset($_POST['uye_guncelle'])){
    $id = intval($_POST['uye_id']);
    $ad_soyad = g($_POST['ad_soyad']);
    $telefon  = g($_POST['telefon']);
    $email    = g($_POST['email']);
    $yetki    = g($_POST['yetki']);
    $durum    = g($_POST['durum']);

    if($id == $_SESSION['admin_id'] && $yetki != 'admin'){
        header("Location: uyeler.php?hata=kendi_yetkin"); exit;
    }

    $guncelle = $db->prepare("UPDATE uyeler SET ad_soyad = ?, telefon = ?, email = ?, yetki = ?, durum = ? WHERE id = ?");
    $sonuc = $guncelle->execute([$ad_soyad, $telefon, $email, $yetki, $durum, $id]);
    header("Location: uyeler.php?durum=guncellendi"); exit;
}

// --- ÜYE İŞLEMLERİ (SİL/DURDUR) ---
if(isset($_GET['islem']) && isset($_GET['id'])){
    $id = intval($_GET['id']);
    if($id == $_SESSION['admin_id']){ header("Location: uyeler.php?hata=kendi_hesabin"); exit; }
    if($_GET['islem'] == "sil") $db->prepare("DELETE FROM uyeler WHERE id = ?")->execute([$id]);
    header("Location: uyeler.php?durum=islem_tamam"); exit;
}

// Düzenlenecek veriyi çek
$duzenle_id = isset($_GET['duzenle']) ? intval($_GET['duzenle']) : 0;
$d_uye = ['id'=>0, 'ad_soyad'=>'', 'email'=>'', 'telefon'=>'', 'yetki'=>'uye', 'durum'=>'aktif'];
if($duzenle_id > 0) $d_uye = $db->query("SELECT * FROM uyeler WHERE id = $duzenle_id")->fetch();

$uyeler = $db->query("SELECT * FROM uyeler ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>ZiraatBox | Üye Yönetimi</title>
    <style>
        :root { 
            --bg: #f8fafc; --sidebar: #1e293b; --primary: #10b981; 
            --accent: #3b82f6; --text-main: #1e293b; --text-muted: #64748b;
        }
        body { margin: 0; font-family: 'Inter', 'Segoe UI', sans-serif; background: var(--bg); color: var(--text-main); display: flex; }
        
        /* SIDEBAR (index.php ile tam uyum) */
        .sidebar { width: 280px; background: var(--sidebar); min-height: 100vh; padding: 25px; box-sizing: border-box; position: sticky; top: 0; }
        .sidebar h2 { color: var(--primary); font-size: 24px; font-weight: 800; margin-bottom: 30px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #94a3b8; padding: 12px 15px; text-decoration: none; border-radius: 10px; margin-bottom: 8px; transition: 0.3s; font-weight: 500; }
        .sidebar a:hover, .sidebar a.active { background: #334155; color: #fff; }
        .view-site-btn { background: var(--primary); color: #fff !important; justify-content: center; margin-bottom: 25px; }

        .content { flex: 1; padding: 40px; }

        /* DÜZENLEME PANELİ - MODERN CARD */
        .edit-card { 
            background: #fff; border-radius: 20px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); 
            padding: 35px; margin-bottom: 40px; border: 1px solid #e2e8f0; 
            background: linear-gradient(to bottom right, #ffffff, #f8fafc);
        }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .input-group label { display: block; font-size: 12px; font-weight: 800; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; }
        .input-control { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 14px; outline: none; transition: 0.3s; }
        .input-control:focus { border-color: var(--accent); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .btn-update { 
            background: var(--accent); color: #fff; border: 0; padding: 12px 25px; border-radius: 12px; 
            font-weight: 700; cursor: pointer; transition: 0.3s; width: 100%; align-self: end; height: 45px;
        }
        .btn-update:hover { background: #2563eb; transform: translateY(-2px); }

        /* LİSTE TASARIMI */
        .list-card { background: #fff; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; padding: 18px; text-align: left; font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 18px; border-bottom: 1px solid #f1f5f9; transition: 0.2s; }
        tr:hover td { background: #f8fafc; }
        
        /* AVATAR & BADGE */
        .user-info { display: flex; align-items: center; gap: 15px; }
        .avatar { width: 45px; height: 45px; border-radius: 12px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #475569; }
        .badge { padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; }
        .badge-aktif { background: #dcfce7; color: #166534; }
        .badge-pasif { background: #fee2e2; color: #991b1b; }
        
        .action-link { text-decoration: none; font-weight: 700; font-size: 13px; margin-right: 15px; transition: 0.2s; }
        .edit-link { color: var(--accent); }
        .delete-link { color: #ef4444; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Ziraat<span style="color:#f39c12">Box</span></h2>
    <a href="../index.php" target="_blank" class="view-site-btn">🌐 Siteyi Görüntüle</a>
    <nav>
        <a href="index.php">🏠 Panel Özeti</a>
        <a href="ilanlar.php">📦 İlan Yönetimi (<b><?php echo $onay_bekleyen; ?></b>)</a>
        <a href="kategoriler.php">📂 Kategori Yönetimi</a>
        <a href="ozellikler.php">🛠️ Özellik Havuzu</a>
        <a href="uyeler.php" class="active">👥 Üye Yönetimi</a>
        <a href="mesajlar.php">💬 Mesajlar (<b><?php echo $yeni_mesaj; ?></b>)</a>
        <a href="ayarlar.php">⚙️ Genel Ayarlar</a>
    </nav>
</div>

<div class="content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
        <div>
            <h1 style="font-size: 32px; font-weight: 800; color: #0f172a; margin: 0;">👥 Üye Yönetimi</h1>
            <p style="color: var(--text-muted); margin-top: 5px;">Üye bilgilerini düzenleyin ve erişim yetkilerini yönetin.</p>
        </div>
    </div>

    <?php if($duzenle_id > 0): ?>
    <div class="edit-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 style="margin:0; font-size:20px; color:#0f172a;">🛠️ Üye Düzenleniyor: <span style="color:var(--accent);"><?php echo $d_uye['ad_soyad']; ?></span></h2>
            <a href="uyeler.php" style="color:var(--text-muted); text-decoration:none; font-size:13px; font-weight:700;">❌ Vazgeç</a>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="uye_id" value="<?php echo $d_uye['id']; ?>">
            <div class="form-grid">
                <div class="input-group">
                    <label>Tam İsim</label>
                    <input type="text" name="ad_soyad" class="input-control" value="<?php echo $d_uye['ad_soyad']; ?>">
                </div>
                <div class="input-group">
                    <label>E-Posta Adresi</label>
                    <input type="email" name="email" class="input-control" value="<?php echo $d_uye['email']; ?>">
                </div>
                <div class="input-group">
                    <label>Telefon</label>
                    <input type="text" name="telefon" class="input-control" value="<?php echo $d_uye['telefon']; ?>">
                </div>
                <div class="input-group">
                    <label>Yetki Seviyesi</label>
                    <select name="yetki" class="input-control">
                        <option value="uye" <?php echo $d_uye['yetki']=='uye'?'selected':''; ?>>👤 Standart Üye</option>
                        <option value="admin" <?php echo $d_uye['yetki']=='admin'?'selected':''; ?>>👨‍✈️ Yönetici (Admin)</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Hesap Durumu</label>
                    <select name="durum" class="input-control">
                        <option value="aktif" <?php echo $d_uye['durum']=='aktif'?'selected':''; ?>>✅ Aktif</option>
                        <option value="pasif" <?php echo $d_uye['durum']=='pasif'?'selected':''; ?>>🚫 Engelli</option>
                    </select>
                </div>
                <button type="submit" name="uye_guncelle" class="btn-update">💾 GÜNCELLE</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="list-card">
        <table>
            <thead>
                <tr>
                    <th>Üye Profili</th>
                    <th>Yetki</th>
                    <th>Kayıt Tarihi</th>
                    <th>Durum</th>
                    <th style="text-align: right;">Yönet</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($uyeler as $u): ?>
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="avatar"><?php echo mb_substr($u['ad_soyad'], 0, 1); ?></div>
                            <div>
                                <div style="font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($u['ad_soyad']); ?></div>
                                <div style="font-size: 12px; color: var(--text-muted);"><?php echo $u['email']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="font-size: 13px; font-weight: 600; color: <?php echo $u['yetki']=='admin'?'#f59e0b':'#64748b'; ?>">
                            <?php echo $u['yetki']=='admin' ? '👮 Admin' : '👤 Üye'; ?>
                        </span>
                    </td>
                    <td style="font-size: 13px; color: var(--text-muted);"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                    <td>
                        <span class="badge <?php echo $u['durum']=='aktif'?'badge-aktif':'badge-pasif'; ?>">
                            <?php echo strtoupper($u['durum']); ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <a href="?duzenle=<?php echo $u['id']; ?>" class="action-link edit-link">📝 Düzenle</a>
                        <?php if($u['id'] != $_SESSION['admin_id']): ?>
                            <a href="?islem=sil&id=<?php echo $u['id']; ?>" onclick="return confirm('Silinsin mi?')" class="action-link delete-link">❌ Sil</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>