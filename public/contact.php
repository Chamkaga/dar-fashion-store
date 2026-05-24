<?php
$pageTitle = 'Contact | Dar Fashion Store';
$basePath = '..';
include __DIR__ . '/../app/includes/header.php';
?>
<main class="section">
    <div class="container checkout-layout">
        <form class="checkout-form" method="post">
            <p class="section-kicker">Contact</p>
            <h1>How can we help?</h1>
            <label>Name<input type="text" name="name" required></label>
            <label>Email<input type="email" name="email" required></label>
            <label>Message<textarea name="message" rows="5" required></textarea></label>
            <button class="button button--primary" type="submit">Send Message</button>
        </form>
        <aside class="summary-panel">
            <h2>Customer Support</h2>
            <p><span>Phone</span><strong>+255 700 000 000</strong></p>
            <p><span>Email</span><strong>support@darfashion.store</strong></p>
            <p><span>Location</span><strong>Dar es Salaam</strong></p>
        </aside>
    </div>
</main>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
