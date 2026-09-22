<?php
/**
 * ZiraatBox - Profesyonel Geri Yükleme (Restore) Motoru
 * Dosya Yolu: /home/ziraatbo/public_html/yonetim/islem/yedek-yukle.php
 */
require_once "../../sistem/ayar.php";

// 1. YETKİ KONTROLÜ
if(!isset($_SESSION['admin_id']) || $_SESSION['yetki'] != 'admin'){
    die("Yetkisiz erişim!");
}

// Zaman ve Hafıza Limitlerini Artıralım (Büyük yedekler için)
set_time_limit(600); 
ini_set('memory_limit', '512M');

if (isset($_GET['dosya'])) {
    $dosya_adi = basename($_GET['dosya']);
    $yedek_dizini = dirname(__DIR__) . "/yedekler/";
    $tam_yol = $yedek_dizini . $dosya_adi;
    $ana_dizin = dirname(dirname(__DIR__)); // /public_html/

    if (!file_exists($tam_yol)) {
        die("Hata: Yedek dosyası bulunamadı.");
    }

    $uzanti = pathinfo($dosya_adi, PATHINFO_EXTENSION);

    try {
        // ---------------------------------------------------------
        // SENARYO A: SADECE SQL YÜKLEME (.sql dosyası ise)
        // ---------------------------------------------------------
        if ($uzanti == 'sql') {
            $sql_icerik = file_get_contents($tam_yol);
            if($db->exec($sql_icerik) !== false) {
                header("Location: ../yedekleme.php?islem=restore_ok");
                exit;
            }
        }

        // ---------------------------------------------------------
        // SENARYO B: ZIP YÜKLEME (Dosyalar + SQL)
        // ---------------------------------------------------------
        if ($uzanti == 'zip') {
            $zip = new ZipArchive;
            if ($zip->open($tam_yol) === TRUE) {
                
                // 1. Adım: ZIP içindeki SQL dosyasını bul ve içeri aktar
                // Biz yedek alırken SQL'i "veritabani_yedek.sql" ismiyle içine koymuştuk.
                $sql_index = $zip->locateName('veritabani_yedek.sql');
                if ($sql_index !== false) {
                    $sql_icerik = $zip->getFromIndex($sql_index);
                    $db->exec($sql_icerik);
                }

                // 2. Adım: Dosyaları Ana Dizine Çıkart (Overwrite - Üzerine Yazar)
                // Güvenlik notu: Mevcut dosyaları silmez, aynı isimli olanları değiştirir.
                $zip->extractTo($ana_dizin);
                $zip->close();

                header("Location: ../yedekleme.php?islem=restore_ok");
                exit;
            } else {
                die("Hata: ZIP dosyası açılamadı.");
            }
        }

    } catch (PDOException $e) {
        die("Veritabanı Hatası: " . $e->getMessage());
    } catch (Exception $e) {
        die("Sistem Hatası: " . $e->getMessage());
    }
} else {
    die("Geçersiz istek.");
}