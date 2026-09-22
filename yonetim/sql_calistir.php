<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../sistem/ayar.php';

try {
    if (isset($db)) {
        $db->exec("UPDATE ayarlar SET sistem_versiyon = '1.0.22' WHERE id = 1;");
        echo "<h2 style='color:green;'>✅ Veritabanı sürümü başarıyla v1.0.22 olarak güncellendi!</h2>";
    }
} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ Hata: " . $e->getMessage() . "</h2>";
}
