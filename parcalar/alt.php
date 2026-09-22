</main> <!-- /main kapanışı ust.php ile tam örtüşür -->

<style>
    .site-footer {
        background: #1b4332;
        color: #ecf0f1;
        padding: 45px 0 20px 0;
        margin-top: 50px;
        border-top: 4px solid #f39c12;
    }
    
    .footer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
    }

    .footer-col h3, .footer-col h4 {
        color: #ffffff;
        font-weight: 900;
        margin-top: 0;
        margin-bottom: 18px;
        position: relative;
        padding-bottom: 8px;
    }

    .footer-col h4::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 35px;
        height: 2px;
        background: #f39c12;
    }

    .footer-col p {
        color: #a3b899;
        font-size: 13.5px;
        line-height: 1.6;
    }

    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li {
        margin-bottom: 10px;
    }

    .footer-links a {
        color: #d1d5db;
        text-decoration: none;
        font-size: 13.5px;
        font-weight: 600;
        transition: all 0.2s ease;
        display: inline-block;
    }

    .footer-links a:hover {
        color: #f39c12;
        transform: translateX(4px);
    }

    .footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 20px;
        text-align: center;
        font-size: 13px;
        color: #a3b899;
    }
</style>

<footer class="site-footer">
    <div class="konteynir">
        <div class="footer-grid">
            
            <div class="footer-col">
                <h3 style="font-size: 24px; color: #fff;">Ziraat<span style="color: #f39c12;">Box</span></h3>
                <p>Türkiye'nin güvenli ve lider çiftçi pazaryeri platformu. Üreticiden tüketiciye doğrudan ve hızlı ticaret.</p>
            </div>

            <div class="footer-col">
                <h4>Kurumsal</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo URL; ?>/hakkimizda.php">Hakkımızda</a></li>
                    <li><a href="<?php echo URL; ?>/iletisim.php">İletişim & Destek</a></li>
                    <li><a href="<?php echo URL; ?>/kvkk.php">KVKK ve Gizlilik Politikası</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Hızlı Erişim</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo URL; ?>/ilanlar.php">Tüm İlanlar</a></li>
                    <li><a href="<?php echo URL; ?>/ilan-ver.php">Ücretsiz İlan Ver</a></li>
                    <li><a href="<?php echo URL; ?>/hesabim.php">Hesabım & Panel</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Güvenli Ticaret</h4>
                <p style="font-size: 12px; line-height: 1.5;">
                    🛡️ <b>Yer Sağlayıcı Bildirimi:</b> 5651 sayılı Kanun uyarınca platformumuzda yer alan içeriklerden ilan sahipleri sorumludur.
                </p>
            </div>

        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> <b>ZiraatBox</b>. Tüm Hakları Saklıdır.</p>
        </div>
    </div>
</footer>

<script src="<?php echo URL; ?>/dosyalar/js/script.js"></script>
</body>
</html>