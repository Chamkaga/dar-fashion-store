<?php
$pageTitle = 'Register | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullname === '' || $email === '' || $password === '') {
        $error = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $database = new Database();
        $conn = $database->connect();

        if (!$conn) {
            $error = 'Database connection failed. Please check your MySQL server and database settings.';
        } else {
            $userController = new UserController($conn);

            try {
                $userController->register($fullname, $email, $password, $phone);
                $user = $userController->authenticate($email, $password);
                login_user($user);
                header('Location: shop.php');
                exit;
            } catch (PDOException $e) {
                $error = 'This email is already registered.';
            }
        }
    }
}

include __DIR__ . '/../app/includes/header.php';
?>
<main class="section">
    <div class="container auth-split">
        <section class="auth-showcase auth-showcase--register">
            <p class="section-kicker">Join the store</p>
            <h1>Create your customer account.</h1>
            <p>Register once, then shop clothing, shoes, bags, and accessories with a smoother checkout experience.</p>
        </section>
        <form class="auth-card" action="register.php" method="post">
            <p class="section-kicker">Customer account</p>
            <h1>Register</h1>
            <?php if ($error): ?>
                <p class="alert alert--error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <label>Full Name<input type="text" name="name" required></label>
            <label>Email<input type="email" name="email" required></label>
            <label>Phone<input type="tel" name="phone"></label>
            <label>Password<input type="password" name="password" required></label>
            <button class="button button--primary" type="submit">Create Account</button>
            <p>Already registered? <a href="login.php">Login</a></p>
        </form>
    </div>
</main>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
