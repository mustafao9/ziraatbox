<?php
// Ortak Bildirim Sayıları
$nav_bekleyen = 0; $nav_sikayet = 0; $nav_mesaj = 0;

if (isset($db) && $db instanceof PDO) {
    try {
        $nav_bekleyen = $db->query("SELECT COUNT(*) FROM ilanlar WHERE durum = 'beklemede'")->fetchColumn() ?: 0;
        $nav_sikayet  = $db->query("SELECT COUNT(*) FROM sikayetler WHERE durum = 'beklemede'")->fetchColumn() ?: 0;
        $nav_mesaj    = $db->query("SELECT COUNT(*) FROM mesajlar WHERE okundu = 0")->fetchColumn() ?: 0;
    } catch (Exception $e) {}
}

$aktif_sayfa = basename($_SERVER["PHP_SELF"]);
?>
<style>
    :root { --admin-dark: #1a202c; --admin-green: #27ae60; --admin-orange: #ed8936; --admin-blue: #3182ce; --admin-red: #e53e3e; }
    .sidebar { width: 260px; min-width: 260px; background: var(--admin-dark); color: #fff; min-height: 100vh; padding: 20px; box-sizing: border-box; position: sticky; top: 0; }
    .sidebar h2 { color: #48bb78; margin-top: 0; margin-bottom: 30px; font-size: 24px; font-family: 'Segoe UI', sans-serif; }
    .sidebar a { display: block; color: #cbd5e0; padding: 12px; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; font-size: 14px; font-family: 'Segoe UI', sans-serif; }
    .sidebar a:hover, .sidebar a.active { background: #2d3748; color: #fff; font-weight: 600; }
    .view-site-btn { display: flex !important; align-items: center; justify-content: center; background: var(--admin-green) !important; color: #fff !important; text-decoration: none; padding: 12px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; font-size: 14px; }
    .view-site-btn:hover { background: #219150 !important; }
    .nav-badge { background: var(--admin-red); color: white; padding: 2px 7px; border-radius: 50%; font-size: 10px; float: right; font-weight: 800; margin-top: 2px; }
</style>

<div class="sidebar">
    <h2>Ziraat<span style="color: #f39c12;">Box</span></h2>
    <a href="../index.php" target="_blank" class="view-site-btn">🌐 Siteyi Görüntüle</a>
    <nav>
        <a href="index.php" class="<?php echo $aktif_sayfa == 'index.php' ? 'active' : ''; ?>">🏠 Panel Özeti</a>
        <a href="ziyaretciler.php" class="<?php echo $aktif_sayfa == 'ziyaretciler.php' ? 'active' : ''; ?>">📈 Ziyaretçi Analizi</a> 
        <a href="ilanlar.php" class="<?php echo $aktif_sayfa == 'ilanlar.php' ? 'active' : ''; ?>">📦 İlan Yönetimi <?php if($nav_bekleyen > 0): ?><span class="nav-badge" style="background:var(--admin-orange)"><?php echo $nav_bekleyen; ?></span><?php endif; ?></a>
        <a href="sikayetler.php" class="<?php echo $aktif_sayfa == 'sikayetler.php' ? 'active' : ''; ?>">🛡️ Şikayetler <?php if($nav_sikayet > 0): ?><span class="nav-badge"><?php echo $nav_sikayet; ?></span><?php endif; ?></a>
        <a href="kategoriler.php" class="<?php echo $aktif_sayfa == 'kategoriler.php' ? 'active' : ''; ?>">📂 Kategori Yönetimi</a>
        <a href="ozellikler.php" class="<?php echo $aktif_sayfa == 'ozellikler.php' ? 'active' : ''; ?>">🛠️ Özellik Havuzu</a>
        <a href="uyeler.php" class="<?php echo $aktif_sayfa == 'uyeler.php' ? 'active' : ''; ?>">👥 Üye Yönetimi</a>
        <a href="mesajlar.php" class="<?php echo $aktif_sayfa == 'mesajlar.php' ? 'active' : ''; ?>">💬 Mesajlar <?php if($nav_mesaj > 0): ?><span class="nav-badge" style="background:var(--admin-blue)"><?php echo $nav_mesaj; ?></span><?php endif; ?></a>
        
        <hr style="border: 0; border-top: 1px solid #2d3748; margin: 15px 0;">
        <a href="yedekleme.php" class="<?php echo $aktif_sayfa == 'yedekleme.php' ? 'active' : ''; ?>">🗄️ Sistem Yedekleme</a>
        <a href="guncelleme.php" class="<?php echo $aktif_sayfa == 'guncelleme.php' ? 'active' : ''; ?>">🚀 Sistem Güncelleme</a>
        <a href="ayarlar.php" class="<?php echo $aktif_sayfa == 'ayarlar.php' ? 'active' : ''; ?>">⚙️ Genel Ayarlar</a>
        <a href="cikis.php" style="color: #fc8181;">🚪 Güvenli Çıkış</a>
    </nav>
</div>
