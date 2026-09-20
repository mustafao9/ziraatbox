<?php
/**
 * ZiraatBox - Görsel Dönüştürücü ve Boyutlandırıcı (WebP & Thumbnail)
 */
class ResimIslem {

    /**
     * Yüklenen resmi WebP formatına çevirir, yeniden boyutlandırır ve kaydeder.
     *
     * @param array $file $_FILES['resim'] elemanı
     * @param string $hedefKlasor Kaydedilecek klasör (ör: __DIR__ . '/../uploads/ilanlar/')
     * @param int $maxGenislik Maksimum piksel genişliği (Varsayılan: 1200px)
     * @param int $kalite WebP sıkıştırma kalitesi 1-100 (Varsayılan: 80)
     * @return string|false Başarılı ise dosya adını, değilse false döner
     */
    public static function webpYukle($file, $hedefKlasor, $maxGenislik = 1200, $kalite = 80) {
        if (!isset($file['tmp_name']) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $tmpName = $file['tmp_name'];
        $imageInfo = @getimagesize($tmpName);

        if (!$imageInfo) {
            return false;
        }

        $mime = $imageInfo[0] ? $imageInfo['mime'] : '';
        $srcImage = null;

        // Kaynak resim tipine göre GD kaynağı oluştur
        switch ($mime) {
            case 'image/jpeg':
                $srcImage = @imagecreatefromjpeg($tmpName);
                break;
            case 'image/png':
                $srcImage = @imagecreatefrompng($tmpName);
                break;
            case 'image/webp':
                $srcImage = @imagecreatefromwebp($tmpName);
                break;
            default:
                return false; // Desteklenmeyen format
        }

        if (!$srcImage) {
            return false;
        }

        $genislik = $imageInfo[0];
        $yukseklik = $imageInfo[1];

        // Boyutlandırma Oranı Hesaplama
        if ($genislik > $maxGenislik) {
            $yeniGenislik = $maxGenislik;
            $yeniYukseklik = (int)(($yukseklik / $genislik) * $maxGenislik);
        } else {
            $yeniGenislik = $genislik;
            $yeniYukseklik = $yukseklik;
        }

        // Yeni Tuval Oluştur
        $dstImage = imagecreatetruecolor($yeniGenislik, $yeniYukseklik);

        // PNG veya WEBP şeffaflık koruması
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);

        // Resmi Yeniden Boyutlandır
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $yeniGenislik, $yeniYukseklik, $genislik, $yukseklik);

        // Klasör yoksa oluştur
        if (!file_exists($hedefKlasor)) {
            mkdir($hedefKlasor, 0755, true);
        }

        // Benzersiz dosya adı üret
        $yeniDosyaAdi = uniqid('zb_', true) . '_' . time() . '.webp';
        $kayitYolu = rtrim($hedefKlasor, '/') . '/' . $yeniDosyaAdi;

        // WebP Olarak Kaydet
        $basarili = imagewebp($dstImage, $kayitYolu, $kalite);

        // Belleği Temizle
        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return $basarili ? $yeniDosyaAdi : false;
    }

    /**
     * Ana resimden küçük boyutta Thumbnail (Önizleme) resmi oluşturur.
     */
    public static function thumbOlustur($kaynakDosyaYolu, $hedefKlasor, $genislik = 400, $yukseklik = 300, $kalite = 75) {
        if (!file_exists($kaynakDosyaYolu)) {
            return false;
        }

        $srcImage = @imagecreatefromwebp($kaynakDosyaYolu);
        if (!$srcImage) {
            return false;
        }

        $orjGenislik = imagesx($srcImage);
        $orjYukseklik = imagesy($srcImage);

        $dstImage = imagecreatetruecolor($genislik, $yukseklik);
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);

        // Kırparak merkezleme (Crop & Center)
        $orjOran = $orjGenislik / $orjYukseklik;
        $hedefOran = $genislik / $yukseklik;

        if ($orjOran >= $hedefOran) {
            $cropYukseklik = $orjYukseklik;
            $cropGenislik = (int)($orjYukseklik * $hedefOran);
            $srcX = (int)(($orjGenislik - $cropGenislik) / 2);
            $srcY = 0;
        } else {
            $cropGenislik = $orjGenislik;
            $cropYukseklik = (int)($orjGenislik / $hedefOran);
            $srcX = 0;
            $srcY = (int)(($orjYukseklik - $cropYukseklik) / 2);
        }

        imagecopyresampled($dstImage, $srcImage, 0, 0, $srcX, $srcY, $genislik, $yukseklik, $cropGenislik, $cropYukseklik);

        if (!file_exists($hedefKlasor)) {
            mkdir($hedefKlasor, 0755, true);
        }

        $dosyaAdi = basename($kaynakDosyaYolu);
        $kayitYolu = rtrim($hedefKlasor, '/') . '/thumb_' . $dosyaAdi;

        $basarili = imagewebp($dstImage, $kayitYolu, $kalite);

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return $basarili ? 'thumb_' . $dosyaAdi : false;
    }
}
