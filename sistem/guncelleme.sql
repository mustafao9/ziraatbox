-- 1. Ayarlar tablosuna sistem_versiyon kolonu ekle (Varsa atlar, mevcut verilere dokunmaz)
ALTER TABLE ayarlar ADD COLUMN IF NOT EXISTS sistem_versiyon VARCHAR(20) DEFAULT '1.0.0';

-- 2. Sistem versiyonunu guncelle
UPDATE ayarlar SET sistem_versiyon = '1.0.21' WHERE id = 1;
