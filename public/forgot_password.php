<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Mailer;
use App\Notification;
use App\User;

Auth::start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$poslato = false;
$error   = '';
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        die('Neispravan zahtev.');
    }
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Unesite ispravnu e-mail adresu.';
    } else {
        $u = User::createResetToken($email);
        if ($u) {
            $config = require __DIR__ . '/../config/config.php';
            $link   = $config['app']['base_url'] . '/reset_password.php?token=' . $u['token'];

            $subject = 'Promena lozinke';
            $html = '<p>Poštovani/a ' . e($u['first_name']) . ',</p>'
                . '<p>Primili smo zahtev za promenu lozinke. Kliknite na link:</p>'
                . '<p><a href="' . e($link) . '">' . e($link) . '</a></p>'
                . '<p>Link važi 1 sat. Ako niste tražili promenu, ignorišite ovu poruku.</p>';

            $ok = Mailer::send($email, $u['first_name'] . ' ' . $u['last_name'], $subject, $html);
            Notification::log((int) $u['id'], 'PASSWORD_RESET', $subject, $ok ? 'SENT' : 'FAILED');
        }
        // Ista poruka bez obzira da li nalog postoji
        $poslato = true;
    }
}

include __DIR__ . '/includes/header.php';
?>

    <div class="container py-5" style="max-width: 560px;">
        <h2 class="naslov">Zaboravljena lozinka</h2>

        <?php if ($poslato): ?>
            <div class="alert alert-success">
                Ako nalog sa tom e-mail adresom postoji, poslali smo link za promenu lozinke (važi 1 sat).
            </div>
            <a href="login.php" class="btn btn-outline-primary">Nazad na prijavu</a>
        <?php else: ?>
            <p>Unesite e-mail adresu naloga i poslaćemo vam link za postavljanje nove lozinke.</p>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

            <form id="forgotForm" method="post" action="forgot_password.php" novalidate>
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <div class="mb-3">
                    <label class="form-label" for="email">E-mail adresa</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>">
                    <div class="invalid-feedback">Unesite ispravnu e-mail adresu.</div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Pošalji link</button>
            </form>

            <script>
                document.getElementById('forgotForm').addEventListener('submit', function (ev) {
                    const em = document.getElementById('email');
                    const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em.value.trim());
                    em.classList.toggle('is-invalid', !ok);
                    if (!ok) ev.preventDefault();
                });
            </script>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>