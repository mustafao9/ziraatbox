<?php
/**
 * ZiraatBox - Otomatik Güncelleme & Migration & Rollback Motoru
 */
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once "../../sistem/ayar.php";

if (!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin') {
    die("Yetkisiz erişim!");
}

set_time_limit(600);
ini_set('memory_limit', '512M');

$islem = $_GET['islem'] ?? '';
$root_dir = dirname(dirname(__DIR__)); // public_html/
$yedek_dizini = __DIR__ . "/../yedekler/";

if (!is_dir($yedek_dizini)) {
    @mkdir($yedek_dizini, 0777, true);
}

// --- HELPER: SQL DÖKÜM ALMA ---
function getDbDump($db) {
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

try {
    // -------------------------------------------------------------
    // SENARYO 1: OTOMATİK GÜNCELLEME YAP
    // -------------------------------------------------------------
    if ($islem === 'guncelle') {
        $yeni_versiyon = $_GET['version'] ?? '1.0.0';
        $mevcut_versiyon = defined('SISTEM_VERSIYON') ? SISTEM_VERSIYON : '1.0.0';

        // ADIM 1: GÜNCELLEME ÖNCESİ OTOMATİK YEDEK AL (ROLLBACK İÇİN)
        $auto_zip_name = "AUTO_BEFORE_UPDATE_v" . $mevcut_versiyon . "_" . date("Y-m-d_H-i") . ".zip";
        $zip_auto = new ZipArchive();
        if ($zip_auto->open($yedek_dizini . $auto_zip_name, ZipArchive::CREATE OR ZipArchive::OVERWRITE) === TRUE) {
            
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($root_dir) + 1);
                    $normalizedPath = str_replace('\\', '/', $relativePath);

                    if (basename($normalizedPath) === '.env' || strpos($normalizedPath, '.git') !== false) continue;
                    if (strpos($normalizedPath, 'yonetim/yedekler/') !== false) continue;
                    
                    $zip_auto->addFile($filePath, $relativePath);
                }
            }
            $zip_auto->addFromString("veritabani_yedek.sql", getDbDump($db));
            $zip_auto->close();
        }

        // ADIM 2: GITHUB'DAN SON KOD PAKETİNİ İNDİR VE AÇ
        $github_repo = "mustafao9/ziraatbox";
        $zip_download_url = "https://github.com/$github_repo/archive/refs/tags/v$yeni_versiyon.zip";
        $temp_zip = $yedek_dizini . "download_v$yeni_versiyon.zip";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zip_download_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZiraatBox-AutoUpdater');
        $zip_data = curl_exec($ch);
        curl_close($ch);

        if (!$zip_data) {
            throw new Exception("GitHub'dan paket indirilemedi.");
        }

        file_put_contents($temp_zip, $zip_data);

        $zip = new ZipArchive();
        if ($zip->open($temp_zip) === TRUE) {
            // Zip içindeki ilk kök klasör adını bul (Örn: ziraatbox-1.0.2/)
            $root_folder_in_zip = $zip->getNameIndex(0);

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                // Kök klasör adını ayıkla
                $relative_file = substr($filename, strlen($root_folder_in_zip));

                if (empty($relative_file)) continue;

                // Korunacak özel alanlar (.env ve uploads/)
                if (strpos($relative_file, 'uploads/') === 0 || $relative_file === '.env') continue;

                $target_path = $root_dir . '/' . $relative_file;

                if (substr($filename, -1) === '/') {
                    if (!is_dir($target_path)) @mkdir($target_path, 0775, true);
                } else {
                    $dir = dirname($target_path);
                    if (!is_dir($dir)) @mkdir($dir, 0775, true);
                    file_put_contents($target_path, $zip->getFromIndex($i));
                }
            }
            $zip->close();
            @unlink($temp_zip);
        } else {
            throw new Exception("İndirilen güncelleme ZIP paketi açılamadı.");
        }

        // ADIM 3: VERİTABANI MİGRASYONU (sistem/guncellemeler/vX.X.X.sql VARSA ÇALIŞTIR)
        $migration_sql_file = $root_dir . "/sistem/guncellemeler/v" . $yeni_versiyon . ".sql";
        if (file_exists($migration_sql_file)) {
            $sql_query = file_get_contents($migration_sql_file);
            if (!empty(trim($sql_query))) {
                $db->exec($sql_query);
            }
        }

        // ADIM 4: VERSIYON DOSYASINI GÜNCELLE
        $versiyon_file_content = "<?php\nif (!defined('SISTEM_VERSIYON')) {\n    define('SISTEM_VERSIYON', '" . $yeni_versiyon . "');\n}\n";
        file_put_contents($root_dir . "/sistem/versiyon.php", $versiyon_file_content);

        header("Location: ../guncelleme.php?durum=guncellendi&v=" . $yeni_versiyon);
        exit;
    }

    // -------------------------------------------------------------
    // SENARYO 2: ROLLBACK / ESKİYE DÖNÜŞ (GÜNCELLEME İPTALİ)
    // -------------------------------------------------------------
    if ($islem === 'rollback') {
        $dosya_adi = basename($_GET['dosya'] ?? '');
        $tam_yol = $yedek_dizini . $dosya_adi;

        if (!file_exists($tam_yol)) {
            throw new Exception("Rollback yedek dosyası bulunamadı.");
        }

        $zip = new ZipArchive();
        if ($zip->open($tam_yol) === TRUE) {
            
            // 1. Veritabanını Eski Haline Döndür
            $sql_index = $zip->locateName('veritabani_yedek.sql');
            if ($sql_index !== false) {
                $sql_icerik = $zip->getFromIndex($sql_index);
                $db->exec($sql_icerik);
            }

            // 2. Kodları Ana Dizine Çıkart (Overwrite)
            $zip->extractTo($root_dir);
            $zip->close();

            header("Location: ../guncelleme.php?durum=rollback_ok");
            exit;
        } else {
            throw new Exception("Rollback ZIP paketi açılamadı.");
        }
    }

} catch (Exception $e) {
    header("Location: ../guncelleme.php?durum=hata&msg=" . urlencode($e->getMessage()));
    exit;
}
