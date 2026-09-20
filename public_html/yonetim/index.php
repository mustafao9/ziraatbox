<?php 
require_once "../sistem/ayar.php"; 

if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    header("Location: giris.php"); exit;
}

// CANLI İSTATİSTİKLER
$toplam_ilan    = $db->query("SELECT COUNT(*) FROM ilanlar")->fetchColumn();
$onay_bekleyen  = $db->query("SELECT COUNT(*) FROM ilanlar WHERE durum = 'beklemede'")->fetchColumn();
$toplam_uye      = $db->query("SELECT COUNT(*) FROM uyeler WHERE yetki = 'uye'")->fetchColumn();
$yeni_mesaj      = $db->query("SELECT COUNT(*) FROM mesajlar WHERE okundu = 0")->fetchColumn();

// 🚨 ŞİKAYET İSTATİSTİĞİ
$yeni_sikayet    = $db->query("SELECT COUNT(*) FROM sikayetler WHERE durum = 'beklemede'")->fetchColumn();

$ayarlar = $db->query("SELECT * FROM ayarlar WHERE id = 1")->fetch();

// SON İLANLAR
$son_ilanlar = $db->query("SELECT i.*, k.adi as kat_adi, u.ad_soyad 
                           FROM ilanlar i 
                           LEFT JOIN kategoriler k ON i.kategori_id = k.id 
                           LEFT JOIN uyeler u ON i.uye_id = u.id 
                           ORDER BY (i.durum = 'beklemede') DESC, i.id DESC LIMIT 5")->fetchAll();

// 🛡️ SON ŞİKAYETLER
$son_sikayetler = $db->query("SELECT s.*, u.ad_soyad as sikayetci, i.baslik as ilan_baslik 
                              FROM sikayetler s 
                              JOIN uyeler u ON s.sikayetci_id = u.id 
                              LEFT JOIN ilanlar i ON s.ilan_id = i.id 
                              ORDER BY s.id DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Panel Özeti | ZiraatBox</title>
    <style>
        :root { --admin-dark: #1a202c; --admin-green: #27ae60; --admin-orange: #ed8936; --admin-blue: #3182ce; --admin-red: #e53e3e; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f4f7f6; display: flex; }
        .sidebar { width: 260px; background: var(--admin-dark); color: #fff; min-height: 100vh; padding: 20px; box-sizing: border-box; position: sticky; top: 0; }
        .content { flex: 1; padding: 30px; box-sizing: border-box; }
        .view-site-btn { display: flex; align-items: center; justify-content: center; background: var(--admin-green); color: #fff; text-decoration: none; padding: 12px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; font-size: 14px; }
        .sidebar a { display: block; color: #cbd5e0; padding: 12px; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #2d3748; color: #fff; }
        
        /* Bildirim Balonu */
        .nav-badge { background: var(--admin-red); color: white; padding: 2px 7px; border-radius: 50%; font-size: 10px; float: right; font-weight: 800; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 5px solid #4a90e2; }
        .stat-card h3 { margin: 0; font-size: 11px; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .number { font-size: 28px; font-weight: 800; color: #2d3748; margin-top: 8px; }
        
        .dashboard-row { display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px; margin-bottom: 25px; }
        .data-card { background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 12px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; }
        .badge-pending { background: #fffaf0; color: #c05621; }
        .badge-active { background: #f0fdf4; color: #166534; }
        .badge-red { background: #fff5f5; color: #c53030; }

        /* Hızlı İşlem Butonları */
        .quick-action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 20px; }
        .action-btn { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 12px; text-align: center; text-decoration: none; color: #4a5568; font-weight: 700; font-size: 13px; transition: 0.2s; }
        .action-btn:hover { border-color: var(--admin-green); background: #f0fdf4; color: var(--admin-green); }

        @media (max-width: 1100px) { .dashboard-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="sidebar">
    <h2 style="color: #48bb78; margin-bottom: 30px;">Ziraat<span style="color: #f39c12;">Box</span></h2>
    <a href="../index.php" target="_blank" class="view-site-btn">🌐 Siteyi Görüntüle</a>
    <nav>
        <a href="index.php" class="active">🏠 Panel Özeti</a>
        <a href="ziyaretciler.php">📈 Ziyaretçi Analizi</a> 
        <a href="ilanlar.php">📦 İlan Yönetimi <?php if($onay_bekleyen > 0): ?><span class="nav-badge" style="background:var(--admin-orange)"><?php echo $onay_bekleyen; ?></span><?php endif; ?></a>
        <a href="sikayetler.php">🛡️ Şikayetler <?php if($yeni_sikayet > 0): ?><span class="nav-badge"><?php echo $yeni_sikayet; ?></span><?php endif; ?></a>
        <a href="kategoriler.php">📂 Kategori Yönetimi</a>
        <a href="uyeler.php">👥 Üye Yönetimi</a>
        <a href="mesajlar.php">💬 Mesajlar <?php if($yeni_mesaj > 0): ?><span class="nav-badge" style="background:var(--admin-blue)"><?php echo $yeni_mesaj; ?></span><?php endif; ?></a>
        
        <hr style="border: 0; border-top: 1px solid #2d3748; margin: 15px 0;">
        <a href="yedekleme.php">🗄️ Sistem Yedekleme</a>
        <a href="ayarlar.php">⚙️ Genel Ayarlar</a>
        <a href="cikis.php" style="color: #fc8181;">🚪 Güvenli Çıkış</a>
    </nav>
</div>

<div class="content">
    <h1 style="margin: 0 0 30px; color: #1a202c; font-size: 26px;">Komuta Merkezi</h1>

    <div class="stats-grid">
        <div class="stat-card" style="border-left-color: var(--admin-blue);">
            <h3>Toplam İlan</h3>
            <div class="number"><?php echo $toplam_ilan; ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--admin-orange);">
            <h3>Onay Bekleyen</h3>
            <div class="number"><?php echo $onay_bekleyen; ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--admin-red);">
            <h3>Aktif Şikayetler</h3>
            <div class="number" style="color:var(--admin-red)"><?php echo $yeni_sikayet; ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--admin-green);">
            <h3>Toplam Üye</h3>
            <div class="number"><?php echo $toplam_uye; ?></div>
        </div>
    </div>

    <div class="dashboard-row">
        <div class="data-card">
            <h3 style="margin: 0; color: #2d3748; font-size: 16px; display: flex; justify-content: space-between;">
                📦 Son Aktiviteler 
                <a href="ilanlar.php" style="font-size: 12px; color: var(--admin-blue);">Tümünü Gör</a>
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>İlan Başlığı</th>
                        <th>Durum</th>
                        <th style="text-align: right;">Git</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($son_ilanlar as $ilan): ?>
                    <tr>
                        <td><strong><?php echo mb_substr(htmlspecialchars($ilan['baslik']),0,40); ?>...</strong></td>
                        <td>
                            <span class="badge <?php echo ($ilan['durum'] == 'beklemede') ? 'badge-pending' : 'badge-active'; ?>">
                                <?php echo ($ilan['durum'] == 'beklemede') ? 'ONAY BEKLİYOR' : 'YAYINDA'; ?>
                            </span>
                        </td>
                        <td style="text-align: right;"><a href="ilanlar.php?id=<?php echo $ilan['id']; ?>" style="text-decoration: none;">🔍</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="data-card" style="border-top: 4px solid var(--admin-red);">
            <h3 style="margin: 0; color: #2d3748; font-size: 16px;">🛡️ Denetim Bekleyenler</h3>
            <table>
                <thead>
                    <tr>
                        <th>Şikayetçi</th>
                        <th>Durum</th>
                        <th style="text-align: right;">Git</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($son_sikayetler as $s): ?>
                    <tr>
                        <td><div style="font-weight:700;"><?php echo htmlspecialchars($s['sikayetci']); ?></div></td>
                        <td><span class="badge badge-red">ACİL</span></td>
                        <td style="text-align: right;"><a href="sikayetler.php" style="text-decoration: none;">🚨</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($son_sikayetler)): ?>
                        <tr><td colspan="3" style="text-align:center; color:#cbd5e0; padding:20px;">Şu an her şey yolunda.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="data-card" style="margin-top: 25px;">
        <h3 style="margin: 0; color: #2d3748; font-size: 16px;">⚡ Hızlı İşlemler</h3>
        <div class="quick-action-grid">
            <a href="yedekleme.php" class="action-btn">🗄️ Yedekleme Paneli</a>
            <a href="islem/yedek-al.php?tip=sql" class="action-btn">💾 Hızlı SQL Yedeği</a>
            <a href="ziyaretciler.php" class="action-btn">📊 Trafik Raporu</a>
            <a href="ayarlar.php" class="action-btn">⚙️ Site Ayarları</a>
        </div>
    </div>
</div>

</body>
</html>