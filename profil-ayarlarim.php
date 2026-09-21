<?php
require_once "parcalar/ust.php"; 

if (!isset($_SESSION['uye_id'])) {
    header("Location: " . URL . "/giris.php");
    exit;
}

$uye_id = $_SESSION['uye_id'];
$sorgu = $db->prepare("SELECT * FROM uyeler WHERE id = ?");
$sorgu->execute([$uye_id]);
$uye = $sorgu->fetch();

if (!$uye) { die("Hata: Oturum verileri geçersiz."); }

$is_google = (!empty($uye['google_id'])) ? true : false;
?>

<style>
    :root { --z-green: #27ae60; --z-dark: #1b4332; --z-border: #e2e8f0; --z-bg: #f8fafc; }
    .settings-container { display: grid; grid-template-columns: 280px 1fr; gap: 30px; max-width: 1200px; margin: 40px auto; padding: 0 15px; }
    .card-settings { background: #fff; border-radius: 16px; border: 1px solid var(--z-border); padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .form-label { display: block; font-weight: 700; color: #4a5568; margin-bottom: 8px; font-size: 14px; }
    .form-control { width: 100%; padding: 12px; border: 2px solid #edf2f7; border-radius: 10px; outline: none; transition: 0.3s; font-size: 14px; }
    .form-control:focus { border-color: var(--z-green); box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1); }
    .btn-save { background: var(--z-green); color: #fff; padding: 12px 35px; border: none; border-radius: 10px; font-weight: 800; cursor: pointer; transition: 0.3s; width: 100%; }
    .btn-save:hover { background: #219150; transform: translateY(-2px); }
    .section-title { font-size: 18px; font-weight: 800; color: var(--z-dark); border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
    .strength-meter { height: 6px; width: 100%; background: #e2e8f0; margin-top: 8px; border-radius: 10px; overflow: hidden; }
    .strength-fill { height: 100%; width: 0%; transition: 0.4s all ease; }
    .msg-box { font-size: 12px; margin-top: 5px; font-weight: 600; display: block; }

    /* Şifre Göster Butonu Stili */
    .password-wrapper { position: relative; display: flex; align-items: center; }
    .toggle-password { position: absolute; right: 12px; cursor: pointer; color: #94a3b8; font-size: 12px; font-weight: 700; user-select: none; background: #f1f5f9; padding: 2px 8px; border-radius: 6px; }
    .toggle-password:hover { color: var(--z-green); }
    
    @media (max-width: 992px) { .settings-container { grid-template-columns: 1fr; } }
</style>

<div class="settings-container">
    <aside>
        <div class="card-settings" style="text-align: center; position: sticky; top: 20px;">
            <div style="position: relative; display: inline-block;">
                <?php 
                $foto = $uye['profil_foto'];
                $profil_resmi = (filter_var($foto, FILTER_VALIDATE_URL)) ? $foto : (!empty($foto) ? URL.'/yuklemeler/profil/'.$foto : URL.'/dosyalar/resim/avatar.png');
                ?>
                <img src="<?php echo $profil_resmi; ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            </div>
            <h3 style="margin: 15px 0 5px; color: var(--z-dark);"><?php echo htmlspecialchars($uye['ad_soyad']); ?></h3>
            <p style="color: #94a3b8; font-size: 13px; margin-bottom: 20px;"><?php echo $uye['email']; ?></p>
            <hr style="border:0; border-top:1px solid #f1f5f9; margin: 20px 0;">
            <a href="hesabim.php" style="text-decoration:none; color:var(--z-green); font-weight:700;">⬅ Panele Dön</a>
        </div>
    </aside>

    <main>
        <form action="<?php echo URL; ?>/islem/profil-guncelle.php" method="POST" enctype="multipart/form-data">
            <div class="card-settings" style="margin-bottom: 25px;">
                <h3 class="section-title">👤 Kişisel Bilgiler</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div><label class="form-label">Ad Soyad</label><input type="text" name="ad_soyad" value="<?php echo htmlspecialchars($uye['ad_soyad']); ?>" class="form-control" required></div>
                    <div><label class="form-label">E-posta (Değiştirilemez)</label><input type="email" value="<?php echo $uye['email']; ?>" class="form-control" disabled style="background:#f8fafc;"></div>
                    <div><label class="form-label">Telefon Numarası</label><input type="text" name="telefon" value="<?php echo $uye['telefon']; ?>" class="form-control"></div>
                    <div><label class="form-label">Profil Fotoğrafı Güncelle</label><input type="file" name="profil_foto" class="form-control" accept="image/*"></div>
                </div>
            </div>

            <div class="card-settings">
                <h3 class="section-title">🔒 Güvenlik & Şifre</h3>
                <?php if($is_google): ?>
                    <div style="background: #fffaf0; border: 1px solid #feebc8; color: #9c4221; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; line-height: 1.6;">
                        <strong>💡 Google Oturumu Bilgisi:</strong> Google ile oturum açıldığından şu an mevcut şifreniz istenmez. Ancak bu işlemden sonra oluşturmuş olduğunuz şifre sisteme kaydedilecek ve bir sonraki değişiklik işlemlerinizde güvenliğiniz için bu şifrenin girilmesi istenecektir.
                    </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label class="form-label">Yeni Şifre</label>
                        <div class="password-wrapper">
                            <input type="password" name="yeni_sifre" id="pass1" class="form-control" placeholder="••••••••">
                            <span class="toggle-password" onclick="togglePassword('pass1')">GÖSTER</span>
                        </div>
                        <div class="strength-meter"><div id="strength-bar" class="strength-fill"></div></div>
                        <span id="strength-text" class="msg-box"></span>
                    </div>
                    <div>
                        <label class="form-label">Yeni Şifre (Tekrar)</label>
                        <div class="password-wrapper">
                            <input type="password" name="yeni_sifre_tekrar" id="pass2" class="form-control" placeholder="••••••••">
                            <span class="toggle-password" onclick="togglePassword('pass2')">GÖSTER</span>
                        </div>
                        <span id="match-status" style="font-size:13px; font-weight:700; margin-top:8px; display:block;"></span>
                    </div>
                </div>

                <?php if(!$is_google): ?>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed #e2e8f0;">
                    <label class="form-label" style="color: #e53e3e;">Onay İçin Mevcut Şifreniz</label>
                    <div class="password-wrapper">
                        <input type="password" name="mevcut_sifre" id="mevcut_sifre" class="form-control" required>
                        <span class="toggle-password" onclick="togglePassword('mevcut_sifre')">GÖSTER</span>
                    </div>
                </div>
                <?php endif; ?>

                <div style="margin-top: 25px;"><button type="submit" name="profil_guncelle" class="btn-save">DEĞİŞİKLİKLERİ KAYDET</button></div>
            </div>
        </form>
    </main>
</div>

<script>
function togglePassword(id) {
    const el = document.getElementById(id);
    const btn = el.nextElementSibling;
    if (el.type === "password") {
        el.type = "text";
        btn.innerText = "GİZLE";
    } else {
        el.type = "password";
        btn.innerText = "GÖSTER";
    }
}

const p1 = document.getElementById('pass1');
const p2 = document.getElementById('pass2');
const sBar = document.getElementById('strength-bar');
const sText = document.getElementById('strength-text');
const mStatus = document.getElementById('match-status');

p1.addEventListener('input', () => {
    let val = p1.value;
    let score = 0;
    if (val.length >= 6) score++;
    if (val.match(/[A-Z]/)) score++;
    if (val.match(/[0-9]/)) score++;
    if (val.match(/[^A-Za-z0-9]/)) score++;

    if (val.length === 0) {
        sBar.style.width = '0%'; sText.innerText = '';
    } else if (score <= 1) {
        sBar.style.width = '25%'; sBar.style.background = '#e53e3e';
        sText.style.color = '#e53e3e'; sText.innerText = 'Basit Şifre (Sizin şifrenizi kaydedeceğiz, ancak bu şifrenin ele geçirilmesi çok basittir)';
    } else if (score === 2 || score === 3) {
        sBar.style.width = '60%'; sBar.style.background = '#d69e2e';
        sText.style.color = '#d69e2e'; sText.innerText = 'İyi Şifre';
    } else {
        sBar.style.width = '100%'; sBar.style.background = '#38a169';
        sText.style.color = '#38a169'; sText.innerText = 'Çok İyi Şifre';
    }
    checkMatch();
});

p2.addEventListener('input', checkMatch);

function checkMatch() {
    if (p2.value.length > 0) {
        if (p1.value === p2.value) {
            mStatus.innerText = '✔️ Şifreler uyuşuyor, başarılı.';
            mStatus.style.color = '#38a169';
        } else {
            mStatus.innerText = '❌ Şifreler uyuşmuyor!';
            mStatus.style.color = '#e53e3e';
        }
    } else { mStatus.innerText = ''; }
}
</script>
<?php require_once "parcalar/alt.php"; ?>