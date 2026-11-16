<?php
require_once __DIR__ . '/../../vendor/autoload.php';
App\Auth::start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$prijavljen = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Elektronski karton za kućne ljubimce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <img src="img/logo.png" alt="Logo">
        </a>
        <div class="d-flex gap-2 align-items-center">
            <?php if ($prijavljen): ?>
                <span class="small text-muted d-none d-md-inline">
                    <?= htmlspecialchars($prijavljen['first_name'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <a class="btn btn-outline-primary btn-sm"
                   href="<?= App\Auth::homeFor($prijavljen['role']) ?>">Moj nalog</a>
                <form method="post" action="logout.php" class="m-0">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-primary btn-sm">Odjava</button>
                </form>
            <?php else: ?>
                <a class="btn btn-primary btn-sm" href="login.php">Prijava</a>
                <a class="btn btn-outline-primary btn-sm" href="register.php">Registracija</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main>