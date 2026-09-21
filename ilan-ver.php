<?php 
/**
 * ZiraatBox - Yeni İlan Oluşturma Sayfası
 * Veritabanı Şemasına %100 Uyumlu Versiyon
 */
require_once "parcalar/ust.php"; 

// OTURUM KONTROLÜ
if (!isset($_SESSION['uye_id'])) { 
    header("Location: " . URL . "/giris.php?mesaj=oturum_ac"); 
    exit; 
}

/**
 * 🚀 İSİM MASKELEME MANTIĞI
 */
$bilgi_sorgu = $db->prepare("SELECT ad_soyad FROM uyeler WHERE id = ?");
$bilgi_sorgu->execute([$_SESSION['uye_id']]);
$guncel_uye = $bilgi_sorgu->fetch(PDO::FETCH_ASSOC);

$oturum_ismi = isset($guncel_uye['ad_soyad']) ? trim($guncel_uye['ad_soyad']) : (isset($_SESSION['ad_soyad']) ? trim($_SESSION['ad_soyad']) : 'Kullanıcı');

$isim_parca = preg_split('/\s+/', $oturum_ismi, -1, PREG_SPLIT_NO_EMPTY);
$ilk_ad = isset($isim_parca[0]) ? $isim_parca[0] : 'Kullanıcı';
$soy_ad = (count($isim_parca) > 1) ? end($isim_parca) : '';
$soy_ilk_harf = ($soy_ad != '') ? mb_substr($soy_ad, 0, 1, 'UTF-8') : '';

$maskeli_onizleme = $ilk_ad . ($soy_ilk_harf != '' ? " " . $soy_ilk_harf . "." : "");

/**
 * 🌳 SONSUZ HİYERARŞİ FONKSİYONU (Performans İyileştirmeli)
 */
function ilanKategoriSecenekleri($db) {
    $sorgu = $db->query("SELECT id, ust_id, adi FROM kategoriler WHERE aktif = 1 ORDER BY sira ASC, adi ASC");
    $tum_katlar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
    
    $harita = [];
    foreach ($tum_katlar as $k) {
        $harita[$k['ust_id']][] = $k;
    }

    $yazdir = function($ust_id = 0, $derinlik = 0) use (&$yazdir, $harita) {
        if (isset($harita[$ust_id])) {
            foreach ($harita[$ust_id] as $kat) {
                $stil = ($derinlik == 0) ? "font-weight:900; background:#f0fdf4; color:#1b4332;" : "font-weight:400;";
                $girinti = str_repeat('&nbsp;&nbsp;&nbsp;', $derinlik);
                $simge = ($derinlik == 0) ? "📂 " : "└── ";
                
                echo '<option value="'.(int)$kat['id'].'" style="'.$stil.'">'.$girinti.$simge.htmlspecialchars($kat['adi']).'</option>';
                $yazdir($kat['id'], $derinlik + 1);
            }
        }
    };
    
    $yazdir(0, 0);
}
?>

<style>
    :root { --main-g: #27ae60; --dark-g: #1b4332; --bg-soft: #f4f9f7; --p-border: #edf2f7; }
    .premium-container { max-width: 1000px; margin: 40px auto; background: #fff; border-radius: 25px; box-shadow: 0 30px 60px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid #eef2f1; }
    .p-header { background: linear-gradient(135deg, var(--dark-g), #2d6a4f); padding: 50px 40px; color: #fff; text-align: center; }
    .p-section { padding: 40px; border-bottom: 1px solid #f0f4f2; }
    .section-title { font-size: 18px; font-weight: 900; color: var(--dark-g); margin-bottom: 25px; display: flex; align-items: center; gap: 12px; }
    .section-title span { background: var(--main-g); color: #fff; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 14px; }
    .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; }
    .p-input { width: 100%; padding: 14px 18px; border: 2px solid var(--p-border); border-radius: 12px; font-size: 15px; transition: 0.3s; box-sizing: border-box; outline: none; }
    .p-input:focus { border-color: var(--main-g); box-shadow: 0 0 0 4px rgba(39, 174, 96, 0.1); background: #f0fdf4; }
    .btn-submit { width: 100%; background: var(--main-g); color: #fff; padding: 22px; border: 0; border-radius: 15px; font-size: 20px; font-weight: 900; cursor: pointer; box-shadow: 0 8px 0 #1b4332; transition: 0.2s; }
    .btn-submit:active { transform: translateY(4px); box-shadow: 0 4px 0 #1b4332; }
    .gizlilik-kutu { display: flex; align-items: center; gap: 12px; background: #f8fafc; padding: 18px; border-radius: 15px; border: 1px solid var(--p-border); cursor: pointer; transition: 0.3s; width: 100%; }
</style>

<div class="konteynir">
    <div class="premium-container">
        <div class="p-header">
            <h1 style="margin:0; font-size: 34px; font-weight: 950;">🚀 Yeni İlan Oluştur</h1>
            <p style="margin:15px 0 0; font-size: 16px; opacity: 0.9;">Doğru bilgiler ve alt kategorilerle ilanınızı saniyeler içinde binlerce kişiye ulaştırın.</p>
        </div>

        <form action="<?php echo URL; ?>/islem/ilan-ekle.php" method="POST" enctype="multipart/form-data">
            
            <div class="p-section">
                <div class="section-title"><span>1</span> TEMEL BİLGİLER</div>
                <div style="margin-bottom: 25px;">
                    <label style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase;">İlan Başlığı *</label>
                    <input type="text" name="baslik" required placeholder="Örn: 2023 Model 12 Numara Pulluk" class="p-input">
                </div>
                
                <div class="form-row">
                    <div>
                        <label style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase;">Kategori (Detaylı Seçim) *</label>
                        <select name="kategori_id" id="kategori_sec" required class="p-input">
                            <option value="">Kategori Seçin...</option>
                            <?php ilanKategoriSecenekleri($db); ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase;">Satıcı Tipi *</label>
                        <select name="satici_tipi" required class="p-input">
                            <option value="Uretici">Üreticiden</option>
                            <option value="Bayi">Bayiden</option>
                            <option value="Fabrika">Fabrikadan</option>
                            <option value="Toptanci">Toptancıdan</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase;">Satış Fiyatı (TL) *</label>
                        <input type="number" step="0.01" name="fiyat" required placeholder="0.00" class="p-input" style="font-weight: 900; color: var(--main-g);">
                    </div>
                </div>
            </div>

            <div class="p-section" id="ozellik-panel" style="display:none; background: #fafcfb;">
                <div class="section-title"><span>2</span> TEKNİK ÖZELLİKLER</div>
                <div id="dinamikAlanlar" class="form-row"></div>
            </div>

            <div class="p-section">
                <div class="section-title"><span>3</span> ÜRÜNÜN KONUMU</div>
                <div class="form-row">
                    <div>
                        <label style="font-size: 11px; font-weight: 800; color: #666;">ŞEHİR</label>
                        <select name="il_id" id="il_sec" required class="p-input">
                            <option value="">İl Seçin</option>
                            <?php 
                            $iller = $db->query("SELECT id, il_adi FROM iller ORDER BY il_adi ASC")->fetchAll(PDO::FETCH_ASSOC);
                            foreach($iller as $il) echo "<option value='{$il['id']}'>".htmlspecialchars($il['il_adi'])."</option>";
                            ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 800; color: #666;">İLÇE</label>
                        <select name="ilce_id" id="ilce_sec" required disabled class="p-input"><option value="">Önce İl Seçin</option></select>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <label style="font-size: 11px; font-weight: 800; color: #666;">MAHALLE / KÖY</label>
                            <a href="javascript:void(0)" onclick="yeniKoyEkleModal()" id="koy_ekle_link" style="display:none; color:var(--main-g); font-size:12px; font-weight:700; text-decoration:none;">➕ KÖY EKLE</a>
                        </div>
                        <select name="mahalle_id" id="mahalle_sec" required disabled class="p-input"><option value="">Önce İlçe Seçin</option></select>
                    </div>
                </div>
            </div>

            <div class="p-section">
                <div class="section-title"><span>4</span> FOTOĞRAFLAR</div>
                <div style="border: 3px dashed #cbd5e0; padding: 40px; border-radius: 20px; text-align: center; background: #fafbfc; cursor: pointer;" onclick="document.getElementById('resimInput').click();">
                    <div style="font-size: 50px; margin-bottom: 10px;">📸</div>
                    <p style="color: #2d6a4f; margin: 0; font-weight: 700;">Tıklayın veya Fotoğrafları Buraya Sürükleyin</p>
                    <input type="file" id="resimInput" name="ilan_resimleri[]" multiple accept="image/*" style="display: none;">
                    <div id="onizlemeAlani" style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px; justify-content: center;"></div>
                </div>
            </div>

            <div class="p-section" style="background: #fcfdfd;">
                <div class="section-title"><span>5</span> İLETİŞİM VE GİZLİLİK</div>
                <div class="form-row">
                    <div>
                        <label style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase;">İletişim Tercihi</label>
                        <select name="iletisim_tercihi" class="p-input">
                            <option value="hepsi">Arama ve Uygulama İçi Mesaj</option>
                            <option value="sadece_telefon">Sadece Telefonla Aransın</option>
                            <option value="sadece_mesaj">Sadece Uygulama İçi Mesaj</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <label class="gizlilik-kutu">
                            <input type="checkbox" name="isim_gizle" value="1" style="width: 22px; height: 22px; accent-color: var(--main-g);">
                            <span style="font-weight: 700; color: var(--dark-g); font-size: 14px; margin-left:10px;">
                                İsmimi Maskele (Örn: <?php echo htmlspecialchars($maskeli_onizleme); ?>)
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="p-section">
                <div class="section-title"><span>6</span> AÇIKLAMA VE YAYINLA</div>
                <textarea name="aciklama" rows="6" required placeholder="Ürününüzün durumu ve detaylarını buraya yazın..." class="p-input" style="resize:none; margin-bottom: 30px;"></textarea>
                <button type="submit" class="btn-submit">🚀 İLANIMI ONAYA GÖNDER</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function(){
    // Kategoriye Göre Özellik Getirme
    $('#kategori_sec').change(function(){
        var katID = $(this).val();
        if(katID > 0) {
            $.post('<?php echo URL; ?>/islem/ozellik-getir.php', {kat_id: katID}, function(data){
                if(data.trim() != "") { $('#ozellik-panel').slideDown(); $('#dinamikAlanlar').html(data); }
                else { $('#ozellik-panel').slideUp(); }
            });
        }
    });

    // Konum Yönetimi
    $('#il_sec').change(function(){
        var id = $(this).val();
        $('#ilce_sec').prop('disabled', false).html('<option>Yükleniyor...</option>');
        $.post('<?php echo URL; ?>/islem/konum-getir.php', {il_id: id}, function(data){
            $('#ilce_sec').html('<option value="">İlçe Seçin</option>' + data);
        });
    });

    $('#ilce_sec').change(function(){
        var id = $(this).val();
        $('#mahalle_sec').prop('disabled', false).html('<option>Yükleniyor...</option>');
        $.post('<?php echo URL; ?>/islem/konum-getir.php', {ilce_id: id}, function(data){
            $('#mahalle_sec').html('<option value="">Mahalle Seçin</option>' + data);
            $('#koy_ekle_link').fadeIn();
        });
    });

    // Fotoğraf Önizleme
    $('#resimInput').change(function(){
        $('#onizlemeAlani').html('');
        var files = $(this)[0].files;
        for(var i = 0; i<files.length; i++){
            var reader = new FileReader();
            reader.onload = function(e){
                $('#onizlemeAlani').append('<img src="'+e.target.result+'" style="width:100px; height:80px; object-fit:cover; border-radius:12px; border:2px solid #ddd; margin:5px;">');
            }
            reader.readAsDataURL(files[i]);
        }
    });
});

function yeniKoyEkleModal() {
    const ilId = $('#il_sec').val();
    const ilceId = $('#ilce_sec').val();
    Swal.fire({
        title: 'Yeni Köy/Mahalle Ekle',
        input: 'text',
        inputPlaceholder: 'Adı yazın...',
        showCancelButton: true,
        confirmButtonText: 'Kaydet',
        confirmButtonColor: '#27ae60'
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            $.post('<?php echo URL; ?>/islem/konum-ekle.php', {il_id: ilId, ilce_id: ilceId, mahalle_adi: result.value}, function(response){
                try {
                    const res = typeof response === 'object' ? response : JSON.parse(response);
                    $.post('<?php echo URL; ?>/islem/konum-getir.php', {ilce_id: ilceId}, function(data){
                        $('#mahalle_sec').html(data).val(res.id);
                        Swal.fire('Başarılı!', 'Eklendi ve seçildi.', 'success');
                    });
                } catch(e) { console.error(response); }
            });
        }
    });
}
</script>

<?php require_once "parcalar/alt.php"; ?>