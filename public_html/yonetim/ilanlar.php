<?php 
require_once "../sistem/ayar.php"; 

if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    header("Location: giris.php"); exit;
}

// 1. ADIM: İŞLEMLER VE FİLTRE KONTROLÜ
$filtre   = isset($_GET['filtre']) ? g($_GET['filtre']) : '';
$vurgu_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$url_ek   = ($filtre == 'bekleyen') ? "&filtre=bekleyen" : "";

if(isset($_GET['islem']) && isset($_GET['id'])){
    $id = intval($_GET['id']);
    if($_GET['islem'] == "onayla") $db->prepare("UPDATE ilanlar SET durum = 'aktif' WHERE id = ?")->execute([$id]);
    elseif($_GET['islem'] == "reddet") $db->prepare("UPDATE ilanlar SET durum = 'pasif' WHERE id = ?")->execute([$id]);
    elseif($_GET['islem'] == "sil") $db->prepare("DELETE FROM ilanlar WHERE id = ?")->execute([$id]);
    header("Location: ilanlar.php?durum=ok".$url_ek); exit;
}

$onay_bekleyen = $db->query("SELECT COUNT(*) FROM ilanlar WHERE durum = 'beklemede'")->fetchColumn();
$yeni_mesaj    = $db->query("SELECT COUNT(*) FROM mesajlar WHERE okundu = 0")->fetchColumn();

// 2. ADIM: SORGULAMA MANTIĞI (ip_adresi ve user_agent eklendi)
$sql = "SELECT i.*, u.ad_soyad, u.telefon, k.adi as kat_adi,
        (SELECT dosya_adi FROM ilan_resimleri WHERE ilan_id = i.id ORDER BY ana_resim DESC LIMIT 1) as kapak 
        FROM ilanlar i 
        JOIN uyeler u ON i.uye_id = u.id
        LEFT JOIN kategoriler k ON i.kategori_id = k.id";

if($filtre == 'bekleyen') {
    $sql .= " WHERE i.durum = 'beklemede' ORDER BY i.id DESC";
} else {
    $sql .= ($vurgu_id > 0) ? " ORDER BY (i.id = $vurgu_id) DESC, i.id DESC" : " ORDER BY i.id DESC";
}

$ilanlar = $db->query($sql)->fetchAll();

$otomatik_ilan_verisi = null;
if($vurgu_id > 0) {
    foreach($ilanlar as $ilan) {
        if($ilan['id'] == $vurgu_id) { $otomatik_ilan_verisi = $ilan; break; }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İlan Yönetimi | ZiraatBox</title>
    <style>
        :root { --admin-dark: #1a202c; --admin-green: #27ae60; --admin-orange: #ed8936; --admin-blue: #3182ce; --admin-red: #e53e3e; --admin-gray: #718096; }
        body { margin: 0; font-family: 'Inter', 'Segoe UI', sans-serif; background: #f4f7f6; display: flex; }
        .sidebar { width: 260px; background: var(--admin-dark); color: #fff; min-height: 100vh; padding: 20px; box-sizing: border-box; position: sticky; top: 0; }
        .sidebar h2 { color: #48bb78; margin-bottom: 30px; font-size: 24px; }
        .sidebar a { display: block; color: #cbd5e0; padding: 12px; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #2d3748; color: #fff; }
        .view-site-btn { display: flex; align-items: center; justify-content: center; background: var(--admin-green); color: #fff !important; padding: 12px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; }
        .content { flex: 1; padding: 35px; box-sizing: border-box; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #edf2f7; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 18px; background: #f8fafc; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid #edf2f7; }
        td { padding: 15px 18px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }
        .badge { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; }
        .badge-pending { background: #fffaf0; color: #c05621; border: 1px solid #fbd38d; }
        .badge-active { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .islem-btn { border:0; padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; color: #fff; }
        .btn-incele { background: var(--admin-blue); }
        .btn-onay { background: var(--admin-green); }
        .btn-sil { background: var(--admin-red); }
        .ilan-thumb { width: 65px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; }
        .modal-overlay { display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.8); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .modal-content { background: #fff; width: 90%; max-width: 850px; border-radius: 20px; padding: 0; overflow: hidden; position: relative; animation: modalSlide 0.3s ease-out; }
        
        /* Güvenlik Kutusu Stili */
        .security-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; margin-top: 20px; }
        .security-title { font-size: 11px; font-weight: 800; color: #e53e3e; text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 5px; }
        
        @keyframes modalSlide { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Ziraat<span style="color:#f39c12">Box</span></h2>
    <a href="../index.php" target="_blank" class="view-site-btn">🌐 Siteyi Görüntüle</a>
    <nav>
        <a href="index.php">🏠 Panel Özeti</a>
        <a href="ilanlar.php" class="<?php echo ($filtre == '') ? 'active' : ''; ?>">📦 Tüm İlanlar</a>
        <a href="ilanlar.php?filtre=bekleyen" class="<?php echo ($filtre == 'bekleyen') ? 'active' : ''; ?>">⏳ Bekleyenler (<b><?php echo $onay_bekleyen; ?></b>)</a>
        <a href="kategoriler.php">📂 Kategori Yönetimi</a>
        <a href="ozellikler.php">🛠️ Özellik Havuzu</a>
        <a href="uyeler.php">👥 Üye Yönetimi</a>
        <a href="mesajlar.php">💬 Mesajlar (<b><?php echo $yeni_mesaj; ?></b>)</a>
        <a href="ayarlar.php">⚙️ Genel Ayarlar</a>
    </nav>
</div>

<div class="content">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
        <div>
            <h1 style="margin: 0; font-weight: 800; color: #1a202c;"><?php echo ($filtre == 'bekleyen') ? '⏳ Onay Bekleyenler' : '📦 İlan Havuzu'; ?></h1>
            <p style="color: #718096; margin-top: 5px;">Yer Sağlayıcı (5651) kanununa uygun log kayıtları aşağıdadır.</p>
        </div>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Görsel</th>
                    <th>İlan Bilgileri</th>
                    <th>Satıcı</th>
                    <th>Durum</th>
                    <th style="text-align: right;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($ilanlar as $i): 
                    $img = !empty($i['kapak']) ? "../yuklemeler/ilanlar/".$i['kapak'] : "../dosyalar/resim/yok.png";
                ?>
                <tr id="row-<?php echo $i['id']; ?>" class="<?php echo ($i['id'] == $vurgu_id) ? 'vurgu' : ''; ?>">
                    <td><img src="<?php echo $img; ?>" class="ilan-thumb"></td>
                    <td>
                        <div style="font-weight: 700; color: #1a202c;"><?php echo htmlspecialchars($i['baslik']); ?></div>
                        <div style="font-size: 12px; color: #27ae60; font-weight: 800;"><?php echo number_format($i['fiyat'],0,',','.'); ?> TL</div>
                    </td>
                    <td>
                        <div style="font-weight: 600; font-size: 13px;"><?php echo htmlspecialchars($i['ad_soyad']); ?></div>
                        <div style="font-size: 11px; color: #718096;">IP: <?php echo !empty($i['ip_adresi']) ? $i['ip_adresi'] : 'Kayıt Yok'; ?></div>
                    </td>
                    <td>
                        <span class="badge <?php echo ($i['durum'] == 'beklemede') ? 'badge-pending' : 'badge-active'; ?>">
                            <?php echo ($i['durum'] == 'beklemede') ? 'BEKLEYEN' : 'YAYINDA'; ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <button class="islem-btn btn-incele" onclick='ilanIncele(<?php echo json_encode($i); ?>)'>🔍 İncele & Log</button>
                        <a href="ilanlar.php?islem=sil&id=<?php echo $i['id'].$url_ek; ?>" class="islem-btn btn-sil" onclick="return confirm('İlan tamamen silinecek?')">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="inceleModal" class="modal-overlay">
    <div class="modal-content">
        <div style="padding: 20px 25px; background: #f8fafc; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="m-baslik" style="margin:0; color:var(--admin-dark);">İlan Detayı</h3>
            <span style="cursor:pointer; font-size:24px; color:#999;" onclick="modalKapat()">&times;</span>
        </div>
        <div id="modalGovde" style="padding: 25px;"></div>
    </div>
</div>

<script>
function ilanIncele(ilan) {
    const govde = document.getElementById('modalGovde');
    const mBaslik = document.getElementById('m-baslik');
    mBaslik.innerText = ilan.baslik;
    
    govde.innerHTML = `
        <div style="display:flex; gap:25px;">
            <div style="flex:1;">
                <img src="../yuklemeler/ilanlar/${ilan.kapak || 'yok.png'}" style="width:100%; border-radius:12px; border:1px solid #eee;">
                <div style="margin-top:15px; background:#f0fdf4; padding:12px; border-radius:10px; text-align:center; border:1px solid #bbf7d0;">
                    <span style="display:block; font-size:11px; color:#166534; font-weight:800;">İLAN FİYATI</span>
                    <strong style="font-size:22px; color:#27ae60;">${new Intl.NumberFormat('tr-TR').format(ilan.fiyat)} TL</strong>
                </div>
            </div>
            <div style="flex:1.5;">
                <div style="margin-bottom:15px; border-bottom:1px solid #f1f5f9; pb-10">
                    <label style="display:block; font-size:11px; font-weight:800; color:#a0aec0; text-transform:uppercase;">Satıcı & İletişim</label>
                    <div style="font-weight:700; font-size:15px; color:#1a202c;">${ilan.ad_soyad}</div>
                    <div style="color:#718096;">Tel: ${ilan.telefon}</div>
                </div>
                
                <div style="margin-bottom:15px;">
                    <label style="display:block; font-size:11px; font-weight:800; color:#a0aec0; text-transform:uppercase;">Açıklama</label>
                    <div style="font-size:13px; color:#4a5568; line-height:1.5; max-height:120px; overflow-y:auto; padding-right:5px; background:#fbfcfd; border-radius:8px; padding:10px; border:1px solid #f1f5f9;">${ilan.aciklama}</div>
                </div>

                <div class="security-box">
                    <div class="security-title">🛡️ 5651 Sayılı Kanun Güvenlik Logları</div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-size:12px;">
                        <div>
                            <span style="color:#94a3b8; display:block;">İlan Kayıt IP</span>
                            <strong style="color:#1a202c;">${ilan.ip_adresi || 'Kayıt Yok'}</strong>
                        </div>
                        <div>
                            <span style="color:#94a3b8; display:block;">İşlem Zamanı</span>
                            <strong style="color:#1a202c;">${ilan.created_at}</strong>
                        </div>
                        <div style="grid-column: span 2; margin-top:5px; padding-top:5px; border-top:1px dashed #cbd5e0;">
                            <span style="color:#94a3b8; display:block;">Cihaz Parmak İzi (User Agent)</span>
                            <code style="display:block; background:#fff; padding:5px; border-radius:4px; font-size:10px; color:#4a5568; border:1px solid #e2e8f0; white-space: normal; word-break: break-all;">${ilan.user_agent || 'Bilinmiyor'}</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div style="margin-top:25px; padding-top:20px; border-top:1px solid #eee; display:flex; gap:10px; justify-content:flex-end;">
            <a href="ilanlar.php?islem=reddet&id=${ilan.id}<?php echo $url_ek; ?>" class="islem-btn" style="background:#718096; padding:12px 20px;">YAYINDAN KALDIR</a>
            <a href="ilanlar.php?islem=onayla&id=${ilan.id}<?php echo $url_ek; ?>" class="islem-btn btn-onay" style="padding:12px 20px;">İLANI ONAYLA</a>
        </div>
    `;
    document.getElementById('inceleModal').style.display = 'flex';
}

function modalKapat() { document.getElementById('inceleModal').style.display = 'none'; }

window.onload = function() {
    <?php if($otomatik_ilan_verisi): ?>
        ilanIncele(<?php echo json_encode($otomatik_ilan_verisi); ?>);
    <?php endif; ?>
}
</script>

</body>
</html>