<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\Appointment;

$user = Auth::requireRole(['OWNER']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_POST['csrf'] ?? '';
    if ($tok === '' || !hash_equals($_SESSION['csrf'], $tok)) {
        http_response_code(400);
        die('Nevažeći zahtev.');
    }
    $rez = Appointment::cancelByOwner((int) ($_POST['appointment_id'] ?? 0), $user['id']);
    header('Location: moji_termini.php?rez=' . $rez);
    exit;
}

$svi = Appointment::forOwner($user['id']);
$nadolazeci = [];
$istorija   = [];
foreach ($svi as $t) {
    if ($t['status'] === 'SCHEDULED' && strtotime($t['start_time']) > time()) {
        $nadolazeci[] = $t;
    } else {
        $istorija[] = $t;
    }
}
$nadolazeci = array_reverse($nadolazeci); // najbliži prvi

$nazivStatusa = [
        'SCHEDULED'                 => 'Zakazan',
        'COMPLETED'                 => 'Završen',
        'CANCELLED_BY_OWNER'        => 'Otkazali ste',
        'CANCELLED_BY_VETERINARIAN' => 'Otkazao veterinar',
];

$aktivna = 'termini';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/owner_start.php';
?>

    <h2 class="naslov">Moji termini</h2>

<?php $rez = $_GET['rez'] ?? ''; ?>
<?php if ($rez === 'ok'): ?>
    <div class="alert alert-success py-2">Termin je otkazan.</div>
<?php elseif ($rez === 'too_late'): ?>
    <div class="alert alert-danger py-2">Termin se može otkazati najkasnije 1 sat pre početka.</div>
<?php elseif ($rez === 'invalid'): ?>
    <div class="alert alert-danger py-2">Otkazivanje nije uspelo.</div>
<?php endif; ?>

    <h5 class="naslov mt-3">Nadolazeći termini</h5>
<?php if (!$nadolazeci): ?>
    <p class="text-muted">Nemate zakazanih termina. <a href="zakazivanje.php">Zakažite termin</a>.</p>
<?php endif; ?>
<?php foreach ($nadolazeci as $t): ?>
    <div class="termin">
        <div>
            <strong><?= e($t['pet_name']) ?></strong><br>
            <small>dr <?= e($t['first_name'] . ' ' . $t['last_name']) ?> ·
                <?= datum($t['start_time'], true) ?></small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="<?= e(gcal_link(
                    'Veterinar: ' . $t['pet_name'],
                    $t['start_time'],
                    $t['end_time'],
                    'Pregled ljubimca ' . $t['pet_name'] . ' kod dr ' . $t['first_name'] . ' ' . $t['last_name']
            )) ?>" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-calendar-plus"></i> Google Calendar
            </a>
            <?php if (strtotime($t['start_time']) - time() >= 3600): ?>
                <form method="post" class="m-0"
                      onsubmit="return confirm('Da li ste sigurni da želite da otkažete termin?');">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                    <input type="hidden" name="appointment_id" value="<?= (int) $t['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">Otkaži</button>
                </form>
            <?php else: ?>
                <small class="text-muted">Otkazivanje više nije moguće</small>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

    <h5 class="naslov mt-4">Istorija</h5>
<?php if (!$istorija): ?>
    <p class="text-muted">Nema prethodnih termina.</p>
<?php endif; ?>
<?php foreach ($istorija as $t): ?>
    <div class="termin">
        <div>
            <strong><?= e($t['pet_name']) ?></strong><br>
            <small>dr <?= e($t['first_name'] . ' ' . $t['last_name']) ?> ·
                <?= datum($t['start_time'], true) ?></small>
            <?php if ($t['cancellation_reason']): ?>
                <br><small>Razlog otkazivanja: <?= e($t['cancellation_reason']) ?></small>
            <?php endif; ?>
        </div>
        <div>
            <span class="badge text-bg-secondary">
                <?= e($nazivStatusa[$t['status']] ?? 'Otkazan') ?>
            </span>
        </div>
    </div>
<?php endforeach; ?>

<?php
include __DIR__ . '/includes/owner_end.php';
include __DIR__ . '/includes/footer.php';
?>