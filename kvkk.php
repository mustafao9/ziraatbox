<?php 
require_once "parcalar/ust.php"; 

/**
 * ZiraatBox - KVKK & Gizlilik Politikası (Plus Premium)
 * Veritabanındaki 'ayarlar' tablosundan 'kvkk' metnini çeker.
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

    /* ANA LAYOUT NİZAMI */
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
    .plus-side-list a { display: flex; justify-content: space-between; font-size: 13.5px; color: #333; font-weight: 600; padding: 5px 0; }
    
    /* SAĞ İÇERİK ALANI */
    .plus-content-area {
        padding: 30px;
        background: var(--z-bg);
    }

    .policy-card {
        background: #fff;
        border: 1px solid var(--z-border);
        border-radius: 12px;
        padding: 45px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
    }

    .policy-header {
        border-bottom: 3px solid var(--z-sari);
        padding-bottom: 20px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .policy-header h1 {
        font-size: 26px;
        font-weight: 900;
        color: var(--z-yesil);
        margin: 0;
    }

    .policy-text {
        font-size: 15px;
        line-height: 1.8;
        color: #4a5568;
        white-space: pre-line; /* Satır sonlarını otomatik korur */
    }

    /* KVKK ÖZEL VURGU KUTUSU */
    .notice-box {
        background: #fff9eb;
        border-left: 5px solid var(--z-sari);
        padding: 20px;
        margin-bottom: 30px;
        font-size: 14px;
        color: #856404;
        font-weight: 600;
    }

    @media (max-width: 992px) {
        .plus-full-wrapper { grid-template-columns: 1fr; }
        .plus-sidebar { display: none; }
        .plus-content-area { padding: 20px; }
        .policy-card { padding: 25px; }
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
        
        <div class="policy-card">
            <div class="policy-header">
                <span style="font-size: 32px;">⚖️</span>
                <h1>KVKK ve Gizlilik Politikası</h1>
            </div>

            <div class="notice-box">
                Bu metin, 6698 sayılı Kişisel Verilerin Korunması Kanunu uyarınca ZiraatBox kullanıcılarını bilgilendirmek amacıyla hazırlanmıştır.
            </div>

            <div class="policy-text">
                <?php 
                if(!empty($ayar['kvkk'])) {
                    echo $ayar['kvkk']; 
                } else {
                    echo "Gizlilik politikası içeriği henüz sisteme yüklenmemiştir. Lütfen yönetim panelinden güncelleyiniz.";
                }
                ?>
            </div>
            
            <div style="margin-top: 50px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999; text-align: center;">
                Son Güncelleme: <?php echo date("d.m.Y"); ?> | ZiraatBox Hukuk Birimi
            </div>
        </div>

    </main>
</div>

<?php require_once "parcalar/alt.php"; ?>