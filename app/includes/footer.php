<?php $footerBase = $footerBase ?? $basePath ?? '..'; ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <section>
            <a class="logo logo--footer" href="<?php echo $footerBase; ?>/public/index.php">
                <span class="logo__mark">DF</span>
                <span class="logo__text">Dar Fashion Store</span>
            </a>
            <p>Fashion essentials, fast delivery, and trusted checkout for shoppers across Tanzania.</p>
        </section>

        <section>
            <h2>Company</h2>
            <a href="<?php echo $footerBase; ?>/public/about.php">About</a>
            <a href="<?php echo $footerBase; ?>/public/contact.php">Contact</a>
            <a href="<?php echo $footerBase; ?>/public/shop.php">Shop</a>
        </section>

        <section>
            <h2>Support</h2>
            <a href="<?php echo $footerBase; ?>/public/contact.php">Help Center</a>
            <a href="<?php echo $footerBase; ?>/public/order-success.php">Track Order</a>
            <a href="<?php echo $footerBase; ?>/public/checkout.php">Checkout</a>
        </section>

        <section>
            <h2>Newsletter</h2>
            <form class="footer-newsletter" action="<?php echo $footerBase; ?>/public/index.php#newsletter" method="post">
                <label class="sr-only" for="footer-email">Email address</label>
                <input id="footer-email" type="email" placeholder="Email address">
                <button type="submit">Join</button>
            </form>
            <p class="social-links">Instagram | Facebook | WhatsApp</p>
        </section>
    </div>
    <div class="footer-bottom">
        <div class="container">Copyright <?php echo date('Y'); ?> Dar Fashion Store. All rights reserved.</div>
    </div>
</footer>
<script src="<?php echo $footerBase; ?>/assets/js/script.js"></script>
<script src="<?php echo $footerBase; ?>/assets/js/cart.js"></script>
<script src="<?php echo $footerBase; ?>/assets/js/validation.js"></script>
</body>
</html>
