<?php
$pageTitle = 'Admin Login | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $database = new Database();
    $conn = $database->connect();

    if (!$conn) {
        $error = 'Database connection failed.';
    } else {
        $userController = new UserController($conn);
        $user = $userController->authenticate($email, $password);

        if ($user && ($user['role'] ?? '') === 'admin') {
            login_user($user);
            header('Location: index.php');
            exit;
        }

        $error = 'Invalid admin login.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
<main class="section">
    <div class="container auth-page">
        <form class="auth-card" action="login.php" method="post">
            <p class="section-kicker">Admin dashboard</p>
            <h1>Admin Login</h1>
            <?php if ($error): ?>
                <p class="alert alert--error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <p class="form-hint">Demo admin: admin@darfashion.store / password123</p>
            <label>Email<input type="email" name="email" required></label>
            <label>Password<input type="password" name="password" required></label>
            <button class="button button--primary" type="submit">Login</button>
        </form>
    </div>
</main>
</body>
</html>
