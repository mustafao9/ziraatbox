<?php 
/**
 * ZiraatBox - Gelişmiş Mesajlaşma Paneli
 * Dosya Yolu: /home/ziraatbo/public_html/mesajlarim.php
 */
require_once "parcalar/ust.php"; 

if (!isset($_SESSION['uye_id'])) { header("Location: giris.php"); exit; }
$uye_id = $_SESSION['uye_id'];

// 1. KONUŞMALARI ÇEKELİM (Okunmamış mesaj sayısıyla birlikte)
$sorgu = $db->prepare("
    SELECT 
        mk.*, 
        i.baslik as ilan_basli, 
        u.ad_soyad as karsi_taraf,
        u.profil_foto as karsi_foto,
        (SELECT COUNT(m.id) FROM mesajlar m WHERE m.konusma_id = mk.id AND m.okundu = 0 AND m.gonderen_id != ?) as okunmamis
    FROM mesaj_konusmalari mk 
    LEFT JOIN ilanlar i ON mk.ilan_id = i.id 
    JOIN uyeler u ON (u.id = mk.baslatan_uye_id OR u.id = mk.karsi_uye_id)
    WHERE (mk.baslatan_uye_id = ? OR mk.karsi_uye_id = ?) AND u.id != ?
    ORDER BY mk.son_mesaj_zamani DESC
");
$sorgu->execute([$uye_id, $uye_id, $uye_id, $uye_id]);
$konusmalar = $sorgu->fetchAll();
?>

<style>
    :root { --z-green: #27ae60; --z-dark: #1b4332; --z-border: #e2e8f0; --z-bg: #f1f5f9; }
    body { background: var(--z-bg); }

    .msg-container { max-width: 900px; margin: 40px auto; padding: 0 15px; }
    .msg-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .msg-header h1 { font-weight: 950; color: var(--z-dark); margin: 0; font-size: 28px; }

    .msg-card { background: #fff; border-radius: 20px; border: 1px solid var(--z-border); box-shadow: 0 10px 25px rgba(0,0,0,0.02); overflow: hidden; }

    .konusma-link { 
        display: flex; 
        align-items: center; 
        padding: 20px; 
        border-bottom: 1px solid #f1f5f9; 
        text-decoration: none; 
        color: inherit; 
        transition: 0.3s; 
        position: relative;
    }
    .konusma-link:last-child { border-bottom: none; }
    .konusma-link:hover { background: #f8fafc; transform: translateX(5px); }

    .avatar-wrapper { position: relative; margin-right: 15px; }
    .msg-avatar { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    
    .status-dot { width: 12px; height: 12px; background: #cbd5e0; border: 2px solid #fff; border-radius: 50%; position: absolute; bottom: 3px; right: 3px; }
    .status-online { background: var(--z-green); }

    .msg-info { flex: 1; min-width: 0; }
    .karsi-isim { font-weight: 800; color: var(--z-dark); font-size: 16px; margin-bottom: 3px; display: flex; align-items: center; gap: 8px; }
    .ilan-etiket { font-size: 13px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    .msg-meta { text-align: right; margin-left: 15px; }
    .msg-time { font-size: 11px; color: #94a3b8; font-weight: 700; }
    
    .badge-count { 
        background: var(--z-green); 
        color: #fff; 
        font-size: 10px; 
        font-weight: 900; 
        padding: 4px 8px; 
        border-radius: 10px; 
        display: inline-block; 
        margin-top: 8px;
        box-shadow: 0 4px 10px rgba(39,174,96,0.2);
    }

    /* Boş Durum */
    .empty-msg { padding: 80px 20px; text-align: center; }
    .empty-msg i { font-size: 50px; color: #cbd5e0; margin-bottom: 20px; }

    /* Mobil */
    @media (max-width: 600px) {
        .msg-avatar { width: 50px; height: 50px; }
        .karsi-isim { font-size: 14px; }
        .ilan-etiket { font-size: 12px; }
        .msg-header h1 { font-size: 22px; }
    }
</style>

<div class="msg-container">
    <div class="msg-header">
        <h1>💬 Mesajlarım</h1>
        <div style="font-size: 12px; background: #fff; padding: 5px 12px; border-radius: 20px; border: 1px solid var(--z-border); font-weight: 800; color: var(--z-green);">
            <?php echo count($konusmalar); ?> Konuşma
        </div>
    </div>

    <div class="msg-card">
        <?php if($konusmalar): foreach($konusmalar as $k): 
            $foto = !empty($k['karsi_foto']) ? (filter_var($k['karsi_foto'], FILTER_VALIDATE_URL) ? $k['karsi_foto'] : URL.'/yuklemeler/profil/'.$k['karsi_foto']) : URL.'/dosyalar/resim/avatar.png';
        ?>
            <a href="mesaj-detay.php?id=<?php echo $k['id']; ?>" class="konusma-link">
                <div class="avatar-wrapper">
                    <img src="<?php echo $foto; ?>" class="msg-avatar">
                    <?php if($k['okunmamis'] > 0): ?>
                        <div class="status-dot status-online"></div>
                    <?php endif; ?>
                </div>

                <div class="msg-info">
                    <div class="karsi-isim">
                        <?php echo htmlspecialchars($k['karsi_taraf']); ?>
                        <?php if($k['okunmamis'] > 0): ?>
                            <small style="color:var(--z-green); font-size: 10px;">(Yeni Mesaj)</small>
                        <?php endif; ?>
                    </div>
                    <div class="ilan-etiket">
                        <i class="fa-solid fa-tractor" style="font-size: 11px; color: #cbd5e0;"></i> 
                        <?php echo htmlspecialchars($k['ilan_basli']); ?>
                    </div>
                </div>

                <div class="msg-meta">
                    <div class="msg-time"><?php echo date('H:i', strtotime($k['son_mesaj_zamani'])); ?></div>
                    <div style="font-size: 10px; color: #cbd5e0; margin-top: 2px;"><?php echo date('d.m.Y', strtotime($k['son_mesaj_zamani'])); ?></div>
                    <?php if($k['okunmamis'] > 0): ?>
                        <div class="badge-count"><?php echo $k['okunmamis']; ?></div>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; else: ?>
            <div class="empty-msg">
                <i class="fa-regular fa-comments"></i>
                <h3 style="color:var(--z-dark); margin-bottom: 10px; font-weight: 800;">Mesaj kutun boş</h3>
                <p style="color:#94a3b8; font-size: 14px;">İlan sahipleriyle iletişime geçerek<br>sohbet etmeye başlayabilirsin.</p>
                <a href="ilanlar.php" style="display:inline-block; margin-top: 20px; color: var(--z-green); font-weight: 800; text-decoration: none;">İlanları Keşfet ➔</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once "parcalar/alt.php"; ?>