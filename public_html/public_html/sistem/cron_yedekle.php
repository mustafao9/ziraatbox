<?php
/**
 * ZiraatBox - Otomatik Cron Yedekleme Motoru
 * Bu dosya sunucu (Cron Job) tarafından tetiklenir.
 */

// Tarayıcıdan erişimi engelle (Sadece CLI veya Local tetikleyebilir)
if (php_sapi_name() !== 'cli' && ($_SERVER['REMOTE_ADDR'] ?? '') !== ($_SERVER['SERVER_ADDR'] ?? '127.0.0.1')) {
    die("Yetkisiz erişim! Bu dosya sadece sistem tarafından çalıştırılabilir.");
}

require_once __DIR__ . "/ayar.php";
require_once __DIR__ . "/yedek_config.php";

// Eğer yedekleme kapalıysa işlemi durdur
if (defined('YEDEK_SIKLIK') && YEDEK_SIKLIK == 'kapali') exit;

$bugun = date("Y-m-d");
$tarih_damgasi = date("Y-m-d_H-i");
$yedek_klasor = dirname(__DIR__) . "/yonetim/yedekler/";

if (!is_dir($yedek_klasor)) {
    mkdir($yedek_klasor, 0755, true);
}

// Günde sadece 1 kez çalışmasını kontrol et
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
                    $row[$j] = addslashes($row[$j] ?? '');
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
$dosya_adi = "AUTO_" . strtoupper(defined('YEDEK_ICERIK') ? YEDEK_ICERIK : 'KOD_SQL') . "_" . $tarih_damgasi . ".zip";

if ($zip->open($yedek_klasor . $dosya_adi, ZipArchive::CREATE) === TRUE) {
    $rootPath = realpath(dirname(__DIR__));
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);
            
            // Yol ayrımı uyumu için ters eğik çizgileri düzelt
            $normalizedPath = str_replace('\\', '/', $relativePath);

            // 🛑 1. GÜVENLİK VE GİZLİLİK FİLTRESİ
            if (basename($normalizedPath) === '.env' || strpos($normalizedPath, '.env') !== false) continue;
            if (strpos($normalizedPath, '.git') !== false) continue;
            if (strpos($normalizedPath, '.idea') !== false || strpos($normalizedPath, '.vscode') !== false) continue;

            // 🛑 2. ESKİ YEDEKLERİN YEDEKLEMEYE GİRMESİNİ ENGELLE (ÖZ-YİNELEME ENGELLENDİ)
            if (strpos($normalizedPath, 'yonetim/yedekler/') !== false || strpos($normalizedPath, 'yedekler/') !== false) continue;
            if (pathinfo($normalizedPath, PATHINFO_EXTENSION) === 'zip') continue;

            // 🛑 3. YÜKLENEN RESİMLERİ / MEDYALARI HARİÇ TUT (Yığılmayı önler)
            if (strpos($normalizedPath, 'uploads/') !== false || strpos($normalizedPath, 'yuklemeler/') !== false) continue;

            $zip->addFile($filePath, $relativePath);
        }
    }
    
    // Veritabanı dökümünü ZIP köküne ekle
    $zip->addFromString("veritabani_yedek.sql", get_sql_backup($db));
    $zip->close();

    // Config dosyasını güncelle
    $yeni_config = "<?php\ndefine('YEDEK_SIKLIK', '" . (defined('YEDEK_SIKLIK') ? YEDEK_SIKLIK : 'gunluk') . "');\ndefine('YEDEK_ICERIK', '" . (defined('YEDEK_ICERIK') ? YEDEK_ICERIK : 'kod_sql') . "');\ndefine('SON_YEDEK_TARIHI', '" . $bugun . "');\n?>";
    file_put_contents(__DIR__ . "/yedek_config.php", $yeni_config);
    
    echo "Başarılı: Otomatik yedek alındı ($dosya_adi)";
}
