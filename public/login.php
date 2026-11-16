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

if (Auth::user()) {
    header('Location: ' . Auth::homeFor(Auth::user()['role']));
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        die('Neispravan zahtev.');
    }
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $u = filter_var($email, FILTER_VALIDATE_EMAIL) ? User::findForLogin($email) : null;

    if (!$u || !password_verify($password, $u['password_hash'])) {
        $error = 'Pogrešan e-mail ili lozinka.';
    } elseif (!$u['is_email_verified']) {
        $error = 'Nalog još nije aktiviran. Proverite e-mail i kliknite na aktivacioni link.';
    } elseif (!$u['is_active']) {
        $error = 'Vaš nalog je onemogućen. Obratite se administratoru.';
    } else {
        Auth::login($u);
        header('Location: ' . Auth::homeFor($u['role']));
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>

    <div class="row g-0 register-wrap">
        <div class="col-md-5 d-none d-md-block register-slika"></div>
        <div class="col-md-7 d-flex align-items-center">
            <div class="register-forma w-100">
                <h2 class="naslov">Prijavite se</h2>
                <p class="small">Nemate nalog? <a href="register.php">Registrujte se</a></p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form id="loginForm" method="post" action="login.php" novalidate>
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="email">E-mail adresa</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>">
                        <div class="invalid-feedback">Unesite ispravnu e-mail adresu.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Lozinka</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <div class="invalid-feedback">Unesite lozinku.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Prijava</button>
                    <p class="small mt-3 mb-0"><a href="forgot_password.php">Zaboravljena lozinka?</a></p>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function (ev) {
            const em = document.getElementById('email');
            const pw = document.getElementById('password');
            const emOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em.value.trim());
            const pwOk = pw.value.length > 0;
            em.classList.toggle('is-invalid', !emOk);
            pw.classList.toggle('is-invalid', !pwOk);
            if (!emOk || !pwOk) ev.preventDefault();
        });
    </script>
<?php include __DIR__ . '/includes/footer.php'; ?>