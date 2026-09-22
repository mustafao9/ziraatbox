<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

// Ayar dosyasını ve veritabanı bağlantısını çek
require_once __DIR__ . '/../sistem/ayar.php';

echo "<h2>🚀 ZiraatBox v1.0.21 Veritabanı Migration Çalıştırıcı</h2>";

$sql_dosyasi = __DIR__ . '/../sistem/guncelleme.sql';

if (!file_exists($sql_dosyasi)) {
    // Eğer dosya canlıda henüz kopyalanmadıysa içeriği doğrudan çalıştıralım
    $sql_icerik = "
    ALTER TABLE ayarlar ADD COLUMN IF NOT EXISTS sistem_versiyon VARCHAR(20) DEFAULT '1.0.21';
    UPDATE ayarlar SET sistem_versiyon = '1.0.21' WHERE id = 1;
    ";
} else {
    $sql_icerik = file_get_contents($sql_dosyasi);
}

try {
    if (isset($db)) {
        $db->exec($sql_icerik);
        echo "<h3 style='color:green;'>✅ TEBRİKLER! Veritabanı migration başarıyla çalıştırıldı ve sistem_versiyon v1.0.21 olarak güncellendi!</h3>";
    } else {
        echo "<h3 style='color:red;'>❌ Veritabanı bağlantısı ($db) bulunamadı!</h3>";
    }
} catch (PDOException $e) {
    echo "<h3 style='color:red;'>❌ SQL Hatası: " . $e->getMessage() . "</h3>";
}
