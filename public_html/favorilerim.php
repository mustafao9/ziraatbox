<?php 
require_once "parcalar/ust.php"; 

// 1. OTURUM KONTROLÜ
if(!isset($_SESSION['uye_id'])){
    header("Location: giris.php"); exit;
}

$uye_id = $_SESSION['uye_id'];

// 2. FAVORİ İLANLARI ÇEKELİM (Geliştirilmiş Sorgu)
// Bu sorgu ile ilanın eklendiği fiyattan daha düşük olup olmadığını da kontrol ediyoruz
$sorgu = $db->prepare("SELECT i.*, il.il_adi, ilc.ilce_adi, f.id as favori_id, f.eklenen_fiyat,
                        (SELECT dosya_adi FROM ilan_resimleri WHERE ilan_id = i.id ORDER BY ana_resim DESC LIMIT 1) as ana_resim 
                       FROM favoriler f 
                       JOIN ilanlar i ON f.ilan_id = i.id 
                       LEFT JOIN iller il ON i.il = il.id
                       LEFT JOIN ilceler ilc ON i.ilce = ilc.id
                       WHERE f.uye_id = ? AND i.durum = 'aktif'
                       ORDER BY f.id DESC");
$sorgu->execute([$uye_id]);
$favoriler = $sorgu->fetchAll();
?>

<style>
    :root { --p-green: #27ae60; --p-red: #e74c3c; --p-dark: #1b4332; --p-orange: #f39c12; }
    
    .fav-header { background: linear-gradient(135deg, var(--p-dark) 0%, #2d6a4f 100%); padding: 60px 20px; border-radius: 30px; margin-bottom: 40px; text-align: center; color: #fff; box-shadow: 0 15px 30px rgba(27,67,50,0.15); }
    
    .fav-grid { display: grid; grid-template-columns: 1fr; gap: 20px; max-width: 950px; margin: 0 auto; }
    
    .fav-kart { background: #fff; border-radius: 25px; border: 1px solid #f0f4f2; display: flex; gap: 20px; padding: 20px; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; }
    .fav-kart:hover { transform: scale(1.02); box-shadow: 0 20px 40px rgba(0,0,0,0.06); border-color: var(--p-green); }
    
    .fav-img-wrap { position: relative; width: 220px; height: 160px; flex-shrink: 0; }
    .fav-img { width: 100%; height: 100%; object-fit: cover; border-radius: 20px; }
    
    /* Fiyat Düşüş Etiketi */
    .price-drop-badge { position: absolute; top: -10px; left: -10px; background: var(--p-orange); color: #fff; padding: 6px 12px; border-radius: 12px; font-size: 11px; font-weight: 900; box-shadow: 0 4px 10px rgba(243,156,18,0.4); animation: pulse 2s infinite; }
    
    @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }

    .fav-detay { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
    .fav-konum { font-size: 12px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .fav-baslik { font-size: 20px; font-weight: 800; color: var(--p-dark); text-decoration: none; line-height: 1.3; }
    .fav-fiyat-wrap { display: flex; align-items: baseline; gap: 10px; margin-top: 10px; }
    .fav-fiyat { font-size: 26px; font-weight: 950; color: var(--p-green); letter-spacing: -1px; }
    .eski-fiyat { font-size: 14px; color: #cbd5e0; text-decoration: line-through; }

    .kart-aksiyonlar { display: flex; gap: 10px; margin-top: 15px; }
    .btn-gozat { background: #f0fdf4; color: var(--p-green); padding: 10px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 800; border: 1px solid #dcfce7; transition: 0.3s; }
    .btn-gozat:hover { background: var(--p-green); color: #fff; }
    
    .btn-kaldir { background: #fff1f0; color: var(--p-red); padding: 10px 15px; border-radius: 12px; font-size: 13px; font-weight: 800; border: 1px solid #ffa39e; cursor: pointer; transition: 0.3s; }
    .btn-kaldir:hover { background: var(--p-red); color: #fff; }

    @media (max-width: 768px) {
        .fav-kart { flex-direction: column; }
        .fav-img-wrap { width: 100%; height: 200px; }
    }
</style>

<div class="konteynir" style="margin-top: 30px; margin-bottom: 80px;">
    
    <div class="fav-header">
        <h1 style="margin: 0; font-size: 42px; font-weight: 950;">❤️ Favorilerim</h1>
        <p style="opacity: 0.9; font-size: 18px; font-weight: 500;">Beğendiğin tarım makineleri ve mahsuller burada güvende.</p>
    </div>

    <div class="fav-grid">
        <?php if(count($favoriler) > 0): ?>
            <?php foreach($favoriler as $f): 
                $resim = !empty($f['ana_resim']) ? URL."/yuklemeler/ilanlar/".$f['ana_resim'] : URL."/dosyalar/resim/yok.png";
                $fiyat_dustu_mu = ($f['fiyat'] < $f['eklenen_fiyat']);
            ?>
                <div class="fav-kart" id="favRow_<?php echo $f['id']; ?>">
                    <div class="fav-img-wrap">
                        <?php if($fiyat_dustu_mu): ?>
                            <div class="price-drop-badge">🔥 FİYATI DÜŞTÜ</div>
                        <?php endif; ?>
                        <img src="<?php echo $resim; ?>" class="fav-img">
                    </div>
                    
                    <div class="fav-detay">
                        <div>
                            <div class="fav-konum">📍 <?php echo $f['il_adi']; ?> / <?php echo $f['ilce_adi']; ?></div>
                            <a href="ilan-detay.php?id=<?php echo $f['id']; ?>" class="fav-baslik"><?php echo htmlspecialchars($f['baslik']); ?></a>
                            
                            <div class="fav-fiyat-wrap">
                                <span class="fav-fiyat"><?php echo number_format($f['fiyat'], 0, ',', '.'); ?> TL</span>
                                <?php if($fiyat_dustu_mu): ?>
                                    <span class="eski-fiyat"><?php echo number_format($f['eklenen_fiyat'], 0, ',', '.'); ?> TL</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="kart-aksiyonlar">
                            <a href="ilan-detay.php?id=<?php echo $f['id']; ?>" class="btn-gozat">İlanı İncele</a>
                            <button class="btn-kaldir" onclick="favoriSil(<?php echo $f['id']; ?>)">
                                🗑️ Kaldır
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 100px 20px; background: #fff; border-radius: 40px; border: 3px dashed #edf2f0;">
                <div style="font-size: 80px; margin-bottom: 20px;">🌾</div>
                <h2 style="color: var(--p-dark); font-weight: 900; font-size: 28px;">Listeniz şu an nadasa bırakılmış!</h2>
                <p style="color: #718096; font-size: 16px; margin-bottom: 30px;">Hiç favori ilanınız yok. Hemen ilanlara göz atıp beğendiklerinizi ekleyin.</p>
                <a href="ara.php" style="background: var(--p-green); color: #fff; padding: 18px 40px; border-radius: 15px; text-decoration: none; font-weight: 900; box-shadow: 0 10px 20px rgba(39,174,96,0.2);">İlanları Keşfetmeye Başla</a>
            </div>
        <?php endif; ?>
    </div>
</div>



<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function favoriSil(ilanID) {
    Swal.fire({
        title: 'Favorilerden kaldırılsın mı?',
        text: "Bu ilanı istediğiniz zaman tekrar ekleyebilirsiniz.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#718096',
        confirmButtonText: 'Evet, kaldır',
        cancelButtonText: 'Vazgeç',
        background: '#fff',
        borderRadius: '25px'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('islem/favori-islem.php', {ilan_id: ilanID}, function(response){
                try {
                    const res = JSON.parse(response);
                    if(res.durum == 'cikarildi') {
                        $('#favRow_' + ilanID).css('transform', 'scale(0.8)').fadeOut(400, function(){
                            $(this).remove();
                            if($('.fav-kart').length == 0) { location.reload(); }
                        });
                        
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({ icon: 'success', title: 'İlan başarıyla kaldırıldı' });
                    }
                } catch(e) {
                    console.error("Hata:", response);
                }
            });
        }
    })
}
</script>

<?php require_once "parcalar/alt.php"; ?>