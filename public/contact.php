<?php
$pageTitle = 'Contact | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/includes/auth.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? 'Website inquiry');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $message === '') {
            $error = 'Please complete all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $conn = (new Database())->connect();
            if (!$conn) {
                $error = 'Unable to send your message right now. Please try again later.';
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO messages (name, email, phone, subject, message)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $email, $phone ?: null, $subject, $message]);
                $success = 'Thank you. Your message has been received.';
            }
        }
    }
}

include __DIR__ . '/../app/includes/header.php';
?>
<main class="section">
    <div class="container checkout-layout">
        <form class="checkout-form" method="post">
            <p class="section-kicker">Contact</p>
            <h1>How can we help?</h1>
            <?php if ($success): ?><p class="alert alert--success"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>
            <?php if ($error): ?><p class="alert alert--error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <label>Name<input type="text" name="name" required></label>
            <label>Email<input type="email" name="email" required></label>
            <label>Phone<input type="tel" name="phone"></label>
            <label>Subject<input type="text" name="subject" value="Website inquiry"></label>
            <label>Message<textarea name="message" rows="5" required></textarea></label>
            <button class="button button--primary" type="submit">Send Message</button>
        </form>
        <aside class="summary-panel">
            <h2>Customer Support</h2>
            <p><span>Phone</span><strong>+255 757 742 486</strong></p>
            <p><span>Email</span><strong>support@darfashion.store</strong></p>
            <p><span>Location</span><strong>Dar es Salaam</strong></p>
        </aside>
    </div>
</main>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
