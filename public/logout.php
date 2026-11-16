<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;

Auth::start();

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    Auth::logout();
}

header('Location: index.php');
exit;