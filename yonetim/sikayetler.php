<?php  
require_once "../sistem/ayar.php";  

if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){ 
    header("Location: giris.php"); exit; 
} 

// 🚀 MODERASYON MOTORU 
if(isset($_POST['moderasyon_aksiyon'])){ 
    $sikayet_id   = intval($_POST['sikayet_id']); 
    $ilan_id      = intval($_POST['ilan_id']); 
    $aksiyon      = $_POST['aksiyon'] ?? 'devam'; 
    $admin_notu   = htmlspecialchars($_POST['admin_notu'] ?? ''); 

    // 1. İLAN DURUMUNU GÜNCELLE 
    if($ilan_id > 0) {
        if($aksiyon == 'kaldir') { 
            $db->prepare("UPDATE ilanlar SET durum = 'pasif' WHERE id = ?")->execute([$ilan_id]); 
        } elseif($aksiyon == 'durdur') { 
            $db->prepare("UPDATE ilanlar SET durum = 'beklemede' WHERE id = ?")->execute([$ilan_id]); 
        } elseif($aksiyon == 'devam' || $aksiyon == 'reddet') { 
            $db->prepare("UPDATE ilanlar SET durum = 'aktif' WHERE id = ?")->execute([$ilan_id]); 
        } 
    }

    // 2. ŞİKAYETİ SONUÇLANDIR 
    $db->prepare("UPDATE sikayetler SET durum = 'kapatildi', sonuc_mesaji = ? WHERE id = ?") 
       ->execute([$admin_notu, $sikayet_id]); 
     
    header("Location: sikayetler.php?islem=basarili"); exit; 
} 

// Verileri Güvenli Çek 
$sql = "SELECT s.*, u.ad_soyad as sikayetci, u.id as sikayetci_id, 
               i.baslik, i.fiyat, i.aciklama, i.uye_id as ilan_sahibi_id, i.durum as ilan_durumu, 
               (SELECT dosya_adi FROM ilan_resimleri WHERE ilan_id = i.id ORDER BY ana_resim DESC, id ASC LIMIT 1) as kapak 
        FROM sikayetler s  
        LEFT JOIN uyeler u ON s.sikayetci_id = u.id  
        LEFT JOIN ilanlar i ON s.ilan_id = i.id  
        ORDER BY s.id DESC"; 
$sikayetler = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC); 
?> 

<!DOCTYPE html> 
<html lang="tr"> 
<head> 
    <meta charset="UTF-8"> 
    <title>Şikayet Moderasyonu | ZiraatBox</title> 
    <style> 
        :root { --z-red: #e53e3e; --z-orange: #ed8936; --z-blue: #3182ce; --z-green: #38a169; } 
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; display:flex; margin:0; } 
        .content { flex: 1; padding: 30px; box-sizing:border-box; } 
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; } 
        table { width: 100%; border-collapse: collapse; } 
        th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; } 
        td { padding: 15px; border-bottom: 1px solid #edf2f7; font-size: 14px; } 
        
        /* Modal & Denetim Kartı */ 
        .modal { display:none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.8); z-index:1000; justify-content:center; align-items:center; backdrop-filter: blur(4px); } 
        .denetim-karti { background:#fff; width:850px; border-radius:20px; display:flex; overflow:hidden; animation: zoomIn 0.2s; } 
        .denetim-sol { flex:1; background:#f8fafc; padding:25px; border-right:1px solid #eee; } 
        .denetim-sag { flex:1; padding:25px; display:flex; flex-direction:column; } 
        
        .aksiyon-btn { padding:12px; border:none; border-radius:8px; color:#fff; font-weight:700; cursor:pointer; margin-bottom:10px; transition:0.2s; } 
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; }
        @keyframes zoomIn { from {transform:scale(0.95); opacity:0;} to {transform:scale(1); opacity:1;} } 
    </style> 
</head> 
<body> 

<?php include "sol_menu.php"; ?> 

<div class="content"> 
    <h1 style="color:#1a202c; margin-bottom:20px;">🛡️ Şikayet Denetim Merkezi</h1> 

    <?php if(isset($_GET['islem']) && $_GET['islem'] == 'basarili'): ?>
        <div class="alert-success">✅ Şikayet kararı başarıyla uygulandı ve kapatıldı.</div>
    <?php endif; ?>

    <div class="card"> 
        <table> 
            <thead> 
                <tr> 
                    <th>Şikayet Eden</th> 
                    <th>İlan / Hedef</th> 
                    <th>Şikayet Mesajı</th> 
                    <th>Durum</th> 
                    <th style="text-align: right;">İşlem</th> 
                </tr> 
            </thead> 
            <tbody> 
                <?php foreach($sikayetler as $s): 
                    $baslik_ham = $s['baslik'] ?? 'Silinmiş İlan';
                    $kisa_baslik = mb_strlen($baslik_ham) > 30 ? mb_substr($baslik_ham, 0, 30) . '...' : $baslik_ham;
                ?> 
                <tr> 
                    <td><b><?php echo htmlspecialchars($s['sikayetci'] ?? 'Anonim / Silinmiş Üye'); ?></b></td> 
                    <td><?php echo htmlspecialchars($kisa_baslik); ?></td> 
                    <td><small style="color:#4a5568;"><?php echo htmlspecialchars(mb_substr($s['mesaj'] ?? '', 0, 45)); ?>...</small></td> 
                    <td>
                        <span style="font-weight:800; font-size:11px; padding:4px 8px; border-radius:6px; background:<?php echo $s['durum'] == 'beklemede' ? '#fef3c7' : '#e2e8f0'; ?>; color:<?php echo $s['durum'] == 'beklemede' ? '#92400e' : '#475569'; ?>">
                            <?php echo strtoupper($s['durum'] ?? 'KAPATILDI'); ?>
                        </span>
                    </td> 
                    <td style="text-align: right;"> 
                        <button onclick='denetle(<?php echo json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' style="background:var(--z-blue); color:#fff; border:none; padding:8px 14px; border-radius:8px; font-weight:700; cursor:pointer;">🔍 İncele & Karar Ver</button> 
                    </td> 
                </tr> 
                <?php endforeach; ?> 
                <?php if(empty($sikayetler)): ?>
                    <tr><td colspan="5" style="text-align:center; color:#a0aec0; padding:30px;">Kayıtlı şikayet bulunmuyor.</td></tr>
                <?php endif; ?>
            </tbody> 
        </table> 
    </div> 
</div> 

<div id="denetimModal" class="modal"> 
    <div class="denetim-karti"> 
        <div class="denetim-sol"> 
            <h3 id="d-baslik" style="margin-top:0; color:#1a202c;">İlan Başlığı</h3> 
            <img id="d-resim" src="" style="width:100%; height:180px; object-fit:cover; border-radius:10px; margin-bottom:15px; border:1px solid #eee; background:#fff;"> 
            <div id="d-aciklama" style="font-size:13px; color:#4a5568; line-height:1.5; max-height:120px; overflow-y:auto; background:#fff; padding:10px; border-radius:8px; border:1px solid #eee;"></div> 
            <hr style="border:0; border-top:1px solid #e2e8f0; margin:15px 0;"> 
            <div style="background:#fff5f5; padding:12px; border-radius:8px; border:1px solid #feb2b2;"> 
                <label style="font-weight:800; color:#c53030; font-size:11px; display:block; margin-bottom:5px;">GELEN ŞİKAYET:</label> 
                <p id="d-sikayet-mesaj" style="font-size:13px; margin:0; color:#2d3748;"></p> 
            </div> 
        </div> 
         
        <form class="denetim-sag" method="POST"> 
            <input type="hidden" name="sikayet_id" id="form-sid"> 
            <input type="hidden" name="ilan_id" id="form-iid"> 
            <input type="hidden" name="ilan_sahibi_id" id="form-usid"> 
            <input type="hidden" name="sikayetci_id" id="form-scid"> 
             
            <h3 style="margin-top:0; color:#1a202c;">Hakem Kararı</h3> 
            <p style="font-size:12px; color:#718096; margin-top:-5px;">İlan ve şikayeti inceleyip nihai kararınızı uygulayabilirsiniz.</p> 
             
            <label style="font-size:12px; font-weight:800; color:#4a5568; margin-bottom:5px;">Aksiyon Seçin:</label> 
            <select name="aksiyon" class="aksiyon-btn" style="background:#fff; color:#333; border:1px solid #cbd5e0;" required> 
                <option value="devam">✅ Şikayeti Reddet (Yayına Devam)</option> 
                <option value="durdur">⏳ Yayını Durdur (Onay Beklet)</option> 
                <option value="kaldir">🚫 İlanı Yayından Kaldır (Pasife Al)</option> 
            </select> 

            <label style="font-size:12px; font-weight:800; color:#4a5568; margin-top:10px; margin-bottom:5px;">Sonuç / Admin Notu:</label> 
            <textarea name="admin_notu" style="flex:1; min-height:80px; padding:10px; border-radius:8px; border:1px solid #cbd5e0; margin-bottom:15px; font-family:sans-serif;" placeholder="Şikayet incelenmiş olup gerekçesi..." required></textarea> 
             
            <div style="display:flex; gap:10px;"> 
                <button type="button" onclick="closeModal()" style="flex:1; background:#edf2f7; color:#4a5568;" class="aksiyon-btn">Vazgeç</button> 
                <button type="submit" name="moderasyon_aksiyon" style="flex:2; background:var(--z-green);" class="aksiyon-btn">KARARI UYGULA</button> 
            </div> 
        </form> 
    </div> 
</div> 

<script> 
function denetle(data) { 
    document.getElementById('form-sid').value = data.id || 0; 
    document.getElementById('form-iid').value = data.ilan_id || 0; 
    document.getElementById('form-usid').value = data.ilan_sahibi_id || 0; 
    document.getElementById('form-scid').value = data.sikayetci_id || 0; 
     
    document.getElementById('d-baslik').innerText = data.baslik || 'Silinmiş / Bulunamayan İlan'; 
    document.getElementById('d-resim').src = data.kapak ? ("../uploads/ilanlar/" + data.kapak) : "../dosyalar/resim/yok.png"; 
    document.getElementById('d-aciklama').innerText = data.aciklama || 'Açıklama bulunmuyor.'; 
    document.getElementById('d-sikayet-mesaj').innerText = data.mesaj || 'Şikayet metni boş.'; 
     
    document.getElementById('denetimModal').style.display = 'flex'; 
} 

function closeModal() { document.getElementById('denetimModal').style.display = 'none'; } 
</script> 

</body> 
</html>
