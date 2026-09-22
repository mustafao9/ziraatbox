<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔍 ZiraatBox cPanel Dosya & Klasör İzin Analizi</h2>";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; font-family:sans-serif; width:100%;'>";
echo "<tr style='background:#f2f2f2;'><th>Yol (Path)</th><th>İzin (Octal)</th><th>Yazılabilir mi?</th><th>Okunabilir mi?</th><th>Durum</th></tr>";

$paths = [
    'ROOT' => __DIR__ . '/..',
    'sistem Klasörü' => __DIR__ . '/../sistem',
    'sistem/guncelleme.sql' => __DIR__ . '/../sistem/guncelleme.sql',
    'yonetim Klasörü' => __DIR__,
    'yonetim/islem Klasörü' => __DIR__ . '/islem',
    'guncelle-yap.php' => __DIR__ . '/islem/guncelle-yap.php',
];

foreach ($paths as $label => $path) {
    $exists = file_exists($path);
    $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'YOK';
    $writable = $exists ? (is_writable($path) ? 'YES' : 'NO') : '-';
    $readable = $exists ? (is_readable($path) ? 'YES' : 'NO') : '-';
    
    $status = "OK";
    if (!$exists && $label != 'sistem/guncelleme.sql') $status = "EKSİK";
    if ($perms == '0777' || $perms == '0666') $status = "GÜVENLİK ENGELİ (0777/0666 cPanel'de çalışmaz)";

    echo "<tr>";
    echo "<td><b>{$label}</b><br><small style='color:#666;'>{$path}</small></td>";
    echo "<td align='center'><b>{$perms}</b></td>";
    echo "<td align='center'>{$writable}</td>";
    echo "<td align='center'>{$readable}</td>";
    echo "<td align='center'>{$status}</td>";
    echo "</tr>";
}
echo "</table>";
