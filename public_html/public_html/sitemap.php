<?php
require_once "sistem/ayar.php";

header("Content-Type: text/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// 🟢 1. ANA SAYFA
echo '<url><loc>'.URL.'/</loc><lastmod>'.date('Y-m-d').'</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>';

// 🟢 2. KATEGORİLER (SEO Uyumlu URL)
$kategoriler = $db->query("SELECT slug FROM kategoriler WHERE aktif = 1");
foreach($kategoriler as $kat) {
    echo '<url>
            <loc>'.URL.'/kategori/'.htmlspecialchars($kat['slug']).'</loc>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
          </url>';
}

// 🟢 3. İLANLAR (SEO Uyumlu URL)
// Eğer ilan-detay.php?id=... yerine slug kullanıyorsanız loc kısmını ona göre yazdım
$ilanlar = $db->query("SELECT slug, created_at FROM ilanlar WHERE durum = 'aktif' ORDER BY id DESC");
foreach($ilanlar as $ilan) {
    $url_ilan = !empty($ilan['slug']) ? URL.'/ilan/'.$ilan['slug'] : URL.'/ilan-detay.php?id='.$ilan['id'];
    echo '<url>
            <loc>'.$url_ilan.'</loc>
            <lastmod>'.date('Y-m-d', strtotime($ilan['created_at'])).'</lastmod>
            <changefreq>daily</changefreq>
            <priority>0.9</priority>
          </url>';
}

// 🟢 4. STATİK SAYFALAR (Uzantısız URL)
echo '<url><loc>'.URL.'/hakkimizda</loc><priority>0.5</priority></url>';
echo '<url><loc>'.URL.'/kvkk</loc><priority>0.5</priority></url>';

echo '</urlset>';
?>