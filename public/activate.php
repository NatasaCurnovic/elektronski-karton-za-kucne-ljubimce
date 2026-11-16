<?php
require __DIR__ . '/../vendor/autoload.php';

use App\User;

$token = $_GET['token'] ?? '';
$uspeh = false;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $uspeh = User::activate($token);
}

include __DIR__ . '/includes/header.php';
?>

    <div class="container py-5" style="max-width: 560px;">
        <?php if ($uspeh): ?>
            <h2 class="naslov">Nalog je aktiviran</h2>
            <p>Vaša e-mail adresa je potvrđena. Sada se možete prijaviti.</p>
            <a href="login.php" class="btn btn-primary">Prijavite se</a>
        <?php else: ?>
            <h2 class="naslov">Link nije ispravan</h2>
            <p>Aktivacioni link je neispravan, istekao je ili je nalog već aktiviran.</p>
            <a href="index.php" class="btn btn-outline-primary">Početna stranica</a>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>