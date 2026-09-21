<?php 
require_once "parcalar/ust.php"; 

if (!isset($_SESSION['uye_id'])) { header("Location: giris.php"); exit; }

// 1. GÜVENLİK VE VERİ ÇEKME
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// İlan, Kategori ve en güncel Moderasyon Notunu birlikte çekiyoruz
$ilanSorgu = $db->prepare("SELECT i.*, k.adi as kat_adi,
    (SELECT sonuc_mesaji FROM sikayetler WHERE ilan_id = i.id ORDER BY id DESC LIMIT 1) as moderasyon_notu
    FROM ilanlar i 
    LEFT JOIN kategoriler k ON i.kategori_id = k.id 
    WHERE i.id = ? AND i.uye_id = ?");
$ilanSorgu->execute([$id, $_SESSION['uye_id']]);
$i = $ilanSorgu->fetch();

if (!$i) { 
    echo "<div class='konteynir' style='padding:100px; text-align:center;'><h3>🚫 Bu ilana erişim yetkiniz yok veya ilan bulunamadı.</h3><a href='index.php' class='buton'>Anasayfaya Dön</a></div>";
    exit; 
}

// İlanın Mevcut Resimleri
$resimler = $db->prepare("SELECT * FROM ilan_resimleri WHERE ilan_id = ? ORDER BY ana_resim DESC");
$resimler->execute([$id]);
$mevcut_resimler = $resimler->fetchAll();

/**
 * 🌳 KATEGORİ FONKSİYONU
 */
function kategoriSecenekleriDuzenle($db, $ust_id = 0, $derinlik = 0, $secili = 0) {
    $sorgu = $db->prepare("SELECT id, adi FROM kategoriler WHERE ust_id = ? AND aktif = 1 ORDER BY sira ASC, adi ASC");
    $sorgu->execute([$ust_id]);
    foreach($sorgu->fetchAll() as $s) {
        $sel = ($s['id'] == $secili) ? 'selected' : '';
        $stil = ($derinlik == 0) ? "font-weight:900; background:#f8fafc; color:#1b4332;" : "font-weight:400;";
        $prefix = ($derinlik == 0) ? "📂 " : str_repeat('&nbsp;&nbsp;&nbsp;', $derinlik)."└─ ";
        echo '<option value="'.$s['id'].'" '.$sel.' style="'.$stil.'">'.$prefix.$s['adi'].'</option>';
        kategoriSecenekleriDuzenle($db, $s['id'], $derinlik + 1, $secili);
    }
}

// İSİM MASKELEME ÖNİZLEME
$bilgi_sorgu = $db->prepare("SELECT ad_soyad FROM uyeler WHERE id = ?");
$bilgi_sorgu->execute([$_SESSION['uye_id']]);
$u_data = $bilgi_sorgu->fetch();
$oturum_ismi = isset($u_data['ad_soyad']) ? trim($u_data['ad_soyad']) : 'Kullanıcı';
$isim_parca = preg_split('/\s+/', $oturum_ismi, -1, PREG_SPLIT_NO_EMPTY);
$maskeli_onizleme = ($isim_parca[0] ?? 'Kullanıcı') . (isset($isim_parca[1]) ? " " . mb_substr(end($isim_parca), 0, 1, 'UTF-8') . "." : "");
?>

<style>
    :root { --p-green: #27ae60; --p-dark: #1b4332; --p-border: #e2e8f0; --p-red: #e53e3e; --p-orange: #f59e0b; }
    .premium-form { max-width: 1000px; margin: 40px auto; background: #fff; border-radius: 25px; box-shadow: 0 30px 60px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid var(--p-border); }
    .form-header { background: linear-gradient(135deg, var(--p-dark), #2d6a4f); padding: 50px 40px; color: #fff; }
    .form-section { padding: 40px; border-bottom: 1px solid var(--p-border); }
    .s-title { font-size: 18px; font-weight: 900; color: var(--p-dark); margin-bottom: 25px; display: flex; align-items: center; gap: 12px; }
    .s-title span { background: var(--p-green); color: #fff; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 14px; }
    .input-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; }
    .form-label { display: block; font-size: 12px; font-weight: 800; color: #64748b; margin-bottom: 8px; text-transform: uppercase; }
    .form-control { width: 100%; padding: 14px 18px; border: 2px solid var(--p-border); border-radius: 12px; font-size: 15px; transition: 0.3s; box-sizing: border-box; outline: none; }
    .form-control:focus { border-color: var(--p-green); box-shadow: 0 0 0 4px rgba(39, 174, 96, 0.1); background: #f0fdf4; }
    .img-box { position: relative; width: 140px; height: 110px; border-radius: 15px; overflow: hidden; border: 2px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .img-delete { position: absolute; top: 5px; right: 5px; background: rgba(229, 62, 62, 0.9); color: #fff; border: 0; padding: 5px 8px; border-radius: 8px; cursor: pointer; font-size: 11px; font-weight: 700; }
    
    /* 🛡️ Moderasyon Uyarı Kutusu */
    .moderasyon-kutusu { background: #fffbeb; border: 2px solid #fef3c7; margin: 20px 40px -20px 40px; padding: 25px; border-radius: 20px; display: flex; gap: 20px; align-items: flex-start; }
    .mod-icon { font-size: 30px; color: var(--p-orange); }
</style>

<div class="premium-form">
    <div class="form-header">
        <h1 style="margin:0; font-size: 32px; font-weight: 950;">🛠️ İlanı Düzenle</h1>
        <p style="margin:10px 0 0; opacity:0.8; font-size: 16px;">İlan No: #<?php echo $i['ilan_no']; ?> | Düzenlemeleri yapıp tekrar onaya gönderin.</p>
    </div>

    <?php if($i['durum'] == 'beklemede' && !empty($i['moderasyon_notu'])): ?>
    <div class="moderasyon-kutusu">
        <div class="mod-icon">⚠️</div>
        <div>
            <h4 style="margin:0 0 10px 0; color:#92400e; font-weight:900; font-size:18px;">Düzeltilmesi Gereken Hususlar</h4>
            <div style="line-height:1.6; color:#a16207; font-weight:600; font-size:15px;">
                <?php echo nl2br(htmlspecialchars($i['moderasyon_notu'])); ?>
            </div>
            <p style="margin-top:15px; font-size:12px; font-weight:800; color:#d97706; text-transform:uppercase;">ℹ️ Düzenleme yapıp kaydettiğinizde ilanınız yeniden incelemeye alınacaktır.</p>
        </div>
    </div>
    <?php endif; ?>

    <form action="islem/ilan-guncelle.php" method="POST" enctype="multipart/form-data" id="duzenleForm">
        <input type="hidden" name="id" value="<?php echo $i['id']; ?>">

        <div class="form-section">
            <div class="s-title"><span>1</span> Temel Bilgiler</div>
            <div class="form-group" style="margin-bottom:25px;">
                <label class="form-label">İlan Başlığı *</label>
                <input type="text" name="baslik" value="<?php echo htmlspecialchars($i['baslik']); ?>" class="form-control" required>
            </div>
            
            <div class="input-grid">
                <div>
                    <label class="form-label">Kategori *</label>
                    <select name="kategori_id" id="kat_sec" class="form-control" required>
                        <?php kategoriSecenekleriDuzenle($db, 0, 0, $i['kategori_id']); ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Fiyat (TL) *</label>
                    <input type="number" name="fiyat" value="<?php echo $i['fiyat']; ?>" class="form-control" required style="font-weight:900; color:var(--p-green);">
                </div>
                <div>
                    <label class="form-label">Satıcı Tipi</label>
                    <select name="satici_tipi" class="form-control">
                        <option value="Uretici" <?php echo $i['satici_tipi']=='Uretici'?'selected':''; ?>>Üreticiden</option>
                        <option value="Bayi" <?php echo $i['satici_tipi']=='Bayi'?'selected':''; ?>>Bayiden</option>
                        <option value="Fabrika" <?php echo $i['satici_tipi']=='Fabrika'?'selected':''; ?>>Fabrikadan</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section" id="ozellik-alani" style="background: #fafcfb;">
            <div class="s-title"><span>2</span> Teknik Özellikler</div>
            <div id="dinamik-ozellikler" class="input-grid">
                <p style="color:#a0aec0; font-size:13px;">Yükleniyor...</p>
            </div>
        </div>

        <div class="form-section">
            <div class="s-title"><span>3</span> Konum Bilgileri</div>
            <div class="input-grid">
                <div>
                    <label class="form-label">İl</label>
                    <select name="il_id" id="il_sec" class="form-control" required>
                        <?php 
                        $iller = $db->query("SELECT * FROM iller ORDER BY il_adi ASC")->fetchAll();
                        foreach($iller as $il) echo "<option value='{$il['id']}' ".($il['id']==$i['il']?'selected':'').">{$il['il_adi']}</option>";
                        ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">İlçe</label>
                    <select name="ilce_id" id="ilce_sec" class="form-control" required data-secili="<?php echo $i['ilce']; ?>"></select>
                </div>
                <div>
                    <label class="form-label">Mahalle</label>
                    <select name="mahalle_id" id="mahalle_sec" class="form-control" required data-secili="<?php echo $i['mahalle']; ?>"></select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="s-title"><span>4</span> Fotoğraf Yönetimi (Max 5)</div>
            <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
                <?php foreach($mevcut_resimler as $r): ?>
                <div class="img-box">
                    <img src="<?php echo URL; ?>/uploads/ilanlar/<?php echo $r['dosya_adi']; ?>" style="width:100%; height:100%; object-fit:cover;">
                    <label class="img-delete">
                        <input type="checkbox" name="silinecek_resimler[]" value="<?php echo $r['id']; ?>"> Sil
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            <input type="file" name="yeni_resimler[]" multiple class="form-control" style="border: 2px dashed var(--p-green); background: #f0fdf4;">
        </div>

        <div class="form-section">
            <div class="s-title"><span>5</span> Açıklama ve Kaydet</div>
            <textarea name="aciklama" rows="6" class="form-control" placeholder="Ürün detaylarını buraya yazın..."><?php echo htmlspecialchars($i['aciklama']); ?></textarea>
            
            <button type="submit" style="width:100%; background:var(--p-green); color:#fff; border:0; padding:22px; border-radius:15px; font-size:20px; font-weight:900; cursor:pointer; margin-top:30px; box-shadow:0 10px 20px rgba(39, 174, 96, 0.2);">
                🚀 DEĞİŞİKLİKLERİ KAYDET VE ONAMA GÖNDER
            </button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    function ozellikGetir(katID, ilanID) {
        $.post('islem/ozellik-getir-duzenle.php', {kategori_id: katID, ilan_id: ilanID}, function(data){
            $('#dinamik-ozellikler').html(data);
        });
    }
    ozellikGetir(<?php echo $i['kategori_id']; ?>, <?php echo $i['id']; ?>);
    $('#kat_sec').change(function(){ ozellikGetir($(this).val(), <?php echo $i['id']; ?>); });

    function ilceGetir(ilID, seciliID = 0) {
        $.post('islem/konum-getir.php', {il_id: ilID}, function(data){
            $('#ilce_sec').html(data);
            if(seciliID > 0) { 
                $('#ilce_sec').val(seciliID); 
                mahalleGetir(seciliID, $('#mahalle_sec').data('secili'));
            }
        });
    }

    function mahalleGetir(ilceID, seciliID = 0) {
        $.post('islem/konum-getir.php', {ilce_id: ilceID}, function(data){
            $('#mahalle_sec').html(data);
            if(seciliID > 0) { $('#mahalle_sec').val(seciliID); }
        });
    }

    ilceGetir($('#il_sec').val(), $('#ilce_sec').data('secili'));
    $('#il_sec').change(function(){ ilceGetir($(this).val()); });
    $(document).on('change', '#ilce_sec', function(){ mahalleGetir($(this).val()); });
});
</script>

<?php require_once "parcalar/alt.php"; ?>