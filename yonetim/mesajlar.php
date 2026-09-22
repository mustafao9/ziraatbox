<?php  
require_once "../sistem/ayar.php";  

if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){ 
    header("Location: giris.php");  
    exit; 
} 

// 2. ADIM: MESAJ SİLME İŞLEMİ (Konuşma ve Mesajlar birlikte temizlenir)
if(isset($_GET['sil'])){ 
    $id = intval($_GET['sil']); 
    $db->prepare("DELETE FROM mesaj_konusmalari WHERE id = ?")->execute([$id]); 
    $db->prepare("DELETE FROM mesajlar WHERE konusma_id = ?")->execute([$id]); 
    header("Location: mesajlar.php?durum=silindi"); 
    exit; 
} 

// Konuşmaları, kullanıcı adlarını ve ilan başlıklarını tek seferde çekiyoruz 
$konusmalar = $db->query("SELECT mk.*,  
                          i.baslik as ilan_baslik, 
                          u1.ad_soyad as baslatan_ad, u1.email as baslatan_mail, 
                          u2.ad_soyad as karsi_ad, u2.email as karsi_mail 
                          FROM mesaj_konusmalari mk 
                          LEFT JOIN ilanlar i ON mk.ilan_id = i.id 
                          LEFT JOIN uyeler u1 ON mk.baslatan_uye_id = u1.id 
                          LEFT JOIN uyeler u2 ON mk.karsi_uye_id = u2.id 
                          ORDER BY mk.son_mesaj_zamani DESC")->fetchAll(PDO::FETCH_ASSOC); 
?> 

<!DOCTYPE html> 
<html lang="tr"> 
<head> 
    <meta charset="UTF-8"> 
    <title>Mesaj Yönetimi | ZiraatBox</title> 
    <style> 
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; display: flex; } 
        .content { flex: 1; padding: 30px; box-sizing: border-box; } 
        
        .data-card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); } 
        table { width: 100%; border-collapse: collapse; margin-top: 15px; } 
        th { text-align: left; padding: 12px; background: #f8fafc; color: #64748b; font-size: 13px; border-bottom: 2px solid #edf2f7; } 
        td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; vertical-align: middle; } 
        
        .user-info { display: flex; flex-direction: column; } 
        .user-info small { color: #718096; font-size: 12px; } 
        .ilan-link { color: #3182ce; text-decoration: none; font-weight: 600; } 
        .ilan-link:hover { text-decoration: underline; }
        .btn-sil { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; } 
        .alert-success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 700; }
    </style> 
</head> 
<body> 

<?php include "sol_menu.php"; ?> 

<div class="content"> 
    <h1 style="color: #1a202c; margin-bottom: 25px;">💬 Mesajlaşma Yönetimi</h1> 

    <?php if(isset($_GET['durum']) && $_GET['durum'] == 'silindi'): ?>
        <div class="alert-success">✅ Konuşma geçmişi ve tüm mesajlar başarıyla silindi.</div>
    <?php endif; ?>

    <div class="data-card"> 
        <table> 
            <thead> 
                <tr> 
                    <th>İlgili İlan</th> 
                    <th>Gönderen (Başlatan)</th> 
                    <th>Alıcı</th> 
                    <th>Son Mesaj Tarihi</th> 
                    <th style="text-align: right;">İşlem</th> 
                </tr> 
            </thead> 
            <tbody> 
                <?php foreach($konusmalar as $k): ?> 
                <tr> 
                    <td> 
                        <?php if(!empty($k['ilan_baslik'])): ?>
                            <a href="ilanlar.php?id=<?php echo (int)$k['ilan_id']; ?>" class="ilan-link" target="_blank"> 
                                📦 <?php echo htmlspecialchars($k['ilan_baslik']); ?> 
                            </a> 
                        <?php else: ?>
                            <i style="color:#a0aec0">Silinmiş İlan (#<?php echo (int)$k['ilan_id']; ?>)</i>
                        <?php endif; ?>
                    </td> 
                    <td> 
                        <div class="user-info"> 
                            <strong><?php echo htmlspecialchars($k['baslatan_ad'] ?? 'Anonim / Silinmiş Üye'); ?></strong> 
                            <small><?php echo htmlspecialchars($k['baslatan_mail'] ?? ''); ?></small> 
                        </div> 
                    </td> 
                    <td> 
                        <div class="user-info"> 
                            <strong><?php echo htmlspecialchars($k['karsi_ad'] ?? 'Anonim / Silinmiş Üye'); ?></strong> 
                            <small><?php echo htmlspecialchars($k['karsi_mail'] ?? ''); ?></small> 
                        </div> 
                    </td> 
                    <td style="color: #718096;"> 
                        <?php echo !empty($k['son_mesaj_zamani']) ? date('d.m.Y H:i', strtotime($k['son_mesaj_zamani'])) : '-'; ?> 
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
