<?php 
require_once "parcalar/ust.php"; 

// Eğer kullanıcı zaten giriş yapmışsa ana sayfaya gönder
if (isset($_SESSION['uye_id'])) {
    header("Location: index.php");
    exit;
}
?>

<style>
    :root { --z-green: #27ae60; --z-dark: #1b4332; --z-bg: #f8fafc; }
    body { background: var(--z-bg); }
    .reg-container { max-width: 500px; margin: 40px auto; padding: 0 15px; }
    .reg-card { background: #fff; border-radius: 20px; padding: 40px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    .reg-title { font-size: 24px; font-weight: 900; color: var(--z-dark); text-align: center; margin-bottom: 10px; }
    .reg-sub { text-align: center; color: #718096; font-size: 14px; margin-bottom: 30px; }
    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-weight: 700; color: #4a5568; margin-bottom: 6px; font-size: 13px; }
    .form-control { width: 100%; padding: 12px 15px; border: 2px solid #edf2f7; border-radius: 12px; outline: none; transition: 0.3s; font-size: 14px; box-sizing: border-box; }
    .form-control:focus { border-color: var(--z-green); background: #fff; }
    .btn-reg { width: 100%; padding: 15px; background: var(--z-green); color: #fff; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; font-size: 16px; transition: 0.3s; margin-top: 10px; }
    .btn-reg:hover { background: #219150; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(39,174,96,0.3); }
    
    /* Mesaj Kutuları */
    .alert-box { padding: 15px; border-radius: 10px; font-size: 13px; font-weight: 700; margin-bottom: 20px; border: 1px solid; }
    .alert-error { background: #fff5f5; color: #c53030; border-color: #fed7d7; }
    .alert-success { background: #f0fff4; color: #276749; border-color: #c6f6d5; }
</style>

<div class="reg-container">
    <div class="reg-card">
        <h1 class="reg-title">ZiraatBox'a Katılın</h1>
        <p class="reg-sub">İkinci el tarım aletleri dünyasına ilk adımı atın.</p>

        <?php if(isset($_SESSION['mesaj'])): ?>
            <div class="alert-box <?php echo (strpos($_SESSION['mesaj'], 'Başarıyla') !== false || strpos($_SESSION['mesaj'], 'geldiniz') !== false) ? 'alert-success' : 'alert-error'; ?>">
                ⚠️ <?php echo $_SESSION['mesaj']; unset($_SESSION['mesaj']); ?>
            </div>
        <?php endif; ?>

        <form action="islem/kayit-kontrol.php" method="POST">
            <div class="form-group">
                <label>Ad Soyad</label>
                <input type="text" name="ad_soyad" class="form-control" placeholder="Örn: Mustafa Satılmış" required>
            </div>

            <div class="form-group">
                <label>E-Posta Adresi</label>
                <input type="email" name="eposta" class="form-control" placeholder="isminiz@mail.com" required>
            </div>

            <div class="form-group">
                <label>Telefon Numarası</label>
                <input type="text" name="telefon" class="form-control" placeholder="05XX XXX XX XX">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Şifre</label>
                    <input type="password" name="sifre" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label>Şifre (Tekrar)</label>
                    <input type="password" name="sifre_tekrar" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <div class="form-group" style="display: flex; justify-content: center; margin-top: 5px;">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
            </div>

            <button type="submit" name="kayit_ol" class="btn-reg">ÜYE OL</button>
        </form>

        <div style="text-align: center; margin-top: 25px; font-size: 14px; color: #718096;">
            Zaten hesabınız var mı? <a href="giris.php" style="color: var(--z-green); font-weight: 800; text-decoration: none;">Giriş Yap</a>
        </div>
    </div>
</div>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php require_once "parcalar/alt.php"; ?>