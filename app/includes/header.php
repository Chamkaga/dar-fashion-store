<?php
require_once __DIR__ . '/auth.php';
$pageTitle = $pageTitle ?? 'Dar Fashion Store';
$pageDescription = $pageDescription ?? 'Modern fashion marketplace for clothing, shoes, bags, and accessories.';
$basePath = $basePath ?? '..';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo $basePath; ?>/assets/images/favicon.svg">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/responsive.css">
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>
