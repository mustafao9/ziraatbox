<?php 
require_once "../sistem/ayar.php"; 

// 1. ADIM: YETKİ VE OTURUM KONTROLÜ
if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    header("Location: giris.php"); 
    exit;
}

// 2. ADIM: MESAJ SİLME İŞLEMİ
if(isset($_GET['sil'])){
    $id = intval($_GET['sil']);
    $db->prepare("DELETE FROM mesaj_konusmalari WHERE id = ?")->execute([$id]);
    header("Location: mesajlar.php?durum=silindi"); exit;
}

// Sidebar için onay bekleyen ilan sayısını çekelim
$onay_bekleyen = $db->query("SELECT COUNT(*) FROM ilanlar WHERE durum = 'beklemede'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Mesaj Yönetimi | ZiraatBox</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; display: flex; }
        .sidebar { width: 260px; background: #1a202c; color: #fff; min-height: 100vh; padding: 20px; box-sizing: border-box; position: sticky; top: 0; }
        .content { flex: 1; padding: 30px; box-sizing: border-box; }
        
        /* Webmaster Düğmesi */
        .view-site-btn { display: flex; align-items: center; justify-content: center; background: #27ae60; color: #fff; text-decoration: none; padding: 10px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; font-size: 14px; transition: 0.3s; border: 1px solid #219150; }
        .view-site-btn:hover { background: #2ecc71; transform: scale(1.02); }

        /* Veri Kartı */
        .data-card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 12px; background: #f8fafc; color: #64748b; font-size: 13px; border-bottom: 2px solid #edf2f7; }
        td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; vertical-align: middle; }
        
        .sidebar a { display: block; color: #cbd5e0; padding: 12px; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #2d3748; color: #fff; }
        
        .user-info { display: flex; flex-direction: column; }
        .user-info small { color: #718096; font-size: 12px; }
        .ilan-link { color: #4a90e2; text-decoration: none; font-weight: 600; }
        .btn-sil { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; text-decoration: none; padding: 5px 10px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>ZiraatBox</h2>
    <a href="../index.php" target="_blank" class="view-site-btn">🌐 Siteyi Görüntüle</a>
    <nav>
        <a href="index.php">🏠 Panel Özeti</a>
        <a href="ilanlar.php">📦 İlan Yönetimi (<b><?php echo $onay_bekleyen; ?></b>)</a>
        <a href="kategoriler.php">📂 Kategori Yönetimi</a>
        <a href="uyeler.php">👥 Üye Yönetimi</a>
        <a href="mesajlar.php" class="active">💬 Mesajlar</a>
        <a href="ayarlar.php">⚙️ Genel Ayarlar</a>
        <hr style="border: 0; border-top: 1px solid #2d3748; margin: 20px 0;">
        <a href="cikis.php" style="color: #fc8181;">🚪 Güvenli Çıkış</a>
    </nav>
</div>

<div class="content">
    <h1 style="color: #1a202c; margin-bottom: 25px;">Mesajlaşma Yönetimi</h1>

    <div class="data-card">
        <table>
            <thead>
                <tr>
                    <th>İlgili İlan</th>
                    <th>Gönderen</th>
                    <th>Alıcı</th>
                    <th>Son Mesaj Tarihi</th>
                    <th style="text-align: right;">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Konuşmaları, kullanıcı adlarını ve ilan başlıklarını tek seferde çekiyoruz
                $konusmalar = $db->query("SELECT mk.*, 
                                          i.baslik as ilan_baslik,
                                          u1.ad_soyad as baslatan_ad, u1.email as baslatan_mail,
                                          u2.ad_soyad as karsi_ad, u2.email as karsi_mail
                                          FROM mesaj_konusmalari mk
                                          LEFT JOIN ilanlar i ON mk.ilan_id = i.id
                                          LEFT JOIN uyeler u1 ON mk.baslatan_uye_id = u1.id
                                          LEFT JOIN uyeler u2 ON mk.karsi_uye_id = u2.id
                                          ORDER BY mk.son_mesaj_zamani DESC")->fetchAll();

                foreach($konusmalar as $k):
                ?>
                <tr>
                    <td>
                        <a href="../ilan-detay.php?id=<?php echo $k['ilan_id']; ?>" target="_blank" class="ilan-link">
                            <?php echo !empty($k['ilan_baslik']) ? htmlspecialchars($k['ilan_baslik']) : '<i style="color:#a0aec0">Silinmiş İlan</i>'; ?>
                        </a>
                    </td>
                    <td>
                        <div class="user-info">
                            <strong><?php echo htmlspecialchars($k['baslatan_ad']); ?></strong>
                            <small><?php echo $k['baslatan_mail']; ?></small>
                        </div>
                    </td>
                    <td>
                        <div class="user-info">
                            <strong><?php echo htmlspecialchars($k['karsi_ad']); ?></strong>
                            <small><?php echo $k['karsi_mail']; ?></small>
                        </div>
                    </td>
                    <td style="color: #718096;">
                        <?php echo date('d.m.Y H:i', strtotime($k['son_mesaj_zamani'])); ?>
                    </td>
                    <td style="text-align: right;">
                        <a href="mesajlar.php?sil=<?php echo $k['id']; ?>" class="btn-sil" onclick="return confirm('Tüm konuşma geçmişi silinecek. Emin misiniz?')">🗑️ Konuşmayı Sil</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($konusmalar)): ?>
                    <tr><td colspan="5" style="text-align: center; color: #a0aec0; padding: 40px;">Henüz aktif bir mesajlaşma bulunmuyor.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>