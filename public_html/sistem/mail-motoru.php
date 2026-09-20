<?php
/**
 * ZiraatBox - Merkezi Mail Gönderim Sistemi
 * PHPMailer & Veritabanı Entegrasyonu (Canlı Sunucu Uyumlu)
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 1. PHPMailer Dosyalarını Çağır (Dizin Yapısına Göre Tam Yol)
// sistem/ klasöründen bir üste çıkıp vendor/ klasörüne giriyoruz
require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer/src/SMTP.php';

/**
 * @param string $alici_mail Alıcının adresi
 * @param string $konu E-posta başlığı
 * @param string $sablon_tipi SIFRE_SIFIRLA, ILAN_ONAY, YENI_UYE
 * @param array $veriler Şablon içinde kullanılacak dinamik bilgiler
 */
function ZiraatMailGonder($alici_mail, $konu, $sablon_tipi, $veriler = []) {
    global $db; 
    
    // 2. SMTP Ayarlarını Veritabanından Çek
    try {
        $ayar = $db->query("SELECT * FROM ayarlar WHERE id = 1")->fetch();
    } catch (Exception $e) {
        return false;
    }
    
    if (!$ayar || empty($ayar['smtp_host'])) return false;

    $mail = new PHPMailer(true);
    
    try {
        // --- SMTP Yapılandırması ---
        $mail->isSMTP();
        $mail->Host       = $ayar['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $ayar['smtp_user'];
        $mail->Password   = $ayar['smtp_pass'];
        $mail->Port       = $ayar['smtp_port'];
        
        // Güvenlik Ayarı (Küçük/Büyük harf duyarlılığı giderildi)
        $secure_type = strtoupper($ayar['smtp_secure']);
        if ($secure_type == 'SSL' || $ayar['smtp_port'] == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($secure_type == 'TLS' || $ayar['smtp_port'] == 587) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
        }

        // Karakter Seti ve Gönderen Bilgisi
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($ayar['smtp_user'], 'ZiraatBox Tarım Pazarı');
        $mail->addAddress($alici_mail);
        $mail->isHTML(true);
        $mail->Subject = $konu;

        // --- Dinamik Şablon İçerikleri ---
        $mesaj_govdesi = "";
        $ad = !empty($veriler['ad']) ? $veriler['ad'] : "Değerli Üreticimiz";

        switch ($sablon_tipi) {
            case 'SIFRE_SIFIRLA':
                $mesaj_govdesi = "<h3>Şifre Sıfırlama Talebi</h3>
                                  <p>Merhaba <b>$ad</b>,</p>
                                  <p>ZiraatBox hesabınızın şifresini sıfırlamak için aşağıdaki butona tıklayabilirsiniz. Bu bağlantı 1 saat boyunca geçerlidir.</p>
                                  <div style='text-align:center; margin:30px 0;'>
                                      <a href='{$veriler['link']}' style='background:#27ae60; color:#fff; padding:12px 25px; text-decoration:none; border-radius:10px; font-weight:bold; display:inline-block;'>ŞİFREMİ SIFIRLA</a>
                                  </div>";
                break;
                
            case 'ILAN_ONAY':
                $ilan_baslik = isset($veriler['ilan_baslik']) ? $veriler['ilan_baslik'] : "İlanınız";
                $mesaj_govdesi = "<h3>Müjde! İlanınız Yayında</h3>
                                  <p>Sayın $ad, <b>$ilan_baslik</b> başlıklı ilanınız onaylanmıştır.</p>
                                  <p>Bereketli satışlar dileriz.</p>";
                break;

            case 'YENI_UYE':
                $mesaj_govdesi = "<h3>ZiraatBox'a Hoş Geldiniz!</h3>
                                  <p>Merhaba $ad, üyeliğiniz başarıyla tamamlanmıştır. Artık ilan verebilir ve diğer üreticilerle güvenle iletişime geçebilirsiniz.</p>";
                break;
        }

        // --- Kurumsal Tasarım (ZiraatBox Yeşili) ---
        $mail->Body = "
            <div style='background:#f4f7f6; padding:30px; font-family:Arial, sans-serif;'>
                <div style='max-width:600px; margin:0 auto; background:#fff; border-radius:15px; overflow:hidden; border:1px solid #e2e8f0;'>
                    <div style='background:#1b4332; padding:25px; text-align:center;'>
                        <h1 style='color:#fff; margin:0; font-size:24px;'>Ziraat<span style='color:#f39c12;'>Box</span></h1>
                    </div>
                    <div style='padding:30px; color:#334155; line-height:1.6;'>
                        $mesaj_govdesi
                    </div>
                    <div style='background:#f8fafc; padding:20px; text-align:center; font-size:11px; color:#94a3b8;'>
                        Bu e-posta <b>ziraatbox.com</b> sistemi üzerinden otomatik gönderilmiştir. <br>
                        Aydın - İncirliova / Tarım ve Hayvancılık Pazarı
                    </div>
                </div>
            </div>";

        return $mail->send();
    } catch (Exception $e) {
        // Hata ayıklama için geçici olarak error_log kullanabilirsiniz
        // error_log("PHPMailer Hatası: " . $mail->ErrorInfo);
        return false;
    }
}