<?php 
require_once "parcalar/ust.php"; 

// 1. İLAN ID KONTROLÜ
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) { 
    header("Location: index.php"); 
    exit; 
}

// 2. İSTATİSTİK VE LOG SİSTEMİ (Ziyaret Takibi)
$bugun = date('Y-m-d');
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

$ip_kontrol = $db->prepare("SELECT id FROM ilan_izlenim_log WHERE ilan_id = ? AND izlenme_tarihi = ? AND IP_adresi = ?");
$ip_kontrol->execute([$id, $bugun, $ip]);

if (!$ip_kontrol->fetch()) {
    $db->prepare("INSERT INTO ilan_izlenim_log (ilan_id, izlenme_tarihi, IP_adresi) VALUES (?, ?, ?)")->execute([$id, $bugun, $ip]);
    $db->prepare("UPDATE ilanlar SET goruntulenme_sayisi = goruntulenme_sayisi + 1 WHERE id = ?")->execute([$id]);
}

$stmt_iz = $db->prepare("SELECT COUNT(*) FROM ilan_izlenim_log WHERE ilan_id = ? AND izlenme_tarihi = ?");
$stmt_iz->execute([(int)$id, $bugun]);
$bugun_izlenme = $stmt_iz->fetchColumn();

// 3. İLAN, ÜYE VE KONUM BİLGİLERİNİ ÇEKELİM (SQL Şemasına Tam Uyumlu)
$sorgu = $db->prepare("SELECT i.*, 
                        il.il_adi, ilc.ilce_adi, m.mahalle_adi, 
                        u.ad_soyad, u.telefon, u.profil_foto, u.created_at as uye_tarihi
                       FROM ilanlar i 
                       LEFT JOIN iller il ON (i.il = il.id OR i.il = il.il_adi)
                       LEFT JOIN ilceler ilc ON (i.ilce = ilc.id OR i.ilce = ilc.ilce_adi)
                       LEFT JOIN mahalleler m ON (i.mahalle = m.id OR i.mahalle = m.mahalle_adi)
                       LEFT JOIN uyeler u ON i.uye_id = u.id
                       WHERE i.id = ? AND i.durum = 'aktif'");
$sorgu->execute([$id]);
$ilan = $sorgu->fetch(PDO::FETCH_ASSOC);

if (!$ilan) { 
    header("Location: index.php"); 
    exit; 
}

// BREADCRUMB
function breadcrumbGetir($db, $kat_id) {
    $yol = [];
    while ($kat_id > 0) {
        $s = $db->prepare("SELECT id, adi, slug, ust_id FROM kategoriler WHERE id = ?");
        $s->execute([$kat_id]);
        $k = $s->fetch(PDO::FETCH_ASSOC);
        if ($k) {
            array_unshift($yol, '<a href="kategori.php?slug='.htmlspecialchars($k['slug']).'" style="color: #27ae60; text-decoration: none; font-weight: 700;">'.htmlspecialchars($k['adi']).'</a>');
            $kat_id = (int)$k['ust_id'];
        } else { break; }
    }
    return implode(' <span style="color: #cbd5e0; margin: 0 5px;">&gt;</span> ', $yol);
}

// İSİM MASKELEME
$gorunur_isim = htmlspecialchars($ilan['ad_soyad'] ?? 'İlan Sahibi');
if(($ilan['isim_gizle'] ?? 0) == 1) {
    $parca = explode(" ", trim($gorunur_isim));
    $ad = $parca[0];
    $soyad = end($parca);
    $gorunur_isim = htmlspecialchars($ad) . " " . mb_substr(htmlspecialchars($soyad), 0, 1, 'UTF-8') . ".";
}

// GALERİ (uploads/ilanlar/ Standartına Çevrildi)
$resimlerSorgu = $db->prepare("SELECT * FROM ilan_resimleri WHERE ilan_id = ? ORDER BY ana_resim DESC");
$resimlerSorgu->execute([$id]);
$galeri = $resimlerSorgu->fetchAll(PDO::FETCH_ASSOC);

if (count($galeri) > 0 && file_exists("uploads/ilanlar/" . $galeri[0]['dosya_adi'])) {
    $ana_resim = URL . "/uploads/ilanlar/" . $galeri[0]['dosya_adi'];
} else {
    $ana_resim = URL . "/dosyalar/resim/yok.png";
}

// TEKNİK ÖZELLİKLER (Güvenli Prepare Sorgusu)
$ozellikSorgu = $db->prepare("SELECT ot.ozellik_adi, ot.birim, iov.deger 
                              FROM ilan_ozellik_verileri iov 
                              INNER JOIN ozellik_tanimlari ot ON iov.ozellik_id = ot.id 
                              WHERE iov.ilan_id = ? AND iov.deger != ''");
$ozellikSorgu->execute([$id]);
$dinamik_ozellikler = $ozellikSorgu->fetchAll(PDO::FETCH_ASSOC);

$satici_etiket = ['Uretici'=>'Üreticiden','Bayi'=>'Bayiden','Fabrika'=>'Fabrikadan','Toptanci'=>'Toptancıdan'][$ilan['satici_tipi']] ?? 'Üreticiden';
$sehir_adi = !empty($ilan['il_adi']) ? $ilan['il_adi'] : (!empty($ilan['il']) ? $ilan['il'] : 'Belirtilmedi');
$ilce_adi = !empty($ilan['ilce_adi']) ? $ilan['ilce_adi'] : (!empty($ilan['ilce']) ? $ilan['ilce'] : '');
?>

<style>
    :root { --p-green: #27ae60; --p-dark: #1b4332; --p-border: #edf2f7; }
    
    .detay-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 60px; }
    
    .aksiyon-bar { display: flex; gap: 8px; margin-top: 20px; flex-wrap: wrap; }
    .aksiyon-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 12px; border-radius: 10px; border: 1px solid var(--p-border); background: #fff; color: #4a5568; font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 13px; text-decoration: none; }
    .aksiyon-btn:hover { border-color: var(--p-green); color: var(--p-green); background: #f0fdf4; }
    .aksiyon-btn.aktif { color: #e53e3e; border-color: #feb2b2; background: #fff5f5; }
    .btn-whatsapp { color: #25d366 !important; border-color: #25d366 !important; }
    
    .istatistik-kutusu { background: #f8fafc; border: 1px solid var(--p-border); border-radius: 15px; padding: 20px; margin-bottom: 25px; }
    .stat-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; font-size: 13px; color: #4a5568; font-weight: 600; }
    .detay-liste { list-style: none; padding: 0; margin: 0; }
    .detay-liste li { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    .detay-label { color: #718096; font-weight: 600; }
    .detay-val { color: var(--p-dark); font-weight: 800; text-align: right; }

    .btn-sikayet { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 15px; padding: 10px; font-size: 12px; color: #a0aec0; text-decoration: none; font-weight: 700; border-radius: 10px; cursor: pointer; border: 1px dashed #cbd5e0; width: 100%; }
    
    #sikayetModal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); justify-content: center; align-items: center; }
    .sikayet-content { background: #fff; padding: 30px; border-radius: 20px; width: 90%; max-width: 450px; }
    .sikayet-option { display: block; padding: 12px; border: 1px solid #edf2f7; border-radius: 10px; margin-bottom: 10px; cursor: pointer; font-size: 14px; font-weight: 600; color: #4a5568; }

    @media (max-width: 992px) {
        .detay-grid { grid-template-columns: 1fr; gap: 20px; }
        .breadcrumb-box-mobil { padding: 10px 0 !important; font-size: 11px !important; }
        h1 { font-size: 22px !important; }
        #bigImg { height: 300px !important; }
        .thumb-img { width: 70px !important; height: 50px !important; }
        .sticky-card { position: static !important; }
        .price-text { font-size: 30px !important; }
    }
</style>

<div class="konteynir" style="margin-top: 20px;">
    
    <div class="detay-grid">
        <div class="detay-sol">
            
            <div class="breadcrumb-box-mobil" style="margin-bottom: 15px; font-size: 13px; color: #718096;">
                🏠 <a href="index.php" style="color: #718096; text-decoration: none;">Anasayfa</a> 
                <span style="color: #cbd5e0; margin: 0 8px;">&gt;</span> 
                <?php echo breadcrumbGetir($db, $ilan['kategori_id']); ?>
            </div>

            <h1 style="color: var(--p-dark); margin-bottom: 20px; font-weight: 950; line-height: 1.2;">
                <?php echo htmlspecialchars($ilan['baslik']); ?>
            </h1>
            
            <div style="background: #fff; padding: 15px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid var(--p-border);">
                <img src="<?php echo htmlspecialchars($ana_resim); ?>" id="bigImg" style="width: 100%; height: 550px; object-fit: contain; border-radius: 15px; background: #fcfcfc;">
                
                <div style="display: flex; gap: 10px; margin-top: 15px; overflow-x: auto; padding-bottom: 10px; -webkit-overflow-scrolling: touch;">
                    <?php foreach($galeri as $f): 
                        $thumb = file_exists("uploads/ilanlar/" . $f['dosya_adi']) ? URL . "/uploads/ilanlar/" . $f['dosya_adi'] : URL . "/dosyalar/resim/yok.png";
                    ?>
                        <img src="<?php echo htmlspecialchars($thumb); ?>" 
                             class="thumb-img"
                             onclick="document.getElementById('bigImg').src=this.src" 
                             style="width: 110px; height: 80px; object-fit: cover; cursor: pointer; border-radius: 12px; border: 2px solid #f1f5f9; flex-shrink: 0;">
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="margin-top: 30px; background: #fff; padding: 25px; border-radius: 25px; border: 1px solid var(--p-border);">
                <h3 style="border-bottom: 3px solid var(--p-green); display: inline-block; padding-bottom: 8px; color: var(--p-dark); font-weight: 900; margin-bottom: 20px; font-size: 18px;">📄 İlan Açıklaması</h3>
                <div style="line-height: 1.8; color: #4a5568; white-space: pre-line; font-size: 15px;">
                    <?php echo htmlspecialchars($ilan['aciklama']); ?>
                </div>
            </div>
        </div>

        <div class="detay-sag">
            <div class="sticky-card" style="background: #fff; border-radius: 25px; box-shadow: 0 20px 50px rgba(0,0,0,0.06); border: 1px solid var(--p-border); position: sticky; top: 20px; overflow: hidden;">
                
                <div style="padding: 30px 20px; text-align: center; background: #fdfdfd; border-bottom: 1px solid #f1f5f9;">
                    <div class="price-text" style="font-size: 40px; font-weight: 950; color: var(--p-green); margin-bottom: 20px;">
                        <?php echo number_format($ilan['fiyat'], 0, ',', '.'); ?> <small style="font-size: 16px;">TL</small>
                    </div>

                    <div class="aksiyon-bar">
                        <?php 
                        $is_fav = false;
                        if(isset($_SESSION['uye_id'])) {
                            $fav_k = $db->prepare("SELECT id FROM favoriler WHERE uye_id = ? AND ilan_id = ?");
                            $fav_k->execute([$_SESSION['uye_id'], $id]);
                            $is_fav = $fav_k->fetch();
                        }
                        ?>
                        <div class="aksiyon-btn <?php echo $is_fav ? 'aktif' : ''; ?>" id="favBtn" onclick="favoriIslem(<?php echo $id; ?>)">
                            <span id="favIkon"><?php echo $is_fav ? '❤️' : '🤍'; ?></span> <span id="favMetin"><?php echo $is_fav ? 'Favori' : 'Ekle'; ?></span>
                        </div>
                        <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($ilan['baslik']." - ".URL."/ilan-detay.php?id=".$id); ?>" target="_blank" class="aksiyon-btn btn-whatsapp"><span>🟢</span> WhatsApp</a>
                    </div>
                </div>

                <div style="padding: 20px;">
                    <div class="istatistik-kutusu">
                        <div class="stat-row">🔥 Bugün <b><?php echo (int)$bugun_izlenme; ?></b> inceleme</div>
                        <div class="stat-row">📅 Toplam <b><?php echo (int)($ilan['goruntulenme_sayisi'] ?? 0); ?></b> ziyaret</div>
                    </div>

                    <ul class="detay-liste">
                        <li><span class="detay-label">İlan No</span><span class="detay-val" style="color:#e67e22;">#<?php echo htmlspecialchars($ilan['ilan_no'] ?? $id); ?></span></li>
                        
                        <?php if(!empty($dinamik_ozellikler)): foreach($dinamik_ozellikler as $oz): ?>
                            <li><span class="detay-label"><?php echo htmlspecialchars($oz['ozellik_adi']); ?></span><span class="detay-val"><?php echo htmlspecialchars($oz['deger']); ?> <?php echo htmlspecialchars($oz['birim'] ?? ''); ?></span></li>
                        <?php endforeach; endif; ?>

                        <li style="border-top: 2px solid #f1f5f9; margin-top: 10px; padding-top: 10px;">
                            <span class="detay-label">📍 Konum</span>
                            <span class="detay-val"><?php echo htmlspecialchars($sehir_adi); ?><?php echo !empty($ilce_adi) ? ' / ' . htmlspecialchars($ilce_adi) : ''; ?></span>
                        </li>
                    </ul>

                    <div style="margin-top: 25px;">
                        <?php if(($ilan['iletisim_tercihi'] ?? '') != 'sadece_mesaj' && !empty($ilan['telefon'])): ?>
                            <a href="tel:<?php echo htmlspecialchars($ilan['telefon']); ?>" style="display: block; width: 100%; padding: 15px; background: var(--p-green); color: #fff; text-align: center; text-decoration: none; border-radius: 12px; font-weight: 900; font-size: 18px; box-shadow: 0 5px 0 #219150;">📞 ARA: <?php echo htmlspecialchars($ilan['telefon']); ?></a>
                        <?php endif; ?>
                        
                        <div style="margin-top: 15px; display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 12px; border: 1px solid #eee;">
                            <?php 
                            $profil_resim = !empty($ilan['profil_foto']) 
                                ? (filter_var($ilan['profil_foto'], FILTER_VALIDATE_URL) ? $ilan['profil_foto'] : URL . '/uploads/profil/' . $ilan['profil_foto']) 
                                : URL . '/dosyalar/resim/avatar.png';
                            ?>
                            <img src="<?php echo htmlspecialchars($profil_resim); ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;">
                            <div>
                                <div style="font-weight: 800; color: var(--p-dark); font-size: 14px;"><?php echo $gorunur_isim; ?></div>
                                <div style="font-size: 11px; color: #a0aec0;"><?php echo htmlspecialchars($satici_etiket); ?></div>
                            </div>
                        </div>

                        <div style="background: #fff5f5; border: 1px solid #feb2b2; color: #9b2c2c; padding: 10px; border-radius: 10px; font-size: 10px; margin-top: 15px; line-height: 1.3;">
                            🛡️ <b>Yer Sağlayıcı:</b> 5651 Sayılı Kanun gereği ilandan ilan veren sorumludur.
                        </div>

                        <div class="btn-sikayet" onclick="sikayetAc()">
                            <i class="fa-solid fa-triangle-exclamation"></i> İlanı Şikayet Et
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="sikayetModal">
    <div class="sikayet-content">
        <h3 style="margin-top:0; display:flex; justify-content:space-between; align-items:center; font-size:18px;">📢 Şikayet Bildir <span onclick="sikayetKapat()" style="cursor:pointer; font-size:24px;">&times;</span></h3>
        <form id="sikayetForm">
            <input type="hidden" name="ilan_id" value="<?php echo (int)$id; ?>">
            <label class="sikayet-option"><input type="radio" name="neden" value="Fiyat Yanıltıcı" required> 💰 Fiyat Yanıltıcı</label>
            <label class="sikayet-option"><input type="radio" name="neden" value="Sahte İlan"> 🚫 Sahte / Dolandırıcı</label>
            <label class="sikayet-option"><input type="radio" name="neden" value="Diğer"> 📝 Diğer</label>
            <textarea name="ek_not" placeholder="Notunuz..." style="width:100%; height:80px; margin-top:10px; border-radius:8px; border:1px solid #eee; padding:10px; font-family:inherit;"></textarea>
            <button type="button" onclick="sikayetGonder()" style="width:100%; margin-top:15px; padding:15px; background:var(--p-green); color:#fff; border:none; border-radius:10px; font-weight:900; cursor:pointer;">BİLDİRİMİ GÖNDER</button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function favoriIslem(ilanID) {
    <?php if(!isset($_SESSION['uye_id'])): ?>
        Swal.fire({ title: 'Giriş Yapın', text: 'Favoriye eklemek için giriş yapmalısınız.', icon: 'info', confirmButtonText: 'Giriş Yap' }).then((r) => { if(r.isConfirmed) window.location.href='giris.php'; });
        return;
    <?php endif; ?>
    $.post('islem/favori-islem.php', {ilan_id: ilanID}, function(response){
        try {
            const res = typeof response === 'object' ? response : JSON.parse(response);
            if(res.durum == 'eklendi') {
                $('#favBtn').addClass('aktif').find('#favIkon').text('❤️'); $('#favBtn').find('#favMetin').text('Favori');
            } else {
                $('#favBtn').removeClass('aktif').find('#favIkon').text('🤍'); $('#favBtn').find('#favMetin').text('Ekle');
            }
        } catch(e) { console.error("Ayrıştırma hatası:", response); }
    });
}
function sikayetAc() {
    <?php if(!isset($_SESSION['uye_id'])): ?>
        Swal.fire({ title: 'Giriş', text: 'Şikayet için giriş yapmalısınız.', icon: 'warning', confirmButtonText: 'Giriş' }).then((r) => { if(r.isConfirmed) window.location.href='giris.php'; });
    <?php else: ?>
        document.getElementById('sikayetModal').style.display = 'flex';
    <?php endif; ?>
}
function sikayetKapat() { document.getElementById('sikayetModal').style.display = 'none'; }
function sikayetGonder() {
    const form = $('#sikayetForm');
    if(!$('input[name="neden"]:checked').val()){ Swal.fire('Hata', 'Neden seçin.', 'error'); return; }
    $.post('islem/sikayet-et.php', form.serialize(), function(r){
        try {
            const res = typeof r === 'object' ? r : JSON.parse(r);
            if(res.durum == 'basarili'){
                sikayetKapat();
                Swal.fire('Tamam', 'İnceleme başlatıldı.', 'success');
            }
        } catch(e) { console.error("Ayrıştırma hatası:", r); }
    });
}
</script>

<?php require_once "parcalar/alt.php"; ?>
