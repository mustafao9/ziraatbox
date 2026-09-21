<?php 
require_once "../sistem/ayar.php"; 

if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    header("Location: giris.php"); exit;
}

// 🚀 MODERASYON MOTORU
if(isset($_POST['moderasyon_aksiyon'])){
    $sikayet_id = intval($_POST['sikayet_id']);
    $ilan_id    = intval($_POST['ilan_id']);
    $uye_id     = intval($_POST['ilan_sahibi_id']);
    $sikayetci_id = intval($_POST['sikayetci_id']);
    $aksiyon    = $_POST['aksiyon']; // kaldir, durdur, devam, reddet
    $admin_notu = htmlspecialchars($_POST['admin_notu']);

    // 1. İLAN DURUMUNU GÜNCELLE
    if($aksiyon == 'kaldir') {
        $db->prepare("UPDATE ilanlar SET durum = 'pasif' WHERE id = ?")->execute([$ilan_id]);
        $bildirim_sahibi = "İlanınız kurallara aykırı görüldüğü için kalıcı olarak yayından kaldırılmıştır.";
        $bildirim_sikayetci = "Şikayetiniz haklı bulundu, ilan yayından kaldırıldı.";
    } elseif($aksiyon == 'durdur') {
        $db->prepare("UPDATE ilanlar SET durum = 'beklemede' WHERE id = ?")->execute([$ilan_id]);
        $bildirim_sahibi = "İlanınızdaki eksiklikler nedeniyle yayını durdurulmuştur. Lütfen bilgileri güncelleyip onay bekleyin.";
        $bildirim_sikayetci = "İlan incelemeye alınmış ve geçici olarak yayını durdurulmuştur.";
    } elseif($aksiyon == 'devam' || $aksiyon == 'reddet') {
        $db->prepare("UPDATE ilanlar SET durum = 'aktif' WHERE id = ?")->execute([$ilan_id]);
        $bildirim_sahibi = "İlanınızla ilgili yapılan şikayet asılsız görülmüştür, yayınınız devam ediyor.";
        $bildirim_sikayetci = "İlan incelendi ve kurallara aykırı bir durum bulunamadı. Şikayetiniz reddedildi.";
    }

    // 2. ŞİKAYETİ SONUÇLANDIR
    $db->prepare("UPDATE sikayetler SET durum = 'kapatildi', sonuc_mesaji = ? WHERE id = ?")
       ->execute([$admin_notu, $sikayet_id]);

    // 3. MESAJ SİSTEMİ ÜZERİNDEN BİLDİRİM GÖNDER (Basitleştirilmiş Mesaj Kayıt)
    // Bu kısım sizin mesaj tablonuzun yapısına göre entegre edilmelidir.
    
    header("Location: sikayetler.php?islem=basarili"); exit;
}

// Verileri Çek
$sql = "SELECT s.*, u.ad_soyad as sikayetci, u.id as sikayetci_id,
               i.baslik, i.fiyat, i.aciklama, i.uye_id as ilan_sahibi_id, i.durum as ilan_durumu,
               (SELECT dosya_adi FROM ilan_resimleri WHERE ilan_id = i.id ORDER BY ana_resim DESC LIMIT 1) as kapak
        FROM sikayetler s 
        JOIN uyeler u ON s.sikayetci_id = u.id 
        LEFT JOIN ilanlar i ON s.ilan_id = i.id 
        ORDER BY s.id DESC";
$sikayetler = $db->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şikayet Moderasyonu | ZiraatBox</title>
    <style>
        :root { --z-red: #e53e3e; --z-orange: #ed8936; --z-blue: #3182ce; --z-green: #38a169; }
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; display:flex; margin:0; }
        .sidebar { width: 260px; background: #1a202c; color: #fff; min-height: 100vh; padding: 20px; }
        .content { flex: 1; padding: 30px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        
        /* Modal & Denetim Kartı */
        .modal { display:none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center; }
        .denetim-karti { background:#fff; width:900px; border-radius:20px; display:flex; overflow:hidden; animation: zoomIn 0.3s; }
        .denetim-sol { flex:1; background:#f8fafc; padding:25px; border-right:1px solid #eee; }
        .denetim-sag { flex:1; padding:25px; display:flex; flex-direction:column; }
        
        .aksiyon-btn { padding:12px; border:none; border-radius:8px; color:#fff; font-weight:700; cursor:pointer; margin-bottom:10px; transition:0.2s; }
        @keyframes zoomIn { from {transform:scale(0.9); opacity:0;} to {transform:scale(1); opacity:1;} }
    </style>
</head>
<body>

<div class="sidebar">
    <h2 style="color:#48bb78;">ZiraatBox</h2>
    <a href="index.php" style="color:#fff; text-decoration:none;">🏠 Panele Dön</a>
</div>

<div class="content">
    <h1>🛡️ Şikayet Denetim Merkezi</h1>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Şikayet Eden</th>
                    <th>İlan / Hedef</th>
                    <th>Mesaj</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($sikayetler as $s): ?>
                <tr>
                    <td><b><?php echo $s['sikayetci']; ?></b></td>
                    <td><?php echo mb_substr($s['baslik'], 0, 30); ?>...</td>
                    <td><small><?php echo $s['mesaj']; ?></small></td>
                    <td><span style="color:<?php echo $s['durum'] == 'beklemede' ? 'orange' : 'gray'; ?>"><?php echo strtoupper($s['durum']); ?></span></td>
                    <td>
                        <button onclick='denetle(<?php echo json_encode($s); ?>)' style="background:var(--z-blue); color:#fff; border:none; padding:8px 12px; border-radius:6px; cursor:pointer;">İncele & Karar Ver</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="denetimModal" class="modal">
    <div class="denetim-karti">
        <div class="denetim-sol">
            <h3 id="d-baslik">İlan Başlığı</h3>
            <img id="d-resim" src="" style="width:100%; border-radius:10px; margin-bottom:15px;">
            <div id="d-aciklama" style="font-size:13px; color:#4a5568; line-height:1.5; max-height:200px; overflow-y:auto; background:#fff; padding:10px; border-radius:8px; border:1px solid #eee;"></div>
            <hr>
            <div style="background:#fff5f5; padding:10px; border-radius:8px; border:1px solid #feb2b2;">
                <label style="font-weight:800; color:#c53030; font-size:11px;">GELEN ŞİKAYET:</label>
                <p id="d-sikayet-mesaj" style="font-size:13px; margin:5px 0;"></p>
            </div>
        </div>
        
        <form class="denetim-sag" method="POST">
            <input type="hidden" name="sikayet_id" id="form-sid">
            <input type="hidden" name="ilan_id" id="form-iid">
            <input type="hidden" name="ilan_sahibi_id" id="form-usid">
            <input type="hidden" name="sikayetci_id" id="form-scid">
            
            <h3>Hakem Kararı</h3>
            <p style="font-size:12px; color:#718096;">Bu karar sonucunda taraflara otomatik bildirim gidecektir.</p>
            
            <label style="font-size:13px; font-weight:700;">Aksiyon Seçin:</label>
            <select name="aksiyon" class="aksiyon-btn" style="background:#fff; color:#333; border:1px solid #ddd;" required>
                <option value="devam">✅ Şikayeti Reddet (Yayına Devam)</option>
                <option value="durdur">⏳ Yayını Durdur (Düzenleme İste)</option>
                <option value="kaldir">🚫 İlanı Kalıcı Olarak Kaldır</option>
            </select>

            <label style="font-size:13px; font-weight:700; margin-top:10px;">Sonuç Mesajı (Taraflara Gidecek):</label>
            <textarea name="admin_notu" style="flex:1; padding:10px; border-radius:8px; border:1px solid #ddd; margin-bottom:15px;" placeholder="Bu ilan, belirttiğiniz hususlar çerçevesinde incelenmiş olup..." required></textarea>
            
            <div style="display:flex; gap:10px;">
                <button type="button" onclick="closeModal()" style="flex:1; background:#edf2f7; color:#4a5568;" class="aksiyon-btn">Vazgeç</button>
                <button type="submit" name="moderasyon_aksiyon" style="flex:2; background:var(--z-green);" class="aksiyon-btn">KARARI UYGULA VE BİLDİR</button>
            </div>
        </form>
    </div>
</div>

<script>
function denetle(data) {
    document.getElementById('form-sid').value = data.id;
    document.getElementById('form-iid').value = data.ilan_id;
    document.getElementById('form-usid').value = data.ilan_sahibi_id;
    document.getElementById('form-scid').value = data.sikayetci_id;
    
    document.getElementById('d-baslik').innerText = data.baslik;
    document.getElementById('d-resim').src = "../uploads/ilanlar/" + (data.kapak || 'yok.png');
    document.getElementById('d-aciklama').innerText = data.aciklama;
    document.getElementById('d-sikayet-mesaj').innerText = data.mesaj;
    
    document.getElementById('denetimModal').style.display = 'flex';
}
function closeModal() { document.getElementById('denetimModal').style.display = 'none'; }
</script>

</body>
</html>