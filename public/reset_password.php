<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\User;

Auth::start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$token  = $_POST['token'] ?? $_GET['token'] ?? '';
$validan = preg_match('/^[a-f0-9]{64}$/', $token) && User::isResetTokenValid($token);
$uspeh  = false;
$errors = [];

if ($validan && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        die('Neispravan zahtev.');
    }
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Lozinka mora imati najmanje 8 karaktera, uz slova i bar jednu cifru.';
    }
    if ($password !== $confirm) {
        $errors['password_confirm'] = 'Lozinke se ne poklapaju.';
    }

    if (!$errors) {
        if (User::resetPassword($token, $password)) {
            $uspeh = true;
        } else {
            $validan = false;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

    <div class="container py-5" style="max-width: 560px;">
        <?php if ($uspeh): ?>
            <h2 class="naslov">Lozinka je promenjena</h2>
            <p>Sada se možete prijaviti sa novom lozinkom.</p>
            <a href="login.php" class="btn btn-primary">Prijavite se</a>

        <?php elseif (!$validan): ?>
            <h2 class="naslov">Link nije ispravan</h2>
            <p>Link je neispravan, istekao je ili je već iskorišćen.</p>
            <a href="forgot_password.php" class="btn btn-outline-primary">Zatražite novi link</a>

        <?php else: ?>
            <h2 class="naslov">Nova lozinka</h2>
            <form id="resetForm" method="post" action="reset_password.php" novalidate>
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div class="mb-3">
                    <label class="form-label" for="password">Nova lozinka</label>
                    <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           id="password" name="password" placeholder="Minimalno 8 karaktera">
                    <div class="invalid-feedback"><?= e($errors['password'] ?? 'Lozinka mora imati najmanje 8 karaktera, uz slova i bar jednu cifru.') ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirm">Potvrdi lozinku</label>
                    <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                           id="password_confirm" name="password_confirm">
                    <div class="invalid-feedback"><?= e($errors['password_confirm'] ?? 'Lozinke se ne poklapaju.') ?></div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Sačuvaj lozinku</button>
            </form>

            <script>
                document.getElementById('resetForm').addEventListener('submit', function (ev) {
                    const p = document.getElementById('password');
                    const c = document.getElementById('password_confirm');
                    const pOk = p.value.length >= 8 && /[A-Za-z]/.test(p.value) && /[0-9]/.test(p.value);
                    const cOk = p.value === c.value;
                    p.classList.toggle('is-invalid', !pOk);
                    c.classList.toggle('is-invalid', !cOk);
                    if (!pOk || !cOk) ev.preventDefault();
                });
            </script>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>