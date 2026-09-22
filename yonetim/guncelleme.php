<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../sistem/ayar.php"; 

if(!isset($_SESSION['admin_id']) && (!isset($_SESSION['yetki']) || $_SESSION['yetki'] != 'admin')){
    header("Location: giris.php"); exit;
}

// 🛡️ DİNAMİK SÜRÜM TESPİTİ (Önce Veritabanı, Yedek Olarak Dosya)
$mevcut_versiyon = '1.0.0';

// 1. AŞAMA: Veritabanından (ayarlar tablosu sistem_versiyon kolonu) Oku
try {
    $stmt = $db->query("SELECT sistem_versiyon FROM ayarlar WHERE id = 1 LIMIT 1");
    if ($stmt) {
        $db_v = $stmt->fetchColumn();
        if (!empty($db_v)) { 
            $mevcut_versiyon = trim($db_v); 
        }
    }
} catch (Exception $e) {
    // Veritabanında kolon henüz yoksa varsayılan devam eder
}

// 2. AŞAMA: Veritabanında Yoksa / Okunamadıysa Fiziki Dosyayı Tara
if ($mevcut_versiyon == '1.0.0') {
    $v_path = __DIR__ . "/../sistem/versiyon.php";
    if (file_exists($v_path)) {
        clearstatcache(true, $v_path);
        $v_content = file_get_contents($v_path);
        if (preg_match("/SISTEM_VERSIYON['\"]\s*,\s*['\"]([^'\"]+)['\"]/i", $v_content, $matches)) {
            $mevcut_versiyon = trim($matches[1]);
        }
    }
}

$github_repo = "mustafao9/ziraatbox";
$guncelleme_var = false;
$son_versiyon = $mevcut_versiyon;
$release_notlari = "GitHub bağlantısı kuruluyor...";

// 2. GitHub Tags API Kontrolü
$ch_tag = curl_init();
curl_setopt($ch_tag, CURLOPT_URL, "https://api.github.com/repos/$github_repo/tags");
curl_setopt($ch_tag, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch_tag, CURLOPT_USERAGENT, 'ZiraatBox-AutoUpdater');
curl_setopt($ch_tag, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch_tag, CURLOPT_TIMEOUT, 10);
$response_tag = curl_exec($ch_tag);
curl_close($ch_tag);

if ($response_tag) {
    $tags_data = json_decode($response_tag, true);
    if (is_array($tags_data) && !empty($tags_data[0]['name'])) {
        $latest_tag_name = ltrim($tags_data[0]['name'], 'v');
        $son_versiyon = $latest_tag_name;
        $release_notlari = "GitHub En Son Etiket (Tag): v" . $son_versiyon . " - Güncellemeye hazır.";
    }
}

if (version_compare($son_versiyon, $mevcut_versiyon, '>')) {
    $guncelleme_var = true;
}

// 3. Rollback Yedekleri
$yedek_dizini = __DIR__ . "/yedekler/";
$rollback_yedekleri = [];
if (is_dir($yedek_dizini)) {
    $files = array_diff(scandir($yedek_dizini), ['.', '..']);
    foreach ($files as $f) {
        if (strpos($f, 'AUTO_BEFORE_UPDATE_') !== false && pathinfo($f, PATHINFO_EXTENSION) === 'zip') {
            $rollback_yedekleri[] = $f;
        }
    }
    rsort($rollback_yedekleri);
}

$mesaj = ""; $mesaj_tur = "";
if (isset($_GET['durum'])) {
    switch ($_GET['durum']) {
        case 'guncellendi': 
        case 'basarili': 
            $mesaj = "🚀 Tebrikler! Sistem başarıyla v" . htmlspecialchars($_GET['v'] ?? $_GET['version'] ?? $son_versiyon) . " sürümüne güncellendi."; 
            $mesaj_tur = "success"; 
            break;
        case 'rollback_ok': 
            $mesaj = "🔄 Sistem başarıyla eskiye döndürüldü!"; 
            $mesaj_tur = "warning"; 
            break;
        case 'hata': 
            $mesaj = "❌ Güncelleme Hatası Detayı: " . htmlspecialchars($_GET['msg'] ?? 'Bilinmeyen hata.'); 
            $mesaj_tur = "danger"; 
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sistem Güncelleme | ZiraatBox</title>
    <style>
        :root { --admin-dark: #1a202c; --admin-green: #27ae60; --admin-orange: #ed8936; --admin-red: #e53e3e; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f4f7f6; display: flex; }
        .content { flex: 1; padding: 30px; box-sizing: border-box; }
        .card { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .version-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-weight: 800; font-size: 13px; }
        .badge-current { background: #e2e8f0; color: #2d3748; }
        .badge-latest { background: #dcfce7; color: #166534; }
        .badge-new { background: #feebc8; color: #c05621; }
        .btn { padding: 12px 24px; border-radius: 10px; font-weight: 800; text-decoration: none; display: inline-block; cursor: pointer; border: none; font-size: 14px; transition: 0.2s; }
        .btn-update { background: var(--admin-green); color: white; }
        .btn-update:hover { background: #219150; }
        .btn-force { background: var(--admin-orange); color: white; }
        .btn-force:hover { background: #dd6b20; }
        .alert { padding: 16px 20px; border-radius: 10px; margin-bottom: 25px; font-weight: 700; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-warning { background: #fef3c7; color: #92400e; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        .notes-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 10px; font-family: monospace; font-size: 13px; color: #475569; margin: 15px 0; }
    </style>
</head>
<body>

<?php include "sol_menu.php"; ?>

<div class="content">
    <h1 style="margin: 0 0 30px; color: #1a202c;">🚀 Canlı Sunucu Güncelleme Merkezi</h1>

    <?php if ($mesaj): ?>
        <div class="alert alert-<?php echo $mesaj_tur; ?>"><?php echo $mesaj; ?></div>
    <?php endif; ?>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin:0 0 10px; color:#2d3748;">Mevcut Sürüm: <span class="version-badge badge-current">v<?php echo htmlspecialchars($mevcut_versiyon); ?></span></h2>
                <p style="margin:0; color:#718096; font-size:14px;">GitHub Deposu: <b><?php echo htmlspecialchars($github_repo); ?></b></p>
            </div>
            <div>
                <?php if ($guncelleme_var): ?>
                    <span class="version-badge badge-new">Yeni Sürüm Mevcut: v<?php echo htmlspecialchars($son_versiyon); ?></span>
                <?php else: ?>
                    <span class="version-badge badge-latest">✅ Sisteminiz Güncel (v<?php echo htmlspecialchars($son_versiyon); ?>)</span>
                <?php endif; ?>
            </div>
        </div>

        <hr style="border:0; border-top:1px solid #edf2f7; margin:20px 0;">
        <div class="notes-box"><?php echo htmlspecialchars($release_notlari); ?></div>

        <div style="display:flex; gap:12px; margin-top:20px;">
            <a href="islem/guncelle-yap.php?islem=guncelle&version=<?php echo urlencode($son_versiyon); ?>" 
               onclick="return confirm('Güncelleme başlatılsın mı?')" 
               class="btn btn-update">⚡ GÜNCELLEMEYİ BAŞLAT (v<?php echo htmlspecialchars($son_versiyon); ?>)</a>

            <a href="islem/guncelle-yap.php?islem=guncelle&version=<?php echo urlencode($son_versiyon); ?>&force=1" 
               onclick="return confirm('Zorla güncelleme yapılsın mı?')" 
               class="btn btn-force">🔄 ZORLA GÜNCELLE (Main Branch Çek)</a>
        </div>
    </div>
</div>

</body>
</html>
