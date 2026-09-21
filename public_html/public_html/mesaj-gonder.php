<?php 
require_once "parcalar/ust.php"; 

// 1. GÜVENLİK: Giriş yapmayan mesaj atamaz
if (!isset($_SESSION['uye_id'])) {
    $_SESSION['hata'] = "Mesaj göndermek için giriş yapmalısınız.";
    header("Location: giris.php");
    exit;
}

// 2. VERİLERİ YAKALAMA (Hata veren kısımlar düzeltildi)
$ilan_id = isset($_GET['ilan_id']) ? intval($_GET['ilan_id']) : 0;
$alici_id = isset($_GET['alici_id']) ? intval($_GET['alici_id']) : 0;

// İlan bilgilerini çekelim (Hangi ilan için mesaj atılıyor?)
$ilanSorgu = $db->prepare("SELECT baslik FROM ilanlar WHERE id = ?");
$ilanSorgu->execute([$ilan_id]);
$ilan = $ilanSorgu->fetch();

// Alıcı (Satıcı) bilgilerini çekelim
$aliciSorgu = $db->prepare("SELECT ad_soyad FROM uyeler WHERE id = ?");
$aliciSorgu->execute([$alici_id]);
$alici = $aliciSorgu->fetch();

if (!$ilan || !$alici) {
    echo "<div class='konteynir' style='padding:50px; text-align:center;'>Geçersiz ilan veya kullanıcı bilgisi.</div>";
    require_once "parcalar/alt.php";
    exit;
}
?>

<div class="konteynir" style="max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
    
    <h2 style="color: #2d3748; margin-bottom: 5px;">🗨️ Satıcıya Soru Sor</h2>
    <p style="color: #718096; font-size: 14px; margin-bottom: 25px;">
        <b>İlan:</b> <?php echo htmlspecialchars($ilan['baslik']); ?><br>
        <b>Alıcı:</b> <?php echo htmlspecialchars($alici['ad_soyad']); ?>
    </p>

    <form action="islem/mesaj-kaydet.php" method="POST">
        <input type="hidden" name="ilan_id" value="<?php echo $ilan_id; ?>">
        <input type="hidden" name="alici_id" value="<?php echo $alici_id; ?>">
        
        <div style="margin-bottom: 20px;">
            <label style="font-weight: 600; display: block; margin-bottom: 10px; color: #4a5568;">Mesajınız</label>
            <textarea name="mesaj" rows="6" required 
                      placeholder="Ürün hakkında sormak istediklerinizi buraya yazın..." 
                      style="width: 100%; padding: 15px; border: 2px solid #edf2f7; border-radius: 8px; box-sizing: border-box; font-family: inherit; resize: none; outline: none; transition: 0.3s;"
                      onfocus="this.style.borderColor='#27ae60';"></textarea>
        </div>

        <button type="submit" style="width: 100%; padding: 15px; background: #27ae60; color: #fff; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s;" onmouseover="this.style.background='#219150'">
            🚀 Mesajı Gönder
        </button>
        
        <a href="ilan-detay.php?id=<?php echo $ilan_id; ?>" style="display: block; text-align: center; margin-top: 15px; color: #a0aec0; text-decoration: none; font-size: 14px;">Vazgeç ve İlana Dön</a>
    </form>
</div>

<?php require_once "parcalar/alt.php"; ?>