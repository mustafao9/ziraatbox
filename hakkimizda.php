<?php 
require_once "parcalar/ust.php"; 

/**
 * ZiraatBox - Hakkımızda Sayfası (Plus Premium)
 * Veritabanındaki 'ayarlar' tablosundan 'hakkimizda' metnini çeker.
 */

// Ayarları çekelim
$ayar = $db->query("SELECT * FROM ayarlar WHERE id = 1")->fetch();

if (!function_exists('altKategoriIDleriniGetir')) {
    function altKategoriIDleriniGetir($db, $ust_id) {
        $ids = [$ust_id];
        $sorgu = $db->prepare("SELECT id FROM kategoriler WHERE ust_id = ?");
        $sorgu->execute([$ust_id]);
        $altlar = $sorgu->fetchAll(PDO::FETCH_COLUMN);
        foreach ($altlar as $id) { $ids = array_merge($ids, altKategoriIDleriniGetir($db, $id)); }
        return array_unique($ids);
    }
}
?>

<style>
    :root {
        --z-yesil: #1b4332;
        --z-sari: #f39c12;
        --z-border: #e4e4e4;
        --z-bg: #fdfdfd;
    }

    /* ANA LAYOUT NİZAMI (Index ve Kategori ile Aynı) */
    .plus-full-wrapper {
        display: grid;
        grid-template-columns: 280px 1fr;
        min-height: 100vh;
        width: 100%;
    }

    /* SOL SIDEBAR */
    .plus-sidebar { 
        background: #fff; 
        border-right: 1px solid var(--z-border); 
        padding: 25px 15px; 
        height: 100%;
    }
    .plus-sidebar h3 { font-size: 14px; font-weight: 800; color: #000; margin-bottom: 15px; border-bottom: 2px solid var(--z-sari); padding-bottom: 8px; text-transform: uppercase; }
    .plus-side-list { list-style: none; }
    .plus-side-list li { margin-bottom: 10px; }
    .plus-side-list a { display: flex; justify-content: space-between; font-size: 13px; color: #333; font-weight: 600; padding: 5px 0; }
    
    /* SAĞ İÇERİK ALANI */
    .plus-content-area {
        padding: 30px;
        background: var(--z-bg);
    }

    .corporate-card {
        background: #fff;
        border: 1px solid var(--z-border);
        border-radius: 12px;
        padding: 40px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        line-height: 1.8;
        color: #444;
    }

    .corporate-header {
        border-bottom: 3px solid var(--z-sari);
        padding-bottom: 15px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .corporate-header h1 {
        font-size: 28px;
        font-weight: 900;
        color: var(--z-yesil);
        margin: 0;
    }

    .content-text {
        font-size: 16px;
        white-space: pre-line; /* Veritabanındaki satır boşluklarını korur */
    }

    /* EKSTRA BİLGİ KARTLARI */
    .info-stats {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
        margin-top: 40px;
    }

    .stat-item {
        background: #f9fbf9;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        border: 1px solid #eef2ee;
    }

    .stat-item h4 { color: var(--z-yesil); font-size: 20px; margin-bottom: 5px; }
    .stat-item p { font-size: 12px; color: #777; font-weight: 700; text-transform: uppercase; }

    @media (max-width: 992px) {
        .plus-full-wrapper { grid-template-columns: 1fr; }
        .plus-sidebar { display: none; }
        .plus-content-area { padding: 20px; }
        .info-stats { grid-template-columns: 1fr; }
    }
</style>

<div class="plus-full-wrapper">
    
    <aside class="plus-sidebar">
        <h3>Kategoriler</h3>
        <ul class="plus-side-list">
            <?php
            $side_kat = $db->query("SELECT * FROM kategoriler WHERE ust_id = 0 AND aktif = 1 ORDER BY sira ASC")->fetchAll();
            foreach($side_kat as $sk):
                $kat_idler = altKategoriIDleriniGetir($db, $sk['id']);
                $in_query = implode(',', array_fill(0, count($kat_idler), '?'));
                $say_sorgu = $db->prepare("SELECT COUNT(id) FROM ilanlar WHERE kategori_id IN ($in_query) AND durum = 'aktif'");
                $say_sorgu->execute($kat_idler);
                $sayi = $say_sorgu->fetchColumn();
            ?>
            <li>
                <a href="kategori.php?slug=<?php echo $sk['slug']; ?>">
                    <span><?php echo htmlspecialchars($sk['adi']); ?></span>
                    <span style="color:#999; font-size:11px;">(<?php echo $sayi; ?>)</span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <main class="plus-content-area">
        
        <div class="corporate-card">
            <div class="corporate-header">
                <span style="font-size: 35px;">🚜</span>
                <h1>Hakkımızda</h1>
            </div>

            <div class="content-text">
                <?php 
                if(!empty($ayar['hakkimizda'])) {
                    echo $ayar['hakkimizda']; 
                } else {
                    echo "Hakkımızda içeriği henüz eklenmemiştir. Yönetim panelinden bu alanı güncelleyebilirsiniz.";
                }
                ?>
            </div>

            <div class="info-stats">
                <div class="stat-item">
                    <h4>%100</h4>
                    <p>Yerli Sermaye</p>
                </div>
                <div class="stat-item">
                    <h4>Güvenli</h4>
                    <p>Tarım Ticareti</p>
                </div>
                <div class="stat-item">
                    <h4>7/24</h4>
                    <p>Kesintisiz Destek</p>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once "parcalar/alt.php"; ?>