<?php 
require_once "parcalar/ust.php"; 

// Google ayarlarını tam yol ile çağırıyoruz
if (file_exists(__DIR__ . "/islem/google-ayarlar.php")) { 
    include_once __DIR__ . "/islem/google-ayarlar.php"; 
}

// Oturum varsa ana sayfaya URL sabitiyle gönder
if (isset($_SESSION['uye_id'])) { 
    header("Location: " . URL . "/index.php"); 
    exit; 
}
?>

<div style="width: 100%; min-height: 80vh; display: flex; justify-content: center; align-items: center; background: #f4f7f6; padding: 40px 0; margin: 0 auto;">
    
    <div style="width: 100%; max-width: 400px; background: #fff; padding: 40px; border-radius: 25px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); border-top: 6px solid #f39c12; margin: 0 auto;">
        
        <div style="text-align: center; margin-bottom: 25px;">
            <h2 style="color: #1b4332; font-size: 30px; margin: 0; font-weight: 900;">Ziraat<span style="color:#f39c12;">Box</span></h2>
            <p style="color: #718096; font-size: 14px; margin-top: 10px;">Hesabınıza güvenle giriş yapın.</p>
        </div>

        <?php if(isset($_SESSION['hata'])): ?>
            <div style="background: #fff5f5; color: #c53030; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; font-size: 13px; border: 1px solid #feb2b2; font-weight: 700;">
                <?php echo $_SESSION['hata']; unset($_SESSION['hata']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($google_client)): ?>
            <a href="<?php echo htmlspecialchars($google_client->createAuthUrl()); ?>" style="display: flex; align-items: center; justify-content: center; gap: 10px; background: #fff; border: 2px solid #edf2f7; padding: 12px; border-radius: 12px; text-decoration: none; color: #4a5568; font-weight: 700; margin-bottom: 20px; transition: all 0.3s;">
                <svg width="18" height="18" viewBox="0 0 18 18">
                    <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285f4"/>
                    <path d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34a853"/>
                    <path d="M3.964 10.712c-.18-.54-.282-1.117-.282-1.712s.102-1.173.282-1.712V4.956H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.044l3.007-2.332z" fill="#fbbc05"/>
                    <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.443 2.048.957 4.956L3.964 7.28c.708-2.127 2.692-3.7 5.036-3.7z" fill="#ea4335"/>
                </svg>
                Google ile Giriş
            </a>
            <div style="text-align: center; margin-bottom: 20px; color: #cbd5e0; font-size: 12px;">VEYA</div>
        <?php endif; ?>

        <form action="<?php echo URL; ?>/islem/giris-kontrol.php" method="POST">
            <div style="margin-bottom: 15px;">
                <label style="font-weight: 700; color: #4a5568; font-size: 13px; display: block; margin-bottom: 5px;">E-Posta veya Kullanıcı Adı</label>
                <input type="text" name="kullanici_adi_veya_eposta" style="width: 100%; padding: 14px; border: 2px solid #edf2f7; border-radius: 12px; outline: none; box-sizing: border-box;" placeholder="isminiz@mail.com veya kullanıcı adınız..." required>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="font-weight: 700; color: #4a5568; font-size: 13px; display: block; margin-bottom: 5px;">Şifre</label>
                <input type="password" name="sifre" style="width: 100%; padding: 14px; border: 2px solid #edf2f7; border-radius: 12px; outline: none; box-sizing: border-box;" placeholder="••••••••" required>
            </div>

            <div style="margin-bottom: 20px; display: flex; justify-content: center;">
                <div class="g-recaptcha" data-sitekey="<?php echo defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : '6Lf5NYQsAAAAALvt4iKM_jQrkHLVW5KweD0IH7Kv'; ?>"></div>
            </div>

            <button type="submit" name="giris" style="width: 100%; padding: 16px; background: #27ae60; color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 800; cursor: pointer;">Giriş Yap</button>
        </form>

        <div style="text-align: center; margin-top: 25px; font-size: 13px;">
            <a href="<?php echo URL; ?>/kayit.php" style="color: #27ae60; text-decoration: none; font-weight: 800;">Ücretsiz Kayıt Ol</a>
        </div>
    </div>
</div>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php require_once "parcalar/alt.php"; ?>