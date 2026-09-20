<?php 
require_once "../sistem/ayar.php"; 

if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    header("Location: giris.php"); exit;
}

$dizin = "yedekler/";
if (!is_dir($dizin)) { mkdir($dizin, 0755, true); }
$dosyalar = array_diff(scandir($dizin), array('..', '.', '.htaccess'));

// Tarihe göre sıralama
usort($dosyalar, function($a, $b) use ($dizin) {
    return filemtime($dizin . $b) - filemtime($dizin . $a);
});

// Gruplandırma
$sql_list = []; $kod_sql_list = []; $full_list = [];
foreach($dosyalar as $dosya) {
    if(strpos($dosya, 'DB_') !== false) $sql_list[] = $dosya;
    elseif(strpos($dosya, 'Kod_') !== false) $kod_sql_list[] = $dosya;
    elseif(strpos($dosya, 'FULL_') !== false || strpos($dosya, 'AUTO_') !== false) $full_list[] = $dosya;
}

// Bildirimler
$mesaj = ""; $mesaj_tur = "";
if(isset($_GET['islem'])){
    switch($_GET['islem']){
        case 'ok': $mesaj = "✅ Yedekleme başarıyla tamamlandı."; $mesaj_tur = "success"; break;
        case 'silindi': $mesaj = "🗑️ Yedek dosyası silindi."; $mesaj_tur = "info"; break;
        case 'restore_ok': $mesaj = "🚀 Sistem başarıyla geri yüklendi!"; $mesaj_tur = "restore"; break;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yedekleme Merkezi | ZiraatBox</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --side-bg: #0f172a; --accent: #3b82f6; --text-gray: #94a3b8; --body-bg: #f1f5f9; }
        body { margin: 0; font-family: 'Inter', sans-serif; background: var(--body-bg); display: flex; height: 100vh; overflow: hidden; }

        .sidepad { width: 320px; background: var(--side-bg); padding: 25px; box-sizing: border-box; display: flex; flex-direction: column; border-right: 1px solid rgba(255,255,255,0.05); }
        .back-btn { display: flex; align-items: center; justify-content: center; background: #1e293b; color: #fff; text-decoration: none; padding: 15px; border-radius: 12px; margin-bottom: 35px; font-weight: 800; font-size: 13px; gap: 10px; border: 1px solid #334155; transition: 0.3s; }
        .back-btn:hover { background: #334155; border-color: var(--accent); }
        
        .side-title { color: var(--text-gray); font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 15px; margin-left: 10px; }
        .side-item { display: flex; align-items: center; padding: 14px 18px; border-radius: 12px; color: #cbd5e0; cursor: pointer; margin-bottom: 8px; transition: 0.3s; font-size: 14px; font-weight: 600; }
        .side-item i { width: 28px; font-size: 18px; opacity: 0.7; }
        .side-item:hover { background: #1e293b; color: #fff; }
        .side-item.active { background: var(--accent); color: #fff; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3); }

        .content-area { flex: 1; padding: 40px; overflow-y: auto; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; animation: slideUp 0.4s ease-out; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .action-card { background: #fff; border-radius: 24px; padding: 35px; border: 1px solid #e2e8f0; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .btn-main { background: var(--accent); color: #fff; border: none; padding: 16px 32px; border-radius: 14px; font-weight: 800; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; font-size: 15px; transition: 0.2s; }
        .btn-main:hover { opacity: 0.9; transform: translateY(-2px); }
        
        .table-card { background: #fff; border-radius: 24px; border: 1px solid #e2e8f0; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 18px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 20px 18px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .btn-tool { padding: 8px; border-radius: 8px; text-decoration: none; color: #64748b; background: #f1f5f9; margin-left: 5px; transition: 0.2s; }
        .btn-tool:hover { background: #e2e8f0; color: #1e293b; }

        .alert { padding: 18px; border-radius: 15px; margin-bottom: 30px; font-weight: 700; border-left: 6px solid; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #166534; border-color: #22c55e; }
        .alert-restore { background: #fef3c7; color: #92400e; border-color: #f59e0b; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        select { padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; font-weight: 600; font-family: inherit; }
    </style>
</head>
<body>

<div class="sidepad">
    <a href="index.php" class="back-btn"><i class="fa-solid fa-circle-arrow-left"></i> YÖNETİM PANELİ</a>

    <span class="side-title">Yedekleme İşlemleri</span>
    <div class="side-item active" onclick="openTab(event, 'sql')"><i class="fa-solid fa-database"></i> Veritabanı (SQL)</div>
    <div class="side-item" onclick="openTab(event, 'kod')"><i class="fa-solid fa-file-code"></i> Sayfalar + SQL</div>
    <div class="side-item" onclick="openTab(event, 'full')"><i class="fa-solid fa-box-archive"></i> Tam Yedek (Full)</div>

    <div style="margin-top: 30px;">
        <span class="side-title">Sistem & Araçlar</span>
        <div class="side-item" onclick="openTab(event, 'restore')"><i class="fa-solid fa-clock-rotate-left"></i> Geri Yükleme Arşivi</div>
        <div class="side-item" onclick="openTab(event, 'cron')"><i class="fa-solid fa-robot"></i> Otomatik Görevler</div>
    </div>
</div>

<div class="content-area">
    <?php if($mesaj): ?>
        <div class="alert alert-<?php echo ($mesaj_tur=='restore'?'restore':'success'); ?>"><?php echo $mesaj; ?></div>
    <?php endif; ?>

    <div id="sql" class="tab-panel active">
        <div class="action-card">
            <h1 style="margin-top:0;">💾 Veritabanı (SQL) Yedekleme</h1>
            <p style="color:#64748b; margin-bottom:25px;">Sadece tabloları ve verileri içeren güvenli SQL dökümü oluşturur.</p>
            <a href="islem/yedek-al.php?tip=sql" class="btn-main"><i class="fa-solid fa-plus-circle"></i> ŞİMDİ SQL YEDEKLE</a>
        </div>
        <div class="table-card">
            <?php renderBackupTable($sql_list, $dizin); ?>
        </div>
    </div>

    <div id="kod" class="tab-panel">
        <div class="action-card">
            <h1 style="margin-top:0;">📄 Sayfalar + SQL Yedekleme</h1>
            <p style="color:#64748b; margin-bottom:25px;">İlan resimleri hariç tüm sistem dosyalarını ve veritabanını paketler.</p>
            <a href="islem/yedek-al.php?tip=no_images" class="btn-main" style="background:#f59e0b;"><i class="fa-solid fa-file-export"></i> SAYFALARI YEDEKLE</a>
        </div>
        <div class="table-card">
            <?php renderBackupTable($kod_sql_list, $dizin); ?>
        </div>
    </div>

    <div id="full" class="tab-panel">
        <div class="action-card">
            <h1 style="margin-top:0;">📦 Tam Sistem Yedekleme (Full)</h1>
            <p style="color:#64748b; margin-bottom:25px;">Veritabanı, kodlar ve yüklenen tüm resimler dahil her şeyi paketler.</p>
            <a href="islem/yedek-al.php?tip=full" class="btn-main" style="background:#10b981;"><i class="fa-solid fa-layer-group"></i> TAM YEDEK OLUŞTUR</a>
        </div>
        <div class="table-card">
            <?php renderBackupTable($full_list, $dizin); ?>
        </div>
    </div>

    <div id="restore" class="tab-panel">
        <div style="margin-bottom:30px;">
            <h1 style="margin:0;">🔄 Geri Yükleme Arşivi</h1>
            <p style="color:#64748b;">Sunucudaki tüm yedekler en yeni tarihten itibaren listelenir.</p>
        </div>
        <div class="table-card">
            <?php renderBackupTable($dosyalar, $dizin, true); ?>
        </div>
    </div>

    <div id="cron" class="tab-panel">
        <div class="action-card">
            <h1>🤖 Otomatik Görev Yapılandırması</h1>
            <p style="color:#64748b; margin-bottom:25px;">Sisteminizin belirlediğiniz aralıklarla neyi yedekleyeceğini seçin.</p>
            <form action="islem/yedek-ayar-kaydet.php" method="POST">
                <div class="form-row">
                    <div>
                        <label style="font-weight:700; display:block; margin-bottom:10px;">Yedekleme Sıklığı</label>
                        <select name="yedek_siklik">
                            <option value="kapali">❌ Kapalı</option>
                            <option value="saatlik">⏰ Her Saat Başı</option>
                            <option value="gunluk" selected>☀️ Her Gün (03:00)</option>
                            <option value="haftalik">📅 Her Hafta</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:700; display:block; margin-bottom:10px;">Yedekleme Kapsamı</label>
                        <select name="yedek_icerik">
                            <option value="sql">💾 Sadece Veritabanı (SQL)</option>
                            <option value="kod_sql">📄 Sayfalar + SQL (Görsel Hariç)</option>
                            <option value="full" selected>📦 Tam Yedek (Her Şey Dahil)</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-main">✅ OTOMATİK GÖREV AYARLARINI KAYDET</button>
            </form>
        </div>
    </div>
</div>

<?php 
function renderBackupTable($files, $dizin, $isRestore = false) {
    if(empty($files)) { echo "<div style='padding:50px; text-align:center; color:#94a3b8;'>Bu kategoride kayıt bulunamadı.</div>"; return; }
    echo "<table><thead><tr><th>DOSYA ADI / TARİH</th><th>BOYUT</th><th style='text-align:right;'>İŞLEM</th></tr></thead><tbody>";
    foreach($files as $f) {
        $yol = $dizin.$f;
        $boyut = round(filesize($yol) / 1024 / 1024, 2);
        $tarih = date("d.m.Y H:i", filemtime($yol));
        echo "<tr>
                <td><b style='color:#1e293b'>$f</b><br><small style='color:#94a3b8'>$tarih</small></td>
                <td><span style='font-weight:700; color:#64748b'>$boyut MB</span></td>
                <td style='text-align:right;'>
                    <a href='islem/yedek-indir.php?dosya=$f' class='btn-tool' title='İndir'><i class='fa-solid fa-download'></i></a>
                    ".($isRestore ? "<button onclick=\"doRestore('$f')\" class='btn-tool' style='color:#f59e0b; border:none; cursor:pointer;' title='Geri Yükle'><i class='fa-solid fa-rotate-left'></i></button>" : "")."
                    <a href='islem/yedek-sil.php?dosya=$f' class='btn-tool' style='color:#ef4444' onclick=\"return confirm('Silinsin mi?')\" title='Sil'><i class='fa-solid fa-trash'></i></a>
                </td>
              </tr>";
    }
    echo "</tbody></table>";
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function openTab(evt, tabName) {
    document.querySelectorAll(".tab-panel").forEach(tp => tp.classList.remove("active"));
    document.querySelectorAll(".side-item").forEach(si => si.classList.remove("active"));
    document.getElementById(tabName).classList.add("active");
    evt.currentTarget.classList.add("active");
}

function doRestore(dosya) {
    Swal.fire({
        title: '⚠️ SİSTEM GERİ YÜKLENECEK!',
        html: "<b>" + dosya + "</b> yedeği geri yüklenecektir.<br>Mevcut her şey silinecek ve bu tarihe dönülecektir!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        confirmButtonText: 'Evet, Geri Yükle!',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'İşlem Başlatıldı...', didOpen: () => { Swal.showLoading(); } });
            window.location.href = 'islem/yedek-yukle.php?dosya=' + dosya;
        }
    });
}
</script>
</body>
</html>