<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\Admin;
use App\User;
use App\VetAccount;
use App\Mailer;
use App\Notification;

$user      = Auth::requireRole(['ADMIN']);
$stanice   = Admin::stations();
$staniceId = array_map(fn($s) => (int) $s['id'], $stanice);

$v = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '',
      'station' => '', 'license' => '', 'spec' => ''];
$greske = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_POST['csrf'] ?? '';
    if ($tok === '' || !hash_equals($_SESSION['csrf'], $tok)) {
        http_response_code(400);
        die('Nevažeći zahtev.');
    }

    foreach ($v as $k => $_) {
        $v[$k] = trim($_POST[$k] ?? '');
    }

    if ($v['first_name'] === '' || mb_strlen($v['first_name']) > 100) {
        $greske[] = 'Ime je obavezno (najviše 100 karaktera).';
    }
    if ($v['last_name'] === '' || mb_strlen($v['last_name']) > 100) {
        $greske[] = 'Prezime je obavezno (najviše 100 karaktera).';
    }
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($v['email']) > 191) {
        $greske[] = 'Unesite ispravan e-mail.';
    } elseif (User::findByEmail($v['email'])) {
        $greske[] = 'Nalog sa tim e-mailom već postoji.';
    }
    if ($v['phone'] !== '' && !preg_match('/^[0-9+\/\s()-]{6,30}$/', $v['phone'])) {
        $greske[] = 'Telefon može sadržati samo cifre, razmake i znakove + / ( ) -.';
    }
    if (!in_array((int) $v['station'], $staniceId, true)) {
        $greske[] = 'Izaberite stanicu.';
    }
    if (mb_strlen($v['license']) > 100 || mb_strlen($v['spec']) > 150) {
        $greske[] = 'Broj licence (najviše 100) ili specijalizacija (najviše 150) su predugi.';
    }

    if (!$greske) {
        try {
            $novi = VetAccount::create(
                $v, (int) $v['station'], $v['license'] ?: null, $v['spec'] ?: null
            );

            // E-mail sa linkom za postavljanje lozinke
            $config  = require __DIR__ . '/../config/config.php';
            $url     = $config['app']['base_url'] . '/reset_password.php?token=' . $novi['token'];
            $subject = 'Vaš nalog veterinara je kreiran';
            $html = '<p>Poštovani/a dr ' . e($v['first_name'] . ' ' . $v['last_name']) . ',</p>'
                  . '<p>Administrator je kreirao vaš nalog u sistemu elektronskog kartona za kućne ljubimce.</p>'
                  . '<p>Lozinku postavljate preko ovog linka (važi 48 sati):<br>'
                  . '<a href="' . e($url) . '">' . e($url) . '</a></p>'
                  . '<p>Posle toga se prijavljujete svojim e-mailom i izabranom lozinkom.</p>';

            $poslato = Mailer::send($v['email'], $v['first_name'] . ' ' . $v['last_name'], $subject, $html);
            Notification::log($novi['id'], 'ACCOUNT_ACTIVATION', $subject, $poslato ? 'SENT' : 'FAILED');

            header('Location: veterinar_novi.php?kreiran=' . ($poslato ? '1' : '2'));
            exit;
        } catch (\PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                $greske[] = 'Taj broj licence ili e-mail su već u upotrebi.';
            } else {
                throw $e;
            }
        }
    }
}

$aktivna = 'veterinar';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/admin_start.php';
?>

<h2 class="naslov">Novi veterinar</h2>

<?php if (($_GET['kreiran'] ?? '') === '1'): ?>
    <div class="alert alert-success py-2">Veterinar je kreiran i poslat mu je e-mail za postavljanje lozinke.</div>
<?php elseif (($_GET['kreiran'] ?? '') === '2'): ?>
    <div class="alert alert-warning py-2">Veterinar je kreiran, ali e-mail nije poslat.</div>
<?php endif; ?>
<?php foreach ($greske as $g): ?>
    <div class="alert alert-danger py-2"><?= e($g) ?></div>
<?php endforeach; ?>

<div class="zapis" style="max-width: 640px;">
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

        <div class="row g-2 mb-3">
            <div class="col-sm-6">
                <label class="form-label">Ime *</label>
                <input type="text" name="first_name" class="form-control" maxlength="100"
                       value="<?= e($v['first_name']) ?>" required>
            </div>
            <div class="col-sm-6">
                <label class="form-label">Prezime *</label>
                <input type="text" name="last_name" class="form-control" maxlength="100"
                       value="<?= e($v['last_name']) ?>" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">E-mail *</label>
            <input type="email" name="email" class="form-control" maxlength="191"
                   value="<?= e($v['email']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Telefon</label>
            <input type="text" name="phone" class="form-control" maxlength="30" value="<?= e($v['phone']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Veterinarska stanica *</label>
            <select name="station" class="form-select" required>
                <option value="">Izaberite stanicu</option>
                <?php foreach ($stanice as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= (string) $s['id'] === $v['station'] ? 'selected' : '' ?>>
                        <?= e($s['name'] . ' (' . $s['city'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Broj licence</label>
            <input type="text" name="license" class="form-control" maxlength="100" value="<?= e($v['license']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Specijalizacija</label>
            <input type="text" name="spec" class="form-control" maxlength="150" value="<?= e($v['spec']) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Kreiraj veterinara</button>
    </form>
</div>

<?php
include __DIR__ . '/includes/admin_end.php';
include __DIR__ . '/includes/footer.php';
?>
