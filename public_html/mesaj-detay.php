<?php 
require_once "parcalar/ust.php"; 
if (!isset($_SESSION['uye_id'])) { header("Location: giris.php"); exit; }

$konusma_id = intval($_GET['id']);
$uye_id = $_SESSION['uye_id'];

// --- 1. ADIM: OKUNDU BİLGİSİNİ GÜNCELLE ---
// Sayfa açıldığı an, karşı taraftan gelen ve bu konuşmaya ait mesajları "okundu" yapıyoruz.
$db->prepare("UPDATE mesajlar SET okundu = 1 WHERE konusma_id = ? AND gonderen_id != ? AND okundu = 0")
   ->execute([$konusma_id, $uye_id]);

// 2. ADIM: Konuşma ve Karşı Üye Bilgilerini Çek
$kontrol = $db->prepare("SELECT mk.*, i.baslik, u.ad_soyad as karsi_ad 
                         FROM mesaj_konusmalari mk 
                         JOIN ilanlar i ON mk.ilan_id = i.id 
                         JOIN uyeler u ON (u.id = mk.baslatan_uye_id OR u.id = mk.karsi_uye_id)
                         WHERE mk.id = ? AND (mk.baslatan_uye_id = ? OR mk.karsi_uye_id = ?) AND u.id != ?");
$kontrol->execute([$konusma_id, $uye_id, $uye_id, $uye_id]);
$konusma = $kontrol->fetch();

if (!$konusma) { 
    echo "<div class='konteynir' style='padding:50px; text-align:center;'>Yetkisiz erişim veya konuşma bulunamadı.</div>"; 
    require_once "parcalar/alt.php";
    exit; 
}

// 3. ADIM: Mesajları Getir
$mesaj_sorgu = $db->prepare("SELECT * FROM mesajlar WHERE konusma_id = ? ORDER BY id ASC");
$mesaj_sorgu->execute([$konusma_id]);
$mesajlar = $mesaj_sorgu->fetchAll();
?>



<div style="max-width: 800px; margin: 30px auto; background: #fff; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid #f1f5f9;">
    
    <div style="background: #27ae60; color: #fff; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #f39c12;">
        <div>
            <div style="font-weight: 800; font-size: 18px; letter-spacing: 0.5px;"><?php echo htmlspecialchars($konusma['karsi_ad']); ?></div>
            <div style="font-size: 13px; opacity: 0.9; font-weight: 500;">İlan: <?php echo htmlspecialchars($konusma['baslik']); ?></div>
        </div>
        <a href="mesajlarim.php" style="color: #fff; text-decoration: none; font-weight: bold; font-size: 14px; background: rgba(0,0,0,0.1); padding: 8px 15px; border-radius: 8px;">&larr; Geri Dön</a>
    </div>

    <div style="height: 500px; overflow-y: auto; padding: 25px; background: #f8fafc; display: flex; flex-direction: column;" id="mesaj_alani">
        <?php if($mesajlar): foreach($mesajlar as $m): 
            $benim_mi = ($m['gonderen_id'] == $uye_id);
        ?>
            <div style="margin-bottom: 15px; display: flex; flex-direction: column; align-items: <?php echo $benim_mi ? 'flex-end' : 'flex-start'; ?>;">
                <div style="max-width: 70%; padding: 12px 18px; border-radius: 15px; font-size: 15px; line-height: 1.5; position: relative; 
                            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
                            background: <?php echo $benim_mi ? '#27ae60; color:#fff; border-bottom-right-radius: 2px;' : '#fff; color:#333; border-bottom-left-radius: 2px; border:1px solid #e2e8f0;'; ?>">
                    <?php echo htmlspecialchars($m['mesaj']); ?>
                    <div style="font-size: 10px; margin-top: 6px; text-align: right; opacity: 0.7; font-weight: bold;">
                        <?php echo date('H:i', strtotime($m['created_at'])); ?> 
                        <?php if($benim_mi): ?>
                            <span style="margin-left:5px;"><?php echo ($m['okundu'] == 1) ? '✓✓' : '✓'; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; else: ?>
            <div style="text-align:center; color:#a0aec0; margin-top:100px;">Henüz mesaj yok. İlk mesajı siz yazın!</div>
        <?php endif; ?>
    </div>

    <form action="islem/mesaj-kaydet.php" method="POST" style="padding: 20px; background: #fff; border-top: 1px solid #f1f5f9; display: flex; gap: 15px; align-items: center;">
        <input type="hidden" name="konusma_id" value="<?php echo $konusma_id; ?>">
        <input type="text" name="mesaj" placeholder="Mesajınızı buraya yazın..." required autocomplete="off" 
               style="flex: 1; padding: 14px 20px; border: 2px solid #edf2f7; border-radius: 30px; outline: none; font-size: 15px; transition: 0.3s;"
               onfocus="this.style.borderColor='#27ae60';">
        <button type="submit" style="background: #27ae60; color: #fff; border: none; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 20px; transition: 0.3s; box-shadow: 0 4px 10px rgba(39,174,96,0.3);" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
            ➤
        </button>
    </form>
</div>

<script>
    // Sayfa açıldığında en aşağı kaydır
    var d = document.getElementById("mesaj_alani");
    d.scrollTop = d.scrollHeight;
</script>

<?php require_once "parcalar/alt.php"; ?>