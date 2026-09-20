<?php 
require_once "parcalar/ust.php"; 

if (!isset($_SESSION['uye_id'])) { header("Location: giris.php"); exit; }
$uye_id = $_SESSION['uye_id'];

$sorgu = $db->prepare("SELECT * FROM uyeler WHERE id = ?");
$sorgu->execute([$uye_id]);
$uye = $sorgu->fetch();

if (!$uye) { session_destroy(); header("Location: giris.php"); exit; }
$is_admin = (isset($uye['yetki']) && $uye['yetki'] === 'admin');

// İstatistikler
$toplam_ilan = $db->query("SELECT COUNT(*) FROM ilanlar WHERE uye_id = $uye_id")->fetchColumn();
$favori_say  = $db->query("SELECT COUNT(*) FROM favoriler WHERE uye_id = $uye_id")->fetchColumn();

// 🛡️ Aktif Şikayetlerim (Kullanıcının yaptığı ve henüz sonuçlanmamış şikayetler)
$aktif_sikayet_say = $db->query("SELECT COUNT(*) FROM sikayetler WHERE sikayetci_id = $uye_id AND durum != 'kapatildi'")->fetchColumn();

// İlan Listesi (Kullanıcının kendi ilanları)
$ilan_sorgu = $db->prepare("SELECT i.*, 
    (SELECT dosya_adi FROM ilan_resimleri WHERE ilan_id = i.id ORDER BY ana_resim DESC LIMIT 1) as resim
    FROM ilanlar i WHERE i.uye_id = ? ORDER BY i.id DESC");
$ilan_sorgu->execute([$uye_id]);
$liste = $ilan_sorgu->fetchAll();
?>

<style>
    :root { --z-green: #27ae60; --z-dark: #1b4332; --z-border: #e2e8f0; --z-red: #e11d48; --z-blue: #3b82f6; --z-orange: #f59e0b; }
    body { background: #f1f5f9; }
    .user-container { display: grid; grid-template-columns: 300px 1fr; gap: 30px; max-width: 1300px; margin: 40px auto; padding: 0 15px; }
    
    .user-sidebar { background: #fff; border-radius: 16px; border: 1px solid var(--z-border); padding: 25px; height: fit-content; position: sticky; top: 20px; text-align: center; }
    .profile-avatar { width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 15px; }
    
    .edit-profile-btn { display: inline-block; background: #f0fdf4; color: var(--z-green); padding: 6px 15px; border-radius: 20px; text-decoration: none; font-size: 12px; font-weight: 800; margin-bottom: 20px; transition: 0.3s; border: 1px solid #dcfce7; }
    .user-menu { list-style: none; margin-top: 20px; text-align: left; padding: 0; }
    .user-menu li a { display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 10px; text-decoration: none; color: #475569; font-weight: 700; font-size: 14px; margin-bottom: 5px; }
    .user-menu li a:hover, .user-menu li a.active { background: #f0fdf4; color: var(--z-green); }
    .user-menu li a.active { background: var(--z-green); color: #fff; }

    .stat-card { background: #fff; padding: 25px; border-radius: 16px; border: 1px solid var(--z-border); text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .stat-card b { display: block; font-size: 32px; color: var(--z-dark); margin-bottom: 5px; }
    .stat-card span { font-size: 12px; color: #94a3b8; font-weight: 800; text-transform: uppercase; }

    .data-table-card { background: #fff; border-radius: 16px; border: 1px solid var(--z-border); overflow: hidden; margin-top: 30px; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { background: #f8fafc; padding: 15px; text-align: left; font-size: 12px; color: #64748b; text-transform: uppercase; }
    .data-table td { padding: 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

    .badge-status { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
    .status-beklemede { background: #fef3c7; color: #92400e; }
    .status-aktif { background: #dcfce7; color: #166534; }
    .status-pasif { background: #fee2e2; color: #991b1b; }
    
    .sonuc-notu { font-size: 12px; background: #f8fafc; padding: 10px; border-radius: 8px; border-left: 3px solid var(--z-blue); margin-top: 5px; color: #475569; }

    @media (max-width: 992px) { .user-container { grid-template-columns: 1fr; } .user-sidebar { display: none; } }
</style>

<div class="user-container">
    <aside class="user-sidebar">
        <?php 
        $foto = $uye['profil_foto'];
        $profil_resmi = (filter_var($foto, FILTER_VALIDATE_URL)) ? $foto : (!empty($foto) ? URL.'/yuklemeler/profil/'.$foto : URL.'/dosyalar/resim/avatar.png');
        ?>
        <img src="<?php echo $profil_resmi; ?>" class="profile-avatar">
        <h3 style="margin: 0; color: var(--z-dark); font-weight: 900;"><?php echo htmlspecialchars($uye['ad_soyad']); ?></h3>
        <p style="font-size: 12px; color: #94a3b8; margin: 5px 0 15px;"><?php echo $uye['email']; ?></p>
        
        <a href="profil-ayarlarim.php" class="edit-profile-btn">⚙️ PROFİLİ DÜZENLE</a>

        <ul class="user-menu">
            <?php if($is_admin): ?>
                <li><a href="yonetim/index.php" style="background:#1e293b; color:#fff;"><i class="fas fa-shield-halved"></i> Yönetim Paneli</a></li>
            <?php endif; ?>
            <li><a href="hesabim.php" class="active">📊 Panel Özeti</a></li>
            <li><a href="mesajlarim.php">💬 Mesajlarım</a></li>
            <li><a href="favorilerim.php">⭐ Favorilerim</a></li>
            <li><a href="profil-ayarlarim.php">👤 Profil Ayarları</a></li>
            <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 15px 0;">
            <li><a href="islem/cikis.php" style="color:var(--z-red);"><i class="fas fa-sign-out-alt"></i> Güvenli Çıkış</a></li>
        </ul>
    </aside>

    <main>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; flex-wrap:wrap; gap:15px;">
            <div>
                <h1 style="font-weight: 950; color: var(--z-dark); font-size: 28px; margin:0;">Hesap Paneli</h1>
                <p style="color: #64748b; font-weight: 600; margin:5px 0 0;">ZiraatBox üzerindeki tüm hareketlerinizi buradan izleyin.</p>
            </div>
            <a href="ilan-ver.php" style="background: var(--z-green); color: #fff; text-decoration: none; padding: 12px 25px; border-radius: 12px; font-weight: 800; box-shadow: 0 4px 12px rgba(39,174,96,0.3);">+ YENİ İLAN VER</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div class="stat-card"><b><?php echo $toplam_ilan; ?></b><span>İlanlarım</span></div>
            <div class="stat-card"><b><?php echo $favori_say; ?></b><span>Favorilerim</span></div>
            <div class="stat-card" style="border-color: #fecaca;">
                <b style="color:var(--z-red)"><?php echo $aktif_sikayet_say; ?></b>
                <span style="color:var(--z-red)">Aktif Bildirimlerim</span>
            </div>
        </div>

        <div class="data-table-card">
            <div style="padding: 20px; border-bottom: 1px solid #f1f5f9; font-weight: 900; color: var(--z-dark);">🛒 İlanlarımın Yayındaki Durumu</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>İlan Detayı</th>
                        <th>Durum</th>
                        <th style="text-align: right;">Yönet</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($liste) > 0): foreach($liste as $l): 
                        $img = !empty($l['resim']) ? 'yuklemeler/ilanlar/'.$l['resim'] : 'dosyalar/resim/yok.png';
                        $durum_class = "status-".$l['durum'];
                    ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="<?php echo $img; ?>" style="width: 55px; height: 40px; border-radius: 6px; object-fit: cover; border:1px solid #eee;">
                                <div>
                                    <div style="font-weight: 700; color:var(--z-dark);"><?php echo mb_substr(htmlspecialchars($l['baslik']),0,40); ?>...</div>
                                    <div style="font-size:12px; color:var(--z-green); font-weight:800;"><?php echo number_format($l['fiyat'], 0, ',', '.'); ?> TL</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge-status <?php echo $durum_class; ?>">
                                <?php 
                                    if($l['durum'] == 'beklemede') echo "İncelemede / Durduruldu";
                                    elseif($l['durum'] == 'aktif') echo "Yayında";
                                    else echo "Yayında Değil";
                                ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <a href="ilan-duzenle.php?id=<?php echo $l['id']; ?>" style="color:var(--z-blue); text-decoration:none; font-weight:700; margin-right:10px;">Düzenle</a>
                            <a href="islem/ilan-sil.php?id=<?php echo $l['id']; ?>" onclick="return confirm('İlanı tamamen silmek istediğinize emin misiniz?')" style="color:var(--z-red); text-decoration:none; font-weight:700;">Sil</a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="3" style="padding:40px; text-align:center; color:#94a3b8;">Henüz eklenmiş bir ilanınız bulunmuyor.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="data-table-card" style="margin-top:25px; border-top: 3px solid var(--z-blue);">
            <div style="padding: 20px; border-bottom: 1px solid #f1f5f9; font-weight: 900; color: var(--z-blue);">🛡️ Bildirim ve Şikayet Takip Paneli</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Şikayet Edilen Hedef</th>
                        <th>İnceleme Durumu</th>
                        <th style="text-align: right;">Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sikayet_takip = $db->query("SELECT s.*, i.baslik FROM sikayetler s LEFT JOIN ilanlar i ON s.ilan_id = i.id WHERE s.sikayetci_id = $uye_id ORDER BY s.id DESC LIMIT 8")->fetchAll();
                    if(count($sikayet_takip) > 0): foreach($sikayet_takip as $st):
                    ?>
                    <tr>
                        <td style="max-width:300px;">
                            <div style="font-size:11px; color:#94a3b8; margin-bottom:2px;">Hedef İlan:</div>
                            <b style="color:var(--z-dark);"><?php echo $st['baslik'] ? mb_substr(htmlspecialchars($st['baslik']),0,40).'...' : 'İlan Silinmiş'; ?></b>
                            
                            <?php if(!empty($st['sonuc_mesaji'])): ?>
                                <div class="sonuc-notu">
                                    <strong>⚖️ Hakem Kararı:</strong> <?php echo htmlspecialchars($st['sonuc_mesaji']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($st['durum'] == 'beklemede'): ?>
                                <span class="badge-status" style="background:#e0f2fe; color:#0369a1;">⌛ İNCELENİYOR</span>
                            <?php else: ?>
                                <span class="badge-status" style="background:#f1f5f9; color:#475569;">✔️ SONUÇLANDI</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; font-size:12px; color:#94a3b8;"><?php echo date('d.m.Y', strtotime($st['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="3" style="padding:30px; text-align:center; color:#94a3b8;">Henüz bir şikayet bildiriminiz bulunmuyor.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require_once "parcalar/alt.php"; ?>