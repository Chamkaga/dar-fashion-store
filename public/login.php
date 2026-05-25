<?php
$pageTitle = 'Login | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/models/ActivityLog.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please try again.';
    } else {
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
                
                // Log login activity
                $activityLog = new ActivityLog($conn);
                $activityLog->log($user['id'], 'login', [
                    'details' => ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']
                ]);
                
                header('Location: ' . post_login_redirect($user));
                exit;
            }

            $error = 'Invalid email or password.';
        }
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
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
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
