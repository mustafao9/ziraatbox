<?php 
/**
 * ZiraatBox - Şifremi Unuttum Talebi
 * Tasarım: Modern & Kompakt
 */
require_once "parcalar/ust.php"; 

// Eğer kullanıcı zaten giriş yapmışsa bu sayfada işi yok, ana sayfaya gönderelim.
if (isset($_SESSION['uye_id'])) { 
    header("Location: index.php"); 
    exit; 
}
?>

<style>
    /* Şifremi Unuttum Kartı */
    .forgot-wrapper { max-width: 450px; margin: 80px auto; padding: 0 20px; }
    .forgot-card { 
        background: #fff; 
        padding: 40px; 
        border-radius: 20px; 
        box-shadow: 0 15px 35px rgba(0,0,0,0.07); 
        border-top: 5px solid #f39c12; /* ZiraatBox Turuncusu */
    }
    .forgot-header h1 { color: #1b4332; font-size: 26px; font-weight: 900; margin: 0; }
    .forgot-header p { color: #718096; font-size: 14px; margin-top: 10px; line-height: 1.5; }
    
    .form-label { display: block; font-size: 12px; font-weight: 800; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-input { 
        width: 100%; 
        padding: 14px 18px; 
        border: 2px solid #edf2f7; 
        border-radius: 12px; 
        font-size: 15px; 
        outline: none; 
        transition: 0.3s; 
        box-sizing: border-box; 
    }
    .form-input:focus { border-color: #f39c12; background: #fffaf0; }
    
    .btn-send { 
        width: 100%; 
        background: #f39c12; 
        color: #fff; 
        border: none; 
        padding: 16px; 
        border-radius: 12px; 
        font-weight: 800; 
        font-size: 16px; 
        cursor: pointer; 
        transition: 0.3s; 
        margin-top: 10px;
        box-shadow: 0 4px 0 #d35400;
    }
    .btn-send:hover { background: #e67e22; transform: translateY(-2px); box-shadow: 0 6px 0 #d35400; }
    .btn-send:active { transform: translateY(2px); box-shadow: none; }

    /* Bildirim Kutuları */
    .alert { padding: 15px; border-radius: 12px; margin-bottom: 25px; font-size: 14px; font-weight: 700; text-align: center; line-height: 1.4; }
    .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }
</style>

<div class="forgot-wrapper">
    <div class="forgot-card">
        <div class="forgot-header" style="text-align: center; margin-bottom: 35px;">
            <h1>Şifremi Unuttum</h1>
            <p>E-posta adresinizi yazın, size güvenli bir şifre sıfırlama bağlantısı gönderelim.</p>
        </div>

        <?php 
        // URL'den gelen durumlara göre kullanıcıya mesaj verelim
        if(isset($_GET['durum'])):
            if($_GET['durum'] == 'ok'): ?>
                <div class="alert alert-success">
                    ✅ Talep başarılı! Lütfen mail kutunuzu (ve gereksiz/spam klasörünü) kontrol edin.
                </div>
            <?php elseif($_GET['durum'] == 'yok'): ?>
                <div class="alert alert-error">
                    ❌ Bu e-posta adresi sistemimizde kayıtlı görünmüyor.
                </div>
            <?php elseif($_GET['durum'] == 'hata'): ?>
                <div class="alert alert-error">
                    ⚠️ Mail gönderilirken bir sorun oluştu. Lütfen teknik ekiple iletişime geçin.
                </div>
            <?php endif; 
        endif; ?>

        <form action="islem/sifre-talep.php" method="POST">
            <div style="margin-bottom: 25px;">
                <label class="form-label">Kayıtlı E-Posta Adresiniz</label>
                <input type="email" name="eposta" class="form-input" placeholder="ornek@pazar.com" required>
            </div>

            <button type="submit" name="sifre_sifirla_talep" class="btn-send">BAĞLANTI GÖNDER</button>
        </form>

        <div style="text-align: center; margin-top: 30px;">
            <a href="giris.php" style="color: #27ae60; text-decoration: none; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                <span>←</span> Giriş Sayfasına Geri Dön
            </a>
        </div>
    </div>
</div>

<?php require_once "parcalar/alt.php"; ?>