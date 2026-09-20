<?php 
require_once "parcalar/ust.php"; 
if (!isset($_SESSION['uye_id'])) { header("Location: giris.php"); exit; }

$uye_id = $_SESSION['uye_id'];

// Üyenin ilanlarını, ana resimlerini ve varsa şikayet sonucundaki admin notunu çekelim
// NOT: Şikayet tablosundaki en güncel 'sonuc_mesaji' bilgisini alıyoruz
$sorgu = $db->prepare("SELECT i.*, 
                        (SELECT dosya_adi FROM ilan_resimleri WHERE ilan_id = i.id ORDER BY ana_resim DESC LIMIT 1) as ana_resim,
                        (SELECT sonuc_mesaji FROM sikayetler WHERE ilan_id = i.id ORDER BY id DESC LIMIT 1) as moderasyon_notu
                       FROM ilanlar i 
                       WHERE i.uye_id = ? 
                       ORDER BY i.id DESC");
$sorgu->execute([$uye_id]);
$ilanlar = $sorgu->fetchAll();
?>

<style>
    :root { --z-green: #27ae60; --z-dark: #1b4332; --z-border: #e2e8f0; --z-orange: #f59e0b; --z-red: #e53e3e; }
    
    .durum-etiket { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; display: inline-block; text-transform: uppercase; }
    .durum-aktif { background: #dcfce7; color: #166534; }
    .durum-beklemede { background: #fef9c3; color: #854d0e; }
    .durum-pasif { background: #f1f5f9; color: #475569; }
    
    .ilan-kart-yonetim { background: #fff; border-radius: 20px; border: 1px solid var(--z-border); margin-bottom: 20px; overflow: hidden; display: flex; align-items: stretch; transition: 0.3s; position: relative; }
    .ilan-kart-yonetim:hover { border-color: var(--z-green); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    
    .kart-resim-alan { width: 180px; position: relative; overflow: hidden; }
    .kart-resim { width: 100%; height: 100%; object-fit: cover; }
    
    .kart-icerik { flex: 1; padding: 20px; display: flex; flex-direction: column; justify-content: center; }
    .kart-butonlar { padding: 20px; display: flex; flex-direction: column; gap: 8px; justify-content: center; background: #fafafa; border-left: 1px solid #f1f5f9; width: 160px; }
    
    .btn-islem { padding: 10px; border-radius: 10px; text-decoration: none; font-size: 13px; font-weight: 700; transition: 0.2s; text-align: center; border: 1px solid transparent; }
    .btn-duzenle { background: #fff; color: #1e293b; border-color: #e2e8f0; }
    .btn-duzenle:hover { background: #f8fafc; border-color: var(--z-green); color: var(--z-green); }
    .btn-sil { background: #fff; color: var(--z-red); border-color: #fee2e2; }
    .btn-sil:hover { background: var(--z-red); color: #fff; }

    /* 🛡️ Moderasyon Uyarı Kutusu */
    .moderasyon-uyari { background: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px; padding: 12px 15px; margin-top: 10px; display: flex; align-items: flex-start; gap: 10px; }
    .moderasyon-uyari i { color: var(--z-orange); margin-top: 2px; }
    .moderasyon-uyari div { font-size: 12px; color: #92400e; line-height: 1.4; }

    @media (max-width: 768px) {
        .ilan-kart-yonetim { flex-direction: column; }
        .kart-resim-alan { width: 100%; height: 200px; }
        .kart-butonlar { width: 100%; flex-direction: row; border-left: 0; border-top: 1px solid #f1f5f9; }
        .kart-butonlar a { flex: 1; }
    }
</style>

<div class="konteynir" style="margin-top: 40px; margin-bottom: 80px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="color: var(--z-dark); font-weight: 950; font-size: 32px; margin: 0;">🚜 İlan Yönetimi</h1>
            <p style="color: #64748b; margin-top: 5px;">Tüm ilanlarınızın yayın durumunu buradan kontrol edin.</p>
        </div>
        <a href="ilan-ver.php" style="background: var(--z-green); color: #fff; padding: 14px 28px; border-radius: 15px; text-decoration: none; font-weight: 800; font-size: 15px; box-shadow: 0 4px 15px rgba(39,174,96,0.3); transition: 0.3s;">+ Yeni İlan Oluştur</a>
    </div>

    <div style="max-width: 1000px;">
        <?php if(count($ilanlar) > 0): foreach($ilanlar as $i): 
            $resim = !empty($i['ana_resim']) ? URL."/yuklemeler/ilanlar/".$i['ana_resim'] : URL."/dosyalar/resim/yok.png";
            
            // Durum Mantığı
            $durumClass = ($i['durum'] == 'aktif') ? 'durum-aktif' : (($i['durum'] == 'beklemede') ? 'durum-beklemede' : 'durum-pasif');
            $durumMetin = ($i['durum'] == 'aktif') ? 'Yayında' : (($i['durum'] == 'beklemede') ? 'İncelemede / Durduruldu' : 'Yayında Değil');
        ?>
            <div class="ilan-kart-yonetim">
                <div class="kart-resim-alan">
                    <img src="<?php echo $resim; ?>" class="kart-resim">
                </div>
                
                <div class="kart-icerik">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div style="font-size: 11px; color: #94a3b8; font-weight: 700; margin-bottom: 5px;">İLAN NO: #<?php echo $i['ilan_no']; ?></div>
                            <div style="font-weight: 800; color: #1e293b; font-size: 18px; margin-bottom: 10px;"><?php echo htmlspecialchars($i['baslik']); ?></div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 900; color: var(--z-green); font-size: 20px;"><?php echo number_format($i['fiyat'], 0, ',', '.'); ?> <small style="font-size:12px;">TL</small></div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
                        <div class="durum-etiket <?php echo $durumClass; ?>"><?php echo $durumMetin; ?></div>
                        <span style="font-size: 12px; color: #94a3b8;"><i class="fa-regular fa-eye"></i> <?php echo $i['goruntulenme_sayisi']; ?> Görüntülenme</span>
                    </div>

                    <?php if($i['durum'] == 'beklemede' && !empty($i['moderasyon_notu'])): ?>
                        <div class="moderasyon-uyari">
                            <i class="fa-solid fa-circle-info"></i>
                            <div>
                                <strong>Yönetici Notu:</strong> <?php echo htmlspecialchars($i['moderasyon_notu']); ?>
                                <br><small style="text-decoration: underline;">Lütfen ilanı bu not doğrultusunda düzenleyiniz.</small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="kart-butonlar">
                    <?php if($i['durum'] == 'aktif'): ?>
                        <a href="ilan-detay.php?id=<?php echo $i['id']; ?>" class="btn-islem btn-duzenle" target="_blank"><i class="fa-regular fa-eye"></i> Görüntüle</a>
                    <?php endif; ?>
                    <a href="ilan-duzenle.php?id=<?php echo $i['id']; ?>" class="btn-islem btn-duzenle"><i class="fa-regular fa-pen-to-square"></i> Düzenle</a>
                    <a href="javascript:void(0)" onclick="ilanSil(<?php echo $i['id']; ?>)" class="btn-islem btn-sil"><i class="fa-regular fa-trash-can"></i> Sil</a>
                </div>
            </div>
        <?php endforeach; else: ?>
            <div style="padding: 80px 20px; text-align: center; background: #fff; border-radius: 30px; border: 2px dashed var(--z-border);">
                <div style="font-size: 50px; margin-bottom: 20px;">🚜</div>
                <h3 style="color: var(--z-dark); font-weight: 900; font-size: 22px;">Henüz bir ilanınız bulunmuyor.</h3>
                <p style="color: #94a3b8; margin-bottom: 30px; max-width: 400px; margin-left: auto; margin-right: auto;">Ürünlerinizi binlerce alıcıyla buluşturmak için hemen ilk ilanınızı oluşturun.</p>
                <a href="ilan-ver.php" style="background: var(--z-green); color:#fff; padding: 15px 40px; border-radius: 15px; text-decoration: none; font-weight: 800;">Hemen İlan Ver ➔</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function ilanSil(id) {
    Swal.fire({
        title: 'İlanı Silmek İstiyor musunuz?',
        text: "Bu işlem geri alınamaz ve tüm fotoğraflar silinir!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e53e3e',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Evet, Sil!',
        cancelButtonText: 'Vazgeç',
        border_radius: '20px'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'islem/ilan-sil.php?id=' + id;
        }
    })
}
</script>

<?php require_once "parcalar/alt.php"; ?>