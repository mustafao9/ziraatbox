<?php
/**
 * ZiraatBox - Genel Yardımcı Fonksiyonlar v3.1 (Kök Dizin & SEO Güncellemesi)
 * Mustafa Satılmış - Webmaster
 */

// 🛡️ 1. GÜVENLİK: VERİ TEMİZLEME FONKSİYONU
if (!function_exists('g')) {
    function g($data) {
        if (is_array($data)) {
            return array_map('g', $data);
        }
        return trim(strip_tags($data ?? ''));
    }
}

// 🔗 2. DİNAMİK URL & ASSET ÜRETİCİLERİ
if (!function_exists('url')) {
    function url($yol = '') {
        $cleanPath = ltrim($yol, '/');
        // Eğer eski ziraatbox/public_html kalıntısı geldiyse temizle
        $cleanPath = str_replace(['ziraatbox/public_html/', 'public_html/'], '', $cleanPath);
        return URL . ($cleanPath ? '/' . $cleanPath : '');
    }
}

if (!function_exists('asset')) {
    function asset($yol = '') {
        return url(ltrim($yol, '/'));
    }
}

// 🔗 3. SEO: SEF LINK OLUŞTURUCU
if (!function_exists('sef_link')) {
    function sef_link($str) {
        $preg = array('Ç', 'Ş', 'Ğ', 'Ü', 'İ', 'Ö', 'ç', 'ş', 'ğ', 'ü', 'ö', 'ı', '+', '#', '.', ',', '(', ')', '[', ']', '{', '}', '?', '&', '=', '!', '"', "'");
        $replace = array('c', 's', 'g', 'u', 'i', 'o', 'c', 's', 'g', 'u', 'o', 'i', 'plus', 'sharp', '', '', '', '', '', '', '', '', '', '', '', '', '', '');
        $str = str_replace($preg, $replace, $str);
        $str = preg_replace("@[^A-Za-z0-9\-_]@i", ' ', $str);
        $str = trim(preg_replace('/\s\s+/', ' ', $str));
        $str = str_replace(' ', '-', $str);
        return mb_strtolower($str, 'UTF-8');
    }
}

// 🌳 4. KATEGORİ: SONSUZ HİYERARŞİ FONKSİYONU
if (!function_exists('kategoriListeleAltli')) {
    function kategoriListeleAltli($db, $ust_id = 0, $derinlik = 0, $secili = 0) {
        try {
            $sorgu = $db->prepare("SELECT id, adi FROM kategoriler WHERE ust_id = ? AND aktif = 1 ORDER BY sira ASC");
            $sorgu->execute([(int)$ust_id]);
            $kategoriler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

            foreach ($kategoriler as $kat) {
                $sel = ($kat['id'] == $secili) ? 'selected' : '';
                $girinti = str_repeat('&nbsp;&nbsp;&nbsp;', $derinlik);
                $simge = ($derinlik == 0) ? "📂 " : "└─ ";
                $stil = ($derinlik == 0) ? "font-weight:900; background:#f0fdf4; color:#1b4332;" : "font-weight:400;";
                
                echo '<option value="' . (int)$kat['id'] . '" ' . $sel . ' style="' . $stil . '">' . $girinti . $simge . htmlspecialchars($kat['adi']) . '</option>';
                
                kategoriListeleAltli($db, $kat['id'], $derinlik + 1, $secili);
            }
        } catch (PDOException $e) {
            // Sessiz başarısızlık
        }
    }
}

// 🗺️ 5. NAVİGASYON: BREADCRUMB OLUŞTURUCU
if (!function_exists('katYoluGetir')) {
    function katYoluGetir($db, $kat_id) {
        $yol = [];
        $temp_id = (int)$kat_id;
        while ($temp_id > 0) {
            try {
                $s = $db->prepare("SELECT id, adi, slug, ust_id FROM kategoriler WHERE id = ? LIMIT 1");
                $s->execute([$temp_id]);
                $k = $s->fetch(PDO::FETCH_ASSOC);
                if ($k) {
                    $katUrl = url('kategori.php?slug=' . urlencode($k['slug']));
                    array_unshift($yol, '<a href="' . $katUrl . '" style="color:#27ae60; text-decoration:none; font-weight:700;">' . htmlspecialchars($k['adi']) . '</a>');
                    $temp_id = (int)$k['ust_id'];
                } else { 
                    break; 
                }
            } catch (PDOException $e) {
                break;
            }
        }
        return implode(' <span style="color:#cbd5e0;">&gt;</span> ', $yol);
    }
}

// ⚖️ 6. GERÇEK IP YAKALAMA FONKSİYONU (5651 Uyumlu)
if (!function_exists('getRealIP')) {
    function getRealIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}
