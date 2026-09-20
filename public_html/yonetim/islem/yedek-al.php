<?php
/**
 * ZiraatBox - Esnek & Dinamik Yedekleme Motoru (SQL ZIP Desteği Eklendi)
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once "../../sistem/ayar.php";

if (!isset($_SESSION['admin_id']) OR ($_SESSION['yetki'] ?? '') != 'admin') {
    die("Yetkisiz erişim!");
}

set_time_limit(600);
ini_set('memory_limit', '512M');

$tip = $_GET['tip'] ?? ($_POST['tip'] ?? 'sql');
$tarih = date("Y-m-d_H-i");

// %100 GARANTİLİ YEDEK KLASÖRÜ YOLU: public_html/yonetim/yedekler/
$yedek_klasor = dirname(__DIR__) . "/yedekler/";

// Klasör yoksa tüm yetkilerle (0777) anında oluştur
if (!is_dir($yedek_klasor)) { 
    @mkdir($yedek_klasor, 0777, true); 
}

// --- SQL DÖKÜM FONKSİYONU ---
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

// --- İŞLEM BAŞLASIN ---
try {
    // 💾 A) SADECE SQL YEDEĞİ (ARTIK ZIP OLARAK SIKIŞTIRILIYOR)
    if ($tip == 'sql') {
        $sql_icerik = veritabaniYedekle($db);
        $dosya_adi = "DB_Yedek_" . $tarih . ".zip";
        
        $zip = new ZipArchive();
        if ($zip->open($yedek_klasor . $dosya_adi, ZipArchive::CREATE OR ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFromString("veritabani_yedek_" . $tarih . ".sql", $sql_icerik);
            $zip->close();
            
            header("Location: ../yedekleme.php?islem=ok");
            exit;
        } else {
            die("Hata: SQL ZIP dosyası oluşturulamadı!");
        }
    }

    // 📦 B) KOD + SQL VEYA FULL YEDEK
    if ($tip == 'full' OR $tip == 'no_images') {
        $zip = new ZipArchive();
        $dosya_adi = ($tip == 'full' ? 'FULL_Yedek_' : 'Kod_Yedek_') . $tarih . ".zip";
        
        if ($zip->open($yedek_klasor . $dosya_adi, ZipArchive::CREATE OR ZipArchive::OVERWRITE) === TRUE) {

            // Taranacak kök dizin: public_html/
            $rootPath = dirname(dirname(__DIR__));

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($rootPath) + 1);
                    $normalizedPath = str_replace('\\', '/', $relativePath);

                    // 1. Gizli sistem dosyalarını engelle
                    if (basename($normalizedPath) === '.env' OR strpos($normalizedPath, '.env') !== false) continue;
                    if (strpos($normalizedPath, '.git') !== false) continue;
                    if (strpos($normalizedPath, '.vscode') !== false) continue;

                    // 2. YEDEKLERİ HARİÇ TUT (Sonsuz Döngü Koruması)
                    if (strpos($normalizedPath, 'yonetim/yedekler/') !== false OR strpos($normalizedPath, 'yedekler/') !== false) continue;
                    
                    $ext = strtolower(pathinfo($normalizedPath, PATHINFO_EXTENSION));
                    if ($ext === 'zip' OR $ext === 'sql') continue;

                    // 3. Resimleri hariç tut (no_images modunda)
                    if ($tip == 'no_images') {
                        if (strpos($normalizedPath, 'uploads/') !== false OR strpos($normalizedPath, 'yuklemeler/') !== false) continue;
                    }

                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->addFromString("veritabani_yedek.sql", veritabaniYedekle($db));
            $zip->close();

            header("Location: ../yedekleme.php?islem=ok");
            exit;
        } else {
            die("Hata: ZIP dosyası oluşturulamadı!");
        }
    }
} catch (Exception $e) {
    die("Yedekleme Hatası: " . $e->getMessage());
}
