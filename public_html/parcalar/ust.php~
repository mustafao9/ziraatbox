<?php 
/**
 * ZiraatBox - Kurumsal Header (Tam Ekran & Mobil Uyum Onarımlı)
 * Dosya Yolu: /parcalar/ust.php
 */
require_once "sistem/ayar.php"; 

// 1. BİLDİRİM SORGULARI
$okunmamis_mesaj = 0;
$yeni_sikayet_sayisi = 0;

if(isset($_SESSION['uye_id'])){
    $uye_id = $_SESSION['uye_id'];
    
    // Mesaj Bildirimleri
    $sorgu_mesaj = $db->prepare("
        SELECT COUNT(m.id) 
        FROM mesajlar m
        JOIN mesaj_konusmalari mk ON m.konusma_id = mk.id
        WHERE m.okundu = 0 
        AND m.gonderen_id != ? 
        AND (mk.baslatan_uye_id = ? OR mk.karsi_uye_id = ?)
    ");
    $sorgu_mesaj->execute([$uye_id, $uye_id, $uye_id]);
    $okunmamis_mesaj = $sorgu_mesaj->fetchColumn();

    // 🚀 ADMİN İÇİN ŞİKAYET BİLDİRİMİ
    if(isset($_SESSION['yetki']) && $_SESSION['yetki'] == 'admin'){
        $yeni_sikayet_sayisi = $db->query("SELECT COUNT(*) FROM sikayetler WHERE durum = 'beklemede'")->fetchColumn();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo defined('SITE_ADI') ? SITE_ADI : 'ZiraatBox'; ?> - Tarım Pazaryeri</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root { 
            --ziraat-koyu-yesil: #1b4332; 
            --ziraat-turuncu: #f39c12;    
            --beyaz: #ffffff;
            --hata-kirmizi: #e74c3c;
        }

        body { font-family: 'Nunito', sans-serif; margin: 0; background: #f4f7f6; color: #333; }

        .ust-header { 
            background: var(--ziraat-koyu-yesil); 
            padding: 12px 0; 
            border-bottom: 5px solid var(--ziraat-turuncu); 
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* Header'a Özel Geniş Kapsayıcı (Sıkışıklığı Engeller) */
        .header-konteynir { 
            max-width: 1450px; 
            margin: 0 auto; 
            padding: 0 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-sizing: border-box;
        }

        /* Sayfaların Genel Standart Kapsayıcısı */
        .konteynir { 
            max-width: 1450px; 
            margin: 0 auto; 
            padding: 0 15px; 
            box-sizing: border-box;
        }

        .logo-yazi { 
            text-decoration: none; 
            font-size: 28px; 
            font-weight: 900; 
            color: var(--beyaz); 
            letter-spacing: -1px;
        }
        .logo-yazi span { color: var(--ziraat-turuncu); }

        nav { display: flex; align-items: center; gap: 18px; }

        .nav-link { 
            color: var(--beyaz); 
            text-decoration: none; 
            font-weight: 700; 
            font-size: 14.5px; 
            display: flex; 
            align-items: center; 
            gap: 6px;
            transition: 0.2s;
        }
        .nav-link:hover { color: var(--ziraat-turuncu); }

        .btn-ilan-ver { 
            background: var(--ziraat-turuncu); 
            color: var(--beyaz) !important; 
            padding: 9px 20px; 
            border-radius: 9px; 
            text-decoration: none; 
            font-weight: 900; 
            font-size: 14px;
            box-shadow: 0 4px 0 #d68910;
            transition: 0.2s;
            white-space: nowrap;
        }
        .btn-ilan-ver:active { transform: translateY(2px); box-shadow: 0 2px 0 #d68910; }

        /* BİLDİRİM BALONLARI */
        .msg-badge {
            background: var(--hata-kirmizi);
            color: white;
            padding: 2px 7px;
            border-radius: 50%;
            font-size: 10px;
            margin-left: 4px;
            font-weight: 900;
            display: inline-block;
            line-height: 1;
        }

        @media screen and (max-width: 850px) {
            .header-konteynir { flex-direction: column; gap: 12px; padding: 10px 15px; }
            nav { width: 100%; justify-content: center; gap: 12px; flex-wrap: wrap; }
            .nav-link { font-size: 13px; }
            .btn-ilan-ver { width: 100%; text-align: center; box-sizing: border-box; }
        }
    </style>
</head>
<body>

<header class="ust-header">
    <div class="header-konteynir">
        <div class="logo">
            <a href="<?php echo URL; ?>" class="logo-yazi">Ziraat<span>Box</span></a>
        </div>
        
        <nav>
            <a href="ilanlar.php" class="nav-link"><i class="fa-solid fa-tractor"></i> İlanlar</a>
            
            <?php if(isset($_SESSION['uye_id'])): ?>
                
                <?php if(isset($_SESSION['yetki']) && $_SESSION['yetki'] == 'admin'): ?>
                    <a href="yonetim/index.php" class="nav-link" style="color: var(--ziraat-turuncu);">
                        <i class="fa-solid fa-shield-halved"></i> Panel
                        <?php if($yeni_sikayet_sayisi > 0): ?>
                            <span class="msg-badge" style="background:#fff; color:var(--hata-kirmizi);"><?php echo $yeni_sikayet_sayisi; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <a href="hesabim.php" class="nav-link">
                    <i class="fa-solid fa-user-circle"></i> Hesabım
                    <?php if($okunmamis_mesaj > 0): ?>
                        <span class="msg-badge"><?php echo $okunmamis_mesaj; ?></span>
                    <?php endif; ?>
                </a>
                <a href="cikis.php" style="color: #ff8a80; font-size: 12px; font-weight: 900; text-decoration: none; margin-left: 5px;">ÇIKIŞ</a>
            <?php else: ?>
                <a href="giris.php" class="nav-link">Giriş Yap</a>
                <a href="kayit.php" class="nav-link" style="border: 1px solid #fff; padding: 5px 12px; border-radius: 8px;">Kayıt Ol</a>
            <?php endif; ?>

            <a href="ilan-ver.php" class="btn-ilan-ver">+ Ücretsiz İlan Ver</a>
        </nav>
    </div>
</header>

<main class="konteynir" style="padding-top: 20px; padding-bottom: 20px;">