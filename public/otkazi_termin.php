<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\Appointment;
use App\Mailer;
use App\Notification;

$user = Auth::requireRole(['VETERINARIAN']);
$id   = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$t    = $id ? Appointment::findForVet($id, $user['id']) : null;

if (!$t) {
    http_response_code(404);
    die('Termin nije pronađen.');
}

$greska = '';
$razlog = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_POST['csrf'] ?? '';
    if ($tok === '' || !hash_equals($_SESSION['csrf'], $tok)) {
        http_response_code(400);
        die('Nevažeći zahtev.');
    }

    $razlog = trim($_POST['razlog'] ?? '');
    if (mb_strlen($razlog) < 5) {
        $greska = 'Unesite razlog otkazivanja (najmanje 5 karaktera).';
    } elseif (mb_strlen($razlog) > 500) {
        $greska = 'Razlog može imati najviše 500 karaktera.';
    } elseif (Appointment::cancelByVet($id, $user['id'], $razlog)) {

        // E-mail vlasniku + evidencija u notifications
        $config  = require __DIR__ . '/../config/config.php';
        $url     = $config['app']['base_url'] . '/zakazivanje.php';
        $subject = 'Vaš termin je otkazan';
        $html = '<p>Poštovani/a ' . e($t['owner_first']) . ',</p>'
            . '<p>Nažalost, veterinar dr ' . e($user['first_name'] . ' ' . $user['last_name'])
            . ' je otkazao termin za ljubimca <strong>' . e($t['pet_name']) . '</strong>'
            . ' zakazan za <strong>' . datum($t['start_time'], true) . '</strong>.</p>'
            . '<p><strong>Razlog:</strong> ' . nl2br(e($razlog)) . '</p>'
            . '<p>Možete zakazati novi termin ovde: <a href="' . e($url) . '">' . e($url) . '</a></p>';

        $poslato = Mailer::send($t['owner_email'], $t['owner_first'] . ' ' . $t['owner_last'], $subject, $html);
        Notification::log((int) $t['owner_id'], 'APPOINTMENT_CANCELLED', $subject, $poslato ? 'SENT' : 'FAILED', $id);

        header('Location: termini.php?otkazan=' . ($poslato ? '1' : '2'));
        exit;
    } else {
        $greska = 'Otkazivanje nije uspelo.';
    }
}

$aktivna = 'termini';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/vet_start.php';
?>

    <a href="termini.php" class="small"><i class="bi bi-arrow-left"></i> Nazad na termine</a>
    <h2 class="naslov mt-2">Otkazivanje termina</h2>

    <div class="zapis mb-3">
        <strong><?= e($t['pet_name']) ?></strong><br>
        <span class="small">
        Vlasnik: <?= e($t['owner_first'] . ' ' . $t['owner_last']) ?> ·
        <?= datum($t['start_time'], true) ?>
    </span>
    </div>

<?php if ($greska): ?>
    <div class="alert alert-danger py-2"><?= e($greska) ?></div>
<?php endif; ?>

    <form method="post" style="max-width: 640px;">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="mb-3">
            <label class="form-label">Razlog otkazivanja * (vlasnik će ga dobiti e-mailom)</label>
            <textarea name="razlog" class="form-control" rows="4" maxlength="500" required><?= e($razlog) ?></textarea>
        </div>

        <button type="submit" class="btn btn-danger">Otkaži termin</button>
        <a href="termini.php" class="btn btn-outline-secondary">Odustani</a>
    </form>

<?php
include __DIR__ . '/includes/vet_end.php';
include __DIR__ . '/includes/footer.php';
?>