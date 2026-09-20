<?php 
require_once "../sistem/ayar.php"; 

// Eğer zaten giriş yapmışsa doğrudan panele gönder
if(isset($_SESSION['admin_id']) && $_SESSION['yetki'] == 'admin'){
    header("Location: index.php"); exit;
}

if(isset($_POST['admin_giris'])){
    // Şifreyi g() fonksiyonundan geçirmeyin, password_verify ham haliyle doğrular
    $kimlik = g($_POST['kimlik']); 
    $sifre  = $_POST['sifre'];

    // Hem e-posta hem de kullanıcı adı ile giriş imkanı
    // yetki = 'admin' ve durum = 'aktif' olanı arıyoruz
    $sorgu = $db->prepare("SELECT * FROM uyeler WHERE (email = ? OR kullanici_adi = ?) AND yetki = 'admin' AND durum = 'aktif'");
    $sorgu->execute([$kimlik, $kimlik]);
    $admin = $sorgu->fetch();

    if($admin && password_verify($sifre, $admin['sifre'])){
        // KRİTİK SESSION ANAHTARLARI
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_ad'] = $admin['ad_soyad'];
        $_SESSION['yetki']    = $admin['yetki']; // index.php bu anahtarı arıyor!
        
        // Genel üye sessionlarını da set edelim (isteğe bağlı)
        $_SESSION['uye_id']   = $admin['id'];
        $_SESSION['uye_ad']   = $admin['ad_soyad'];

        header("Location: index.php");
        exit;
    } else {
        $hata = "Yetkisiz erişim veya hatalı bilgiler!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yönetim Girişi | ZiraatBox</title>
    <style>
        body { background: #1a202c; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: 'Segoe UI', sans-serif; margin:0; }
        .giris-kutu { background: #fff; padding: 40px; border-radius: 12px; width: 380px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
        .logo { text-align: center; color: #48bb78; font-size: 28px; font-weight: bold; margin-bottom: 30px; }
        input { width: 100%; padding: 14px; margin: 10px 0; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; transition: 0.3s; }
        input:focus { border-color: #48bb78; outline: none; box-shadow: 0 0 0 3px rgba(72, 187, 120, 0.1); }
        button { width: 100%; padding: 14px; background: #2d3748; color: #fff; border: 0; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 16px; margin-top: 15px; transition: 0.3s; }
        button:hover { background: #1a202c; }
        .hata-mesaj { background: #fff5f5; color: #c53030; padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center; border: 1px solid #feb2b2; }
    </style>
</head>
<body>
    <div class="giris-kutu">
        <div class="logo">ZiraatBox Admin</div>
        
        <?php if(isset($hata)): ?>
            <div class="hata-mesaj"><?php echo $hata; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <label style="font-size: 14px; color: #4a5568; font-weight: 600;">E-posta veya Kullanıcı Adı</label>
            <input type="text" name="kimlik" placeholder="admin veya admin@ziraatbox.com" required>
            
            <label style="font-size: 14px; color: #4a5568; font-weight: 600;">Şifre</label>
            <input type="password" name="sifre" placeholder="••••••••" required>
            
            <button type="submit" name="admin_giris">Yönetim Paneline Gir</button>
        </form>
        
        <p style="text-align: center; margin-top: 25px;">
            <a href="../index.php" style="color: #718096; text-decoration: none; font-size: 14px;">← Siteye Geri Dön</a>
        </p>
    </div>
</body>
</html>