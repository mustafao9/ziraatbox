<?php 
/**
 * ZiraatBox - Yeni Şifre Belirleme Sayfası
 */
require_once "parcalar/ust.php"; 

$hata = '';
$basarili = '';
$token = isset($_GET['token']) ? g($_GET['token']) : (isset($_POST['token']) ? g($_POST['token']) : '');
$dogrulama = null;

// 1. ADIM: Token Doğruluğunu Kontrol Et
if (!empty($token)) {
    // Token veritabanında var mı? Süresi dolmamış mı? Durumu beklemede mi?
    $sorgu = $db->prepare("SELECT * FROM sifre_sifirlama WHERE token = ? AND durum = 'beklemede' AND son_kullanma > NOW() LIMIT 1");
    $sorgu->execute([$token]);
    $dogrulama = $sorgu->fetch();

    if (!$dogrulama) {
        $hata = "Bağlantı geçersiz, daha önce kullanılmış veya süresi (1 saat) dolmuş.";
    }
} else {
    header("Location: index.php"); exit;
}

// 2. ADIM: Form Gönderildiğinde Şifreyi Güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dogrulama) {
    $sifre = $_POST['sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];

    if (strlen($sifre) < 6) {
        $hata = "Güvenliğiniz için şifreniz en az 6 karakter olmalıdır.";
    } elseif ($sifre !== $sifre_tekrar) {
        $hata = "Girdiğiniz şifreler birbiriyle eşleşmiyor.";
    } else {
        try {
            $db->beginTransaction();

            // Şifreyi Modern Hash (password_hash) ile güncelliyoruz
            $yeni_hash = password_hash($sifre, PASSWORD_DEFAULT);
            $guncelle = $db->prepare("UPDATE uyeler SET sifre = ? WHERE id = ?");
            $guncelle->execute([$yeni_hash, $dogrulama['uye_id']]);

            // Kullanılan token'ı "kullanildi" olarak işaretleyelim ki tekrar girilemesin
            $token_iptal = $db->prepare("UPDATE sifre_sifirlama SET durum = 'kullanildi' WHERE id = ?");
            $token_iptal->execute([$dogrulama['id']]);

            $db->commit();
            $basarili = "Şifreniz başarıyla güncellendi! 3 saniye içinde giriş sayfasına yönlendiriliyorsunuz.";
            header("Refresh:3; url=giris.php");

        } catch (Exception $e) {
            $db->rollBack();
            $hata = "Sistem hatası oluştu, lütfen teknik ekibe bildirin.";
        }
    }
}
?>

<style>
    .reset-wrapper { max-width: 450px; margin: 80px auto; padding: 0 20px; }
    .reset-card { background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.07); border-top: 5px solid #27ae60; }
    .reset-header h1 { color: #1b4332; font-size: 24px; font-weight: 900; margin: 0; text-align: center; }
    
    .form-label { display: block; font-size: 12px; font-weight: 800; color: #64748b; margin-bottom: 8px; text-transform: uppercase; }
    .form-input { width: 100%; padding: 14px 18px; border: 2px solid #edf2f7; border-radius: 12px; font-size: 15px; outline: none; transition: 0.3s; box-sizing: border-box; }
    .form-input:focus { border-color: #27ae60; background: #f0fdf4; }
    
    .btn-update { width: 100%; background: #27ae60; color: #fff; border: none; padding: 16px; border-radius: 12px; font-weight: 800; font-size: 16px; cursor: pointer; transition: 0.3s; margin-top: 15px; box-shadow: 0 4px 0 #1e8449; }
    .btn-update:hover { background: #2ecc71; transform: translateY(-2px); box-shadow: 0 6px 0 #1e8449; }
    
    .alert { padding: 15px; border-radius: 12px; margin-bottom: 25px; font-size: 14px; font-weight: 700; text-align: center; }
    .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }
</style>

<div class="reset-wrapper">
    <div class="reset-card">
        <div class="reset-header" style="margin-bottom: 30px;">
            <h1>🔒 Yeni Şifre Belirle</h1>
            <p style="text-align: center; color: #718096; font-size: 14px; margin-top: 10px;">Lütfen unutmayacağınız, güçlü bir şifre giriniz.</p>
        </div>

        <?php if($basarili): ?>
            <div class="alert alert-success">✅ <?php echo $basarili; ?></div>
        <?php else: ?>
            
            <?php if($hata): ?>
                <div class="alert alert-error">⚠️ <?php echo $hata; ?></div>
            <?php endif; ?>

            <?php if($dogrulama): ?>
                <form action="" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div style="margin-bottom: 20px;">
                        <label class="form-label">YENİ ŞİFRE</label>
                        <input type="password" name="sifre" class="form-input" placeholder="••••••••" required>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label class="form-label">ŞİFRE TEKRAR</label>
                        <input type="password" name="sifre_tekrar" class="form-input" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn-update">ŞİFREYİ GÜNCELLE VE GİRİŞ YAP</button>
                </form>
            <?php else: ?>
                <div style="text-align: center;">
                    <a href="sifremi-unuttum.php" style="color: #27ae60; font-weight: 800; text-decoration: none;">Yeni Bir Talep Oluştur →</a>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php require_once "parcalar/alt.php"; ?>