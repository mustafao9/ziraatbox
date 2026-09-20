<?php
/**
 * ZiraatBox - Otomatik Cron Yedekleme Motoru
 * Bu dosya sunucu (Cron Job) tarafından tetiklenir.
 */

// Tarayıcıdan erişimi engelle (Sadece CLI veya Local tetikleyebilir)
if (php_sapi_name() !== 'cli' && $_SERVER['REMOTE_ADDR'] !== $_SERVER['SERVER_ADDR']) {
    die("Yetkisiz erişim! Bu dosya sadece sistem tarafından çalıştırılabilir.");
}

require_once __DIR__ . "/ayar.php";
require_once __DIR__ . "/yedek_config.php";

// Eğer yedekleme kapalıysa işlemi durdur
if (YEDEK_SIKLIK == 'kapali') exit;

$bugun = date("Y-m-d");
$tarih_damgasi = date("Y-m-d_H-i");
$yedek_klasor = dirname(__DIR__) . "/yonetim/yedekler/";

// Günde sadece 1 kez çalışmasını kontrol et (Mükerrer yedeği önler)
if (defined('SON_YEDEK_TARIHI') && SON_YEDEK_TARIHI == $bugun) exit;

// --- SQL YEDEĞİ FONKSİYONU ---
function get_sql_backup($db) {
    $tables = array();
    $result = $db->query('SHOW TABLES');
    while($row = $result->fetch(PDO::FETCH_NUM)) { $tables[] = $row[0]; }
    $return = "SET NAMES utf8mb4;\n\n";
    foreach($tables as $table) {
        $row2 = $db->query('SHOW CREATE TABLE '.$table)->fetch(PDO::FETCH_NUM);
        $return .= "\n\n".$row2[1].";\n\n";
        $result = $db->query('SELECT * FROM '.$table);
        $num_fields = $result->columnCount();
        for ($i = 0; $i < $num_fields; $i++) {
            while($row = $result->fetch(PDO::FETCH_NUM)) {
                $return .= 'INSERT INTO '.$table.' VALUES(';
                for($j=0; $j<$num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n","\\n",$row[$j]);
                    $return .= isset($row[$j]) ? '"'.$row[$j].'"' : '""';
                    if ($j<($num_fields-1)) $return .= ',';
                }
                $return.= ");\n";
            }
        }
    }
    return $return;
}

// --- YEDEKLEME BAŞLASIN ---
$zip = new ZipArchive();
$dosya_adi = "AUTO_" . strtoupper(YEDEK_ICERIK) . "_" . $tarih_damgasi . ".zip";

if ($zip->open($yedek_klasor . $dosya_adi, ZipArchive::CREATE) === TRUE) {
    $rootPath = realpath(dirname(__DIR__));
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);

            // Filtreler
            if (YEDEK_ICERIK == 'kod_sql' && strpos($relativePath, 'yuklemeler/') === 0) continue;
            if (strpos($relativePath, 'yonetim/yedekler/') === 0) continue;
            if (strpos($relativePath, '.git') !== false) continue;

            $zip->addFile($filePath, $relativePath);
        }
    }
    
    // SQL'i ekle
    $zip->addFromString("veritabani_yedek.sql", get_sql_backup($db));
    $zip->close();

    // Config dosyasını güncelle (Son yedek tarihini yaz)
    $yeni_config = "<?php\ndefine('YEDEK_SIKLIK', '" . YEDEK_SIKLIK . "');\ndefine('YEDEK_ICERIK', '" . YEDEK_ICERIK . "');\ndefine('SON_YEDEK_TARIHI', '" . $bugun . "');\n?>";
    file_put_contents(__DIR__ . "/yedek_config.php", $yeni_config);
    
    echo "Başarılı: Otomatik yedek alındı ($dosya_adi)";
}