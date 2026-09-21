<?php 
require_once "../sistem/ayar.php"; 

if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    header("Location: giris.php"); exit;
}

// MEVCUT AYARLARI ÇEK
$ayar = $db->query("SELECT * FROM ayarlar WHERE id = 1")->fetch();

$onay_bekleyen = $db->query("SELECT COUNT(*) FROM ilanlar WHERE durum = 'beklemede'")->fetchColumn();
$yeni_mesaj    = $db->query("SELECT COUNT(*) FROM mesajlar WHERE okundu = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sistem Ayarları | ZiraatBox Yönetim</title>
    <style>
        :root { --admin-dark: #1a202c; --admin-green: #27ae60; --admin-blue: #3182ce; --admin-orange: #ed8936; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f4f7f6; display: flex; }
        .sidebar { width: 260px; background: var(--admin-dark); color: #fff; min-height: 100vh; padding: 20px; box-sizing: border-box; position: sticky; top: 0; }
        .content { flex: 1; padding: 40px; box-sizing: border-box; }
        .view-site-btn { display: flex; align-items: center; justify-content: center; background: var(--admin-green); color: #fff; text-decoration: none; padding: 12px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; }
        .sidebar a { display: block; color: #cbd5e0; padding: 12px; text-decoration: none; border-radius: 8px; margin-bottom: 5px; }
        .sidebar a.active { background: #2d3748; color: #fff; }
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card { background: #fff; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid #edf2f7; }
        .card-header { background: #f8fafc; padding: 15px 25px; border-bottom: 1px solid #edf2f7; font-weight: 800; color: var(--admin-dark); display: flex; align-items: center; justify-content: space-between; }
        .card-body { padding: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 700; color: #4a5568; font-size: 12px; }
        .form-input { width: 100%; padding: 10px; border: 2px solid #edf2f7; border-radius: 8px; font-size: 14px; box-sizing: border-box; outline: none; transition: 0.3s; }
        .form-input:focus { border-color: var(--admin-green); }
        .btn-save { background: var(--admin-dark); color: #fff; padding: 15px; border: 0; border-radius: 10px; cursor: pointer; font-weight: 800; width: 100%; font-size: 16px; margin-top: 20px; box-shadow: 0 4px 0 #000; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: #c6f6d5; color: #22543d; }
        .alert-hata { background: #fed7d7; color: #822727; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2 style="color: #48bb78;">ZiraatBox</h2>
    <a href="../index.php" target="_blank" class="view-site-btn">🌐 Siteyi Görüntüle</a>
    <nav>
        <a href="index.php">🏠 Panel Özeti</a>
        <a href="ilanlar.php">📦 İlan Yönetimi (<b><?php echo $onay_bekleyen; ?></b>)</a>
        <a href="kategoriler.php">📂 Kategori Yönetimi</a>
        <a href="ozellikler.php">🛠️ Özellik Havuzu</a>
        <a href="uyeler.php">👥 Üye Yönetimi</a>
        <a href="mesajlar.php">💬 Mesajlar (<b><?php echo $yeni_mesaj; ?></b>)</a>
        <a href="ayarlar.php" class="active">⚙️ Genel Ayarlar</a>
        <hr style="border: 0; border-top: 1px solid #2d3748; margin: 20px 0;">
        <a href="cikis.php" style="color: #fc8181;">🚪 Güvenli Çıkış</a>
    </nav>
</div>

<div class="content">
    <h1 style="margin: 0; font-weight: 800;">⚙️ Sistem Kontrol Merkezi</h1>
    <p style="color: #718096; margin-bottom: 30px;">Platformun tüm teknik ve kurumsal DNA'sı burada saklanır.</p>

    <?php 
    if(isset($_GET['durum'])){
        if($_GET['durum'] == 'ok') echo '<div class="alert alert-success">✅ Ayarlar başarıyla güncellendi.</div>';
        if($_GET['durum'] == 'hata') echo '<div class="alert alert-hata">❌ Ayarlar güncellenirken bir hata oluştu.</div>';
    }
    ?>

    <form action="islem/ayar-kaydet.php" method="POST" enctype="multipart/form-data">
        <div class="settings-grid">
            
            <div class="card">
                <div class="card-header">🚀 Genel & SEO Ayarları</div>
                <div class="card-body">
                    <div class="form-group"><label>Site Logosu (PNG önerilir)</label><input type="file" name="logo" class="form-input"></div>
                    <div class="form-group"><label>Site Başlığı</label><input type="text" name="site_baslik" class="form-input" value="<?php echo $ayar['site_baslik']; ?>"></div>
                    <div class="form-group"><label>Motto / Slogan</label><input type="text" name="site_slogan" class="form-input" value="<?php echo $ayar['site_slogan']; ?>"></div>
                    <div class="form-group"><label>Meta Açıklama (Description)</label><textarea name="site_desc" class="form-input" rows="3"><?php echo $ayar['site_desc']; ?></textarea></div>
                </div>
            </div>

            <div class="card" style="border-top: 4px solid var(--admin-orange);">
                <div class="card-header">🛠️ Teknik & İlan Kuralları</div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Bakım Modu</label>
                        <select name="bakim_modu" class="form-input">
                            <option value="0" <?php echo $ayar['bakim_modu']==0?'selected':''; ?>>✅ Site Aktif</option>
                            <option value="1" <?php echo $ayar['bakim_modu']==1?'selected':''; ?>>❌ Bakım Modunda</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>İlan Onay Mekanizması</label>
                        <select name="ilan_onay_gerekiyor" class="form-input">
                            <option value="1" <?php echo $ayar['ilan_onay_gerekiyor']==1?'selected':''; ?>>👮 Admin Onayı Gereksin</option>
                            <option value="0" <?php echo $ayar['ilan_onay_gerekiyor']==0?'selected':''; ?>>⚡ Hemen Yayınlansın</option>
                        </select>
                    </div>
                    <div class="form-group"><label>İlan Yayın Süresi (Gün)</label><input type="number" name="ilan_suresi_gun" class="form-input" value="<?php echo $ayar['ilan_suresi_gun']; ?>"></div>
                </div>
            </div>

            <div class="card" style="grid-column: span 2; border-top: 4px solid var(--admin-blue);">
                <div class="card-header">📝 Kurumsal Metinler (Hakkımızda & KVKK)</div>
                <div class="card-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group"><label>Hakkımızda Sayfası</label><textarea name="hakkimizda" class="form-input" rows="10"><?php echo $ayar['hakkimizda']; ?></textarea></div>
                    <div class="form-group"><label>KVKK & Gizlilik Politikası</label><textarea name="kvkk" class="form-input" rows="10"><?php echo $ayar['kvkk']; ?></textarea></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">📱 İletişim & Sosyal Medya</div>
                <div class="card-body">
                    <div class="form-group"><label>WhatsApp / Tel</label><input type="text" name="site_tel" class="form-input" value="<?php echo $ayar['site_tel']; ?>"></div>
                    <div class="form-group"><label>Kurumsal E-Posta</label><input type="email" name="site_eposta" class="form-input" value="<?php echo $ayar['site_eposta']; ?>"></div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                        <div class="form-group"><label>Instagram URL</label><input type="text" name="instagram" class="form-input" value="<?php echo $ayar['instagram']; ?>"></div>
                        <div class="form-group"><label>Facebook URL</label><input type="text" name="facebook" class="form-input" value="<?php echo $ayar['facebook']; ?>"></div>
                    </div>
                    <div class="form-group"><label>Merkez Adres</label><textarea name="site_adres" class="form-input" rows="2"><?php echo $ayar['site_adres']; ?></textarea></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">📧 SMTP E-Posta</div>
                <div class="card-body">
                    <div class="form-group"><label>SMTP Host</label><input type="text" name="smtp_host" class="form-input" value="<?php echo $ayar['smtp_host']; ?>"></div>
                    <div class="form-group"><label>SMTP Kullanıcı</label><input type="text" name="smtp_user" class="form-input" value="<?php echo $ayar['smtp_user']; ?>"></div>
                    <div class="form-group"><label>SMTP Şifre</label><input type="password" name="smtp_pass" class="form-input" placeholder="Değiştirmeyecekseniz boş bırakın"></div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                        <div class="form-group"><label>SMTP Port</label><input type="text" name="smtp_port" class="form-input" value="<?php echo $ayar['smtp_port']; ?>"></div>
                        <div class="form-group">
                            <label>Güvenlik</label>
                            <select name="smtp_secure" class="form-input">
                                <option value="TLS" <?php echo $ayar['smtp_secure']=='TLS'?'selected':''; ?>>TLS</option>
                                <option value="SSL" <?php echo $ayar['smtp_secure']=='SSL'?'selected':''; ?>>SSL</option>
                                <option value="NONE" <?php echo $ayar['smtp_secure']=='NONE'?'selected':''; ?>>Yok</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <button type="submit" name="ayarlari_kaydet" class="btn-save">💾 TÜM SİSTEM AYARLARINI KAYDET</button>
    </form>
</div>
</body>
</html>