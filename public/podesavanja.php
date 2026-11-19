<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\NotificationPreference;

$user    = Auth::requireRole(['OWNER']);
$dozvoljeno = [1, 2, 6, 12, 24, 48, 72];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_POST['csrf'] ?? '';
    if ($tok === '' || !hash_equals($_SESSION['csrf'], $tok)) {
        http_response_code(400);
        die('Nevažeći zahtev.');
    }
    $sati = (int) ($_POST['sati'] ?? 24);
    if (!in_array($sati, $dozvoljeno, true)) {
        $sati = 24;
    }
    NotificationPreference::save($user['id'], isset($_POST['podsetnici']), $sati);
    header('Location: podesavanja.php?ok=1');
    exit;
}

$pod = NotificationPreference::forUser($user['id']);

$aktivna = 'podesavanja';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/owner_start.php';
?>

    <h2 class="naslov">Podešavanja</h2>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success py-2">Podešavanja su sačuvana.</div>
<?php endif; ?>

    <div class="zapis" style="max-width: 560px;">
        <h5 class="mb-3">Podsetnici o terminima</h5>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="podsetnici" id="podsetnici"
                    <?= $pod['appointment_reminders'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="podsetnici">
                    Šalji mi e-mail podsetnik pre termina
                </label>
            </div>

            <div class="mb-3">
                <label class="form-label">Podseti me unapred</label>
                <select name="sati" class="form-select">
                    <?php foreach ($dozvoljeno as $h): ?>
                        <option value="<?= $h ?>" <?= (int) $pod['appointment_reminder_hours'] === $h ? 'selected' : '' ?>>
                            <?= $h ?> <?= $h === 1 ? 'sat' : 'sata' ?> pre termina
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Sačuvaj</button>
        </form>
    </div>

<?php
include __DIR__ . '/includes/owner_end.php';
include __DIR__ . '/includes/footer.php';
?>