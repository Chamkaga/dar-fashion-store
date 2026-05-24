<?php
$pageTitle = 'Login | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $database = new Database();
        $conn = $database->connect();

        if (!$conn) {
            $error = 'Database connection failed. Please check your MySQL server and database settings.';
        } else {
            $userController = new UserController($conn);
            $user = $userController->authenticate($email, $password);

            if ($user) {
                login_user($user);
                header('Location: shop.php');
                exit;
            }

            $error = 'Invalid email or password.';
        }
    }
}

include __DIR__ . '/../app/includes/header.php';
?>
<main class="section">
    <div class="container auth-split">
        <section class="auth-showcase">
            <p class="section-kicker">Dar Fashion Store</p>
            <h1>Welcome back to your fashion marketplace.</h1>
            <p>Login to track orders, save wishlist items, and checkout faster with a secure customer account.</p>
        </section>
        <form class="auth-card" action="login.php" method="post">
            <p class="section-kicker">Customer account</p>
            <h1>Login</h1>
            <?php if ($error): ?>
                <p class="alert alert--error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <p class="form-hint">Demo account: amina@example.com / password123</p>
            <label>Email<input type="email" name="email" required></label>
            <label>Password<input type="password" name="password" required></label>
            <button class="button button--primary" type="submit">Login</button>
            <p>New customer? <a href="register.php">Create an account</a></p>
        </form>
    </div>
</main>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
