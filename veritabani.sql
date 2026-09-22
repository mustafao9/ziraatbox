-- ZiraatBox Ana Veritabanı Şeması (Sıfır Kurulum)
-- Sürüm: 1.0.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE TABLE `ayarlar` (
  `id` tinyint(4) NOT NULL DEFAULT 1,
  `site_baslik` varchar(255) DEFAULT 'ZiraatBox',
  `site_slogan` varchar(255) DEFAULT 'Çiftçiden Tüketiciye Güvenli İlan Platformu',
  `site_desc` text DEFAULT NULL,
  `site_tel` varchar(20) DEFAULT NULL,
  `site_eposta` varchar(100) DEFAULT NULL,
  `site_adres` text DEFAULT NULL,
  `ilan_suresi_gun` int(11) DEFAULT 30,
  `ilan_onay_gerekiyor` tinyint(4) DEFAULT 1,
  `max_resim_sayisi` int(11) DEFAULT 10,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_user` varchar(255) DEFAULT NULL,
  `smtp_pass` varchar(255) DEFAULT NULL,
  `smtp_port` varchar(10) DEFAULT NULL,
  `bakim_modu` tinyint(1) DEFAULT 0,
  `smtp_secure` varchar(10) DEFAULT 'TLS',
  `hakkimizda` text DEFAULT NULL,
  `kvkk` text DEFAULT NULL,
  `sistem_versiyon` varchar(20) DEFAULT '1.0.21'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ayarlar` (`id`, `site_baslik`, `sistem_versiyon`) VALUES (1, 'ZiraatBox', '1.0.21') ON DUPLICATE KEY UPDATE `sistem_versiyon` = '1.0.21';

CREATE TABLE `favoriler` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uye_id` int(10) UNSIGNED NOT NULL,
  `ilan_id` bigint(20) UNSIGNED NOT NULL,
  `eklenen_fiyat` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ilanlar` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ilan_no` bigint(20) UNSIGNED DEFAULT NULL,
  `uye_id` int(10) UNSIGNED NOT NULL,
  `kategori_id` int(10) UNSIGNED NOT NULL,
  `satici_tipi` enum('Uretici','Bayi','Fabrika','Toptanci') NOT NULL DEFAULT 'Uretici',
  `baslik` varchar(255) NOT NULL,
  `slug` varchar(300) NOT NULL,
  `aciklama` mediumtext NOT NULL,
  `fiyat` decimal(12,2) NOT NULL,
  `il` varchar(60) NOT NULL,
  `ilce` varchar(80) NOT NULL,
  `mahalle` varchar(120) DEFAULT NULL,
  `durum` enum('beklemede','aktif','pasif') DEFAULT 'beklemede',
  `kayit_ip` varchar(45) DEFAULT NULL,
  `bitis_tarihi` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `iletisim_tercihi` enum('hepsi','sadece_telefon','sadece_mesaj') NOT NULL DEFAULT 'hepsi',
  `isim_gizle` tinyint(1) NOT NULL DEFAULT 0,
  `goruntulenme_sayisi` int(11) DEFAULT 0,
  `begeni_sayisi` int(11) DEFAULT 0,
  `ip_adresi` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ilan_izlenim_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ilan_id` bigint(20) UNSIGNED NOT NULL,
  `izlenme_tarihi` date NOT NULL,
  `IP_adresi` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ilan_ozellik_verileri` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ilan_id` bigint(20) UNSIGNED NOT NULL,
  `ozellik_id` int(10) UNSIGNED NOT NULL,
  `deger` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ilan_resimleri` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ilan_id` bigint(20) UNSIGNED NOT NULL,
  `dosya_adi` varchar(255) NOT NULL,
  `ana_resim` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ilceler` (
  `id` int(10) UNSIGNED NOT NULL,
  `il_id` int(11) NOT NULL,
  `ilce_adi` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `iller` (
  `id` int(10) UNSIGNED NOT NULL,
  `il_adi` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kategoriler` (
  `id` int(10) UNSIGNED NOT NULL,
  `ust_id` int(10) UNSIGNED DEFAULT 0,
  `adi` varchar(120) NOT NULL,
  `ikon` varchar(50) DEFAULT NULL,
  `slug` varchar(160) NOT NULL,
  `sira` smallint(6) DEFAULT 0,
  `aktif` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kategori_ozellikleri` (
  `id` int(10) UNSIGNED NOT NULL,
  `kategori_id` int(10) UNSIGNED NOT NULL,
  `ozellik_id` int(10) UNSIGNED NOT NULL,
  `sira` smallint(6) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mahalleler` (
  `id` int(10) UNSIGNED NOT NULL,
  `il_id` int(11) DEFAULT NULL,
  `ilce_id` int(11) DEFAULT NULL,
  `semt_id` int(11) NOT NULL,
  `mahalle_adi` varchar(90) NOT NULL,
  `posta_kodu` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mesajlar` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `konusma_id` bigint(20) UNSIGNED NOT NULL,
  `gonderen_id` int(10) UNSIGNED NOT NULL,
  `mesaj` text NOT NULL,
  `okundu` tinyint(4) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mesaj_konusmalari` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ilan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `baslatan_uye_id` int(10) UNSIGNED NOT NULL,
  `karsi_uye_id` int(10) UNSIGNED NOT NULL,
  `son_mesaj_zamani` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ozellik_tanimlari` (
  `id` int(10) UNSIGNED NOT NULL,
  `ozellik_adi` varchar(100) NOT NULL,
  `veri_turu` enum('yazi','sayi','ondalik','yil','tarih','onay','liste') NOT NULL,
  `birim` varchar(20) DEFAULT NULL,
  `liste_icerik` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `semtler` (
  `id` int(10) UNSIGNED NOT NULL,
  `il_id` int(11) DEFAULT NULL,
  `ilce_id` int(11) NOT NULL,
  `semt_adi` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sifre_sifirlama` (
  `id` int(11) NOT NULL,
  `uye_id` int(11) NOT NULL,
  `token` varchar(100) NOT NULL,
  `son_kullanma` datetime NOT NULL,
  `durum` varchar(20) DEFAULT 'beklemede'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sikayetler` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ilan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sikayetci_id` int(10) UNSIGNED NOT NULL,
  `hedef_uye_id` int(10) UNSIGNED DEFAULT NULL,
  `mesaj` text NOT NULL,
  `durum` enum('beklemede','inceleniyor','kapatildi') DEFAULT 'beklemede',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `sonuc_mesaji` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `uyeler` (
  `id` int(10) UNSIGNED NOT NULL,
  `kullanici_adi` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `sifre` varchar(255) DEFAULT NULL,
  `yetki` enum('uye','admin') DEFAULT 'uye',
  `isim_gizle` tinyint(1) DEFAULT 0,
  `ad_soyad` varchar(100) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `il` varchar(50) DEFAULT NULL,
  `ilce` varchar(50) DEFAULT NULL,
  `profil_foto` varchar(255) DEFAULT NULL,
  `puan` decimal(3,1) DEFAULT 5.0,
  `durum` enum('aktif','pasif','banli') DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `son_giris` datetime DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ziyaretler` (
  `id` int(11) NOT NULL,
  `ip_adresi` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `is_bot` tinyint(1) DEFAULT 0,
  `tarih` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `ayarlar` ADD PRIMARY KEY (`id`);
ALTER TABLE `favoriler` ADD PRIMARY KEY (`id`);
ALTER TABLE `ilanlar` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `slug` (`slug`), ADD UNIQUE KEY `ilan_no` (`ilan_no`), ADD KEY `idx_durum_kategori` (`durum`,`kategori_id`), ADD KEY `idx_uye_id` (`uye_id`), ADD KEY `idx_konum` (`il`,`ilce`);
ALTER TABLE `ilan_izlenim_log` ADD PRIMARY KEY (`id`), ADD KEY `ilan_id` (`ilan_id`), ADD KEY `izlenme_tarihi` (`izlenme_tarihi`);
ALTER TABLE `ilan_ozellik_verileri` ADD PRIMARY KEY (`id`), ADD KEY `ilan_id` (`ilan_id`), ADD KEY `ozellik_id` (`ozellik_id`);
ALTER TABLE `ilan_resimleri` ADD PRIMARY KEY (`id`), ADD KEY `ilan_id` (`ilan_id`);
ALTER TABLE `ilceler` ADD PRIMARY KEY (`id`), ADD KEY `ilceler_il_id_index` (`il_id`);
ALTER TABLE `iller` ADD PRIMARY KEY (`id`);
ALTER TABLE `kategoriler` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `slug` (`slug`);
ALTER TABLE `kategori_ozellikleri` ADD PRIMARY KEY (`id`), ADD KEY `kategori_id` (`kategori_id`), ADD KEY `ozellik_id` (`ozellik_id`);
ALTER TABLE `mahalleler` ADD PRIMARY KEY (`id`), ADD KEY `mahalleler_il_id_ilce_id_semt_id_index` (`il_id`,`ilce_id`,`semt_id`), ADD KEY `mahalleler_posta_kodu_index` (`posta_kodu`);
ALTER TABLE `mesajlar` ADD PRIMARY KEY (`id`), ADD KEY `idx_konusma` (`konusma_id`), ADD KEY `idx_gonderen` (`gonderen_id`);
ALTER TABLE `mesaj_konusmalari` ADD PRIMARY KEY (`id`), ADD KEY `idx_baslatan` (`baslatan_uye_id`), ADD KEY `idx_karsi` (`karsi_uye_id`);
ALTER TABLE `ozellik_tanimlari` ADD PRIMARY KEY (`id`);
ALTER TABLE `semtler` ADD PRIMARY KEY (`id`), ADD KEY `semtler_il_id_ilce_id_index` (`il_id`,`ilce_id`);
ALTER TABLE `sifre_sifirlama` ADD PRIMARY KEY (`id`);
ALTER TABLE `sikayetler` ADD PRIMARY KEY (`id`);
ALTER TABLE `uyeler` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `email` (`email`), ADD UNIQUE KEY `kullanici_adi` (`kullanici_adi`);
ALTER TABLE `ziyaretler` ADD PRIMARY KEY (`id`);

ALTER TABLE `favoriler` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `ilanlar` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `ilan_izlenim_log` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `ilan_ozellik_verileri` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `ilan_resimleri` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `ilceler` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `iller` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `kategoriler` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `kategori_ozellikleri` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `mahalleler` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `mesajlar` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `mesaj_konusmalari` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `ozellik_tanimlari` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `semtler` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `sifre_sifirlama` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `sikayetler` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `uyeler` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `ziyaretler` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

COMMIT;
