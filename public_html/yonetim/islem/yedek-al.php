<?php
/**
 * ZiraatBox - Gelişmiş Yedekleme Motoru (Zaman Aşımı Korumalı)
 */
require_once "../../sistem/ayar.php";

// 🔐 GÜVENLİK VE YETKİ
if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    die("Yetkisiz erişim!");
}

// 🚀 BEYAZ SAYFAYI ENGELLEMEK İÇİN LİMİTLERİ KALDIRALIM
set_time_limit(900); // 15 dakika çalışma süresi
ini_set('memory_limit', '1024M'); // 1 GB RAM kullanımı

$tip = isset($_GET['tip']) ? $_GET['tip'] : (isset($_POST['tip']) ? $_POST['tip'] : 'sql');
$tarih = date("Y-m-d_H-i");
$yedek_klasor = dirname(__DIR__) . "/yedekler/";

// Dizin kontrolü
if (!is_dir($yedek_klasor)) { mkdir($yedek_klasor, 0755, true); }

// --- 1. SQL DÖKÜM FONKSİYONU ---
function veritabaniYedekle($db) {
    $return = "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    $tables = array();
    $result = $db->query('SHOW TABLES');
    while($row = $result->fetch(PDO::FETCH_NUM)) { $tables[] = $row[0]; }
    
    foreach($tables as $table) {
        $row2 = $db->query('SHOW CREATE TABLE '.$table)->fetch(PDO::FETCH_NUM);
        $return .= "\n\n".$row2[1].";\n\n";
        $result = $db->query('SELECT * FROM '.$table);
        $num_fields = $result->columnCount();
        
        while($row = $result->fetch(PDO::FETCH_NUM)) {
            $return .= 'INSERT INTO '.$table.' VALUES(';
            for($j=0; $j<$num_fields; $j++) {
                if (isset($row[$j])) {
                    $val = addslashes($row[$j]);
                    $val = str_replace("\n","\\n",$val);
                    $return .= '"'.$val.'"';
                } else { $return .= 'NULL'; }
                if ($j<($num_fields-1)) { $return .= ','; }
            }
            $return .= ");\n";
        }
    }
    $return .= "\nSET FOREIGN_KEY_CHECKS=1;";
    return $return;
}

// --- 2. İŞLEM BAŞLASIN ---
try {
    if($tip == 'sql') {
        $sql_icerik = veritabaniYedekle($db);
        $dosya_adi = "DB_Yedek_".$tarih.".sql";
        file_put_contents($yedek_klasor . $dosya_adi, $sql_icerik);
        header("Location: ../yedekleme.php?islem=ok");
        exit;
    }

    if($tip == 'full' || $tip == 'no_images') {
        $zip = new ZipArchive();
        $dosya_adi = ($tip == 'full' ? 'FULL_Yedek_' : 'Kod_Yedek_') . $tarih . ".zip";
        
        if ($zip->open($yedek_klasor . $dosya_adi, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $rootPath = realpath(dirname(dirname(__DIR__))); // /public_html/
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($rootPath) + 1);
                    
                    // Windows/Linux yol ayrımı uyumu için ters eğik çizgileri düzelt
                    $normalizedPath = str_replace('\\', '/', $relativePath);

                    // 🛡️ GÜVENLİK VE HARİÇ TUTMA FİLTRELERİ
                    // 1. .env ve gizli sistem yapılandırma dosyalarını zip arşivine ekleme
                    if (basename($normalizedPath) === '.env' || stristr($normalizedPath, '.env')) continue;
                    
                    // 2. Yüklemeleri, yedek klasörlerini, logları ve .git dizinini hariç tut
                    if ($tip == 'no_images' && strpos($normalizedPath, 'yuklemeler/') === 0) continue; 
                    if (strpos($normalizedPath, 'yonetim/yedekler/') === 0) continue; // Yedekleri yedekleme!
                    if (strpos($normalizedPath, 'error_log') !== false) continue; // Logları yedekleme
                    if (strpos($normalizedPath, '.git') !== false) continue; // Git verilerini yedekleme

                    $zip->addFile($filePath, $relativePath);
                }
            }
            
            // SQL'i de ZIP içine ekle
            $zip->addFromString("veritabani_yedek.sql", veritabaniYedekle($db));
            $zip->close();
            
            header("Location: ../yedekleme.php?islem=ok");
            exit;
        } else {
            die("Hata: ZIP dosyası oluşturulamadı.");
        }
    }
} catch (Exception $e) {
    die("Sistem Hatası: " . $e->getMessage());
}
