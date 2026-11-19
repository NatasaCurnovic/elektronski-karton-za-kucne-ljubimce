<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\Pet;
use App\Appointment;

$user = Auth::requireRole(['OWNER']);
$pets = Pet::forOwner($user['id']);

$petId = (int) ($_POST['pet'] ?? $_GET['pet'] ?? ($pets[0]['id'] ?? 0));

// Zakazivanje
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_POST['csrf'] ?? '';
    if ($tok === '' || !hash_equals($_SESSION['csrf'], $tok)) {
        http_response_code(400);
        die('Nevažeći zahtev.');
    }
    $rez = Appointment::book($user['id'], $petId, (int) ($_POST['slot_id'] ?? 0));
    header('Location: zakazivanje.php?pet=' . $petId . '&rez=' . $rez);
    exit;
}

$info  = $petId ? Appointment::vetForPet($petId, $user['id']) : null;
$slots = $info ? Appointment::availableSlots((int) $info['veterinarian_id']) : [];

// Grupisanje termina po datumu
$poDanima = [];
foreach ($slots as $s) {
    $poDanima[date('Y-m-d', strtotime($s['start_time']))][] = $s;
}

$aktivna = 'zakazivanje';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/owner_start.php';
?>

    <h2 class="naslov">Zakazivanje termina</h2>

<?php $rez = $_GET['rez'] ?? ''; ?>
<?php if ($rez === 'ok'): ?>
    <div class="alert alert-success py-2">
        Termin je uspešno zakazan.
        <a href="moji_termini.php">Dodajte ga u Google Calendar</a>.
    </div>
<?php elseif ($rez === 'taken'): ?>
    <div class="alert alert-danger py-2">Taj termin je u međuvremenu zauzet. Izaberite drugi.</div>
<?php elseif ($rez === 'invalid'): ?>
    <div class="alert alert-danger py-2">Zakazivanje nije uspelo.</div>
<?php endif; ?>

<?php if (!$pets): ?>
    <p class="text-muted">Prvo dodajte ljubimca da biste zakazali termin.</p>
<?php elseif (!$info): ?>
    <p class="text-muted">Ljubimac nije pronađen.</p>
<?php else: ?>

    <?php if (count($pets) > 1): ?>
        <ul class="nav nav-pills mb-3">
            <?php foreach ($pets as $p): ?>
                <li class="nav-item">
                    <a class="nav-link <?= (int) $p['id'] === $petId ? 'active' : '' ?>"
                       href="zakazivanje.php?pet=<?= (int) $p['id'] ?>"><?= e($p['name']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p>Veterinar: <strong>dr <?= e($info['first_name'] . ' ' . $info['last_name']) ?></strong></p>

    <?php if (!$poDanima): ?>
        <p class="text-muted">Veterinar trenutno nema slobodnih termina.</p>
    <?php endif; ?>

    <?php foreach ($poDanima as $dan => $lista): ?>
        <h6 class="mt-3"><?= datum($dan) ?></h6>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($lista as $s): ?>
                <form method="post" class="m-0">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                    <input type="hidden" name="pet" value="<?= $petId ?>">
                    <input type="hidden" name="slot_id" value="<?= (int) $s['id'] ?>">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <?= date('H:i', strtotime($s['start_time'])) ?> – <?= date('H:i', strtotime($s['end_time'])) ?>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

<?php
include __DIR__ . '/includes/owner_end.php';
include __DIR__ . '/includes/footer.php';
?>