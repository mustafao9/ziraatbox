<?php 
require_once "../sistem/ayar.php"; 

if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    header("Location: giris.php"); exit;
}

// TRAFİK İSTATİSTİKLERİ
$bugun_tekil     = $db->query("SELECT COUNT(DISTINCT ip_adresi) FROM ziyaretler WHERE DATE(tarih) = CURDATE() AND is_bot = 0")->fetchColumn();
$bugun_bot       = $db->query("SELECT COUNT(*) FROM ziyaretler WHERE DATE(tarih) = CURDATE() AND is_bot = 1")->fetchColumn();
$aylik_ziyaret   = $db->query("SELECT COUNT(id) FROM ziyaretler WHERE tarih > DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

// Grafik için son 15 gün
$grafik_sorgu = $db->query("SELECT DATE(tarih) as gun, COUNT(DISTINCT ip_adresi) as adet FROM ziyaretler WHERE is_bot = 0 GROUP BY gun ORDER BY gun DESC LIMIT 15")->fetchAll();
$grafik_sorgu = array_reverse($grafik_sorgu);

// Son 30 Ziyaret Kaydı
$son_kayitlar = $db->query("SELECT * FROM ziyaretler ORDER BY id DESC LIMIT 30")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ziyaretçi Analizi | ZiraatBox</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --admin-dark: #1a202c; --admin-green: #27ae60; --admin-blue: #3182ce; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f4f7f6; display: flex; }
        .sidebar { width: 260px; background: var(--admin-dark); color: #fff; min-height: 100vh; padding: 20px; box-sizing: border-box; position: sticky; top: 0; }
        .content { flex: 1; padding: 30px; box-sizing: border-box; }
        .view-site-btn { display: flex; align-items: center; justify-content: center; background: var(--admin-green); color: #fff; text-decoration: none; padding: 12px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; }
        .sidebar a { display: block; color: #cbd5e0; padding: 12px; text-decoration: none; border-radius: 8px; margin-bottom: 5px; }
        .sidebar a.active { background: #2d3748; color: #fff; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .card { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .stat-val { font-size: 32px; font-weight: 800; color: var(--admin-dark); margin-top: 10px; }
        .chart-container { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 15px; overflow: hidden; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; font-size: 12px; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .bot-badge { background: #edf2f7; color: #4a5568; padding: 3px 8px; border-radius: 5px; font-size: 10px; font-weight: 700; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2 style="color: #48bb78; margin-bottom: 30px;">ZiraatBox</h2>
    <a href="../index.php" target="_blank" class="view-site-btn">🌐 Siteyi Görüntüle</a>
    <nav>
        <a href="index.php">🏠 Panel Özeti</a>
        <a href="ziyaretciler.php" class="active">📈 Ziyaretçi Analizi</a>
        <a href="ilanlar.php">📦 İlan Yönetimi</a>
        <a href="kategoriler.php">📂 Kategori Yönetimi</a>
        <a href="uyeler.php">👥 Üye Yönetimi</a>
        <a href="mesajlar.php">💬 Mesajlar</a>
        <a href="ayarlar.php">⚙️ Genel Ayarlar</a>
        <hr style="border:0; border-top:1px solid #2d3748; margin:20px 0;">
        <a href="cikis.php" style="color: #fc8181;">🚪 Güvenli Çıkış</a>
    </nav>
</div>

<div class="content">
    <h1 style="margin: 0 0 5px; color: #1a202c;">📈 Ziyaretçi Analizi</h1>
    <p style="color: #718096; margin-bottom: 30px;">Trafik akışı ve arama motoru etkileşimleri.</p>

    <div class="grid">
        <div class="card" style="border-top: 4px solid var(--admin-blue);">
            <small style="color: #718096; font-weight: 700;">BUGÜN TEKİL</small>
            <div class="stat-val"><?php echo $bugun_tekil; ?></div>
        </div>
        <div class="card" style="border-top: 4px solid #9f7aea;">
            <small style="color: #718096; font-weight: 700;">ARAMA MOTORLARI (BUGÜN)</small>
            <div class="stat-val"><?php echo $bugun_bot; ?></div>
        </div>
        <div class="card" style="border-top: 4px solid var(--admin-green);">
            <small style="color: #718096; font-weight: 700;">30 GÜNLÜK TRAFİK</small>
            <div class="stat-val"><?php echo number_format($aylik_ziyaret); ?></div>
        </div>
    </div>

    <div class="chart-container">
        <h3 style="margin: 0 0 20px;">📉 Günlük Ziyaretçi Akışı (Son 15 Gün)</h3>
        <canvas id="ziyaretChart" height="80"></canvas>
    </div>

    <div class="card">
        <h3 style="margin: 0 0 15px;">🔍 Son Ziyaret Kayıtları</h3>
        <table>
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>IP Adresi</th>
                    <th>Tür</th>
                    <th>Cihaz / Bot Bilgisi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($son_kayitlar as $z): ?>
                <tr>
                    <td><?php echo date('d.m.Y H:i', strtotime($z['tarih'])); ?></td>
                    <td><code><?php echo $z['ip_adresi']; ?></code></td>
                    <td><?php echo $z['is_bot'] ? '<span class="bot-badge">BOT</span>' : '👤 KULLANICI'; ?></td>
                    <td style="color: #718096; font-size: 11px;"><?php echo substr($z['user_agent'], 0, 80); ?>...</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const ctx = document.getElementById('ziyaretChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?php foreach($grafik_sorgu as $g) echo "'".date('d/m', strtotime($g['gun']))."',"; ?>],
        datasets: [{
            label: 'Tekil Ziyaretçi',
            data: [<?php foreach($grafik_sorgu as $g) echo $g['adet'].","; ?>],
            borderColor: '#3182ce',
            tension: 0.3,
            fill: true,
            backgroundColor: 'rgba(49, 130, 206, 0.1)',
            borderWidth: 3
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
</script>

</body>
</html>