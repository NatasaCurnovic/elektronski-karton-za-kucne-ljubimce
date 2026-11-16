<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Database;

$pdo = Database::getConnection();
$broj = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

echo 'Konekcija radi. Broj korisnika u bazi: ' . $broj;