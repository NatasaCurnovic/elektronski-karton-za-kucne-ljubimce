<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\Pet;
use App\Appointment;

$user      = Auth::requireRole(['VETERINARIAN']);
$pacijenti = Pet::forVet($user['id']);
$termini   = Appointment::upcomingForVet($user['id']);

$aktivna = 'pregled';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/vet_start.php';
?>

    <h2 class="naslov">Dobro došli, dr <?= e($user['first_name']) ?></h2>

    <div class="row g-3 my-3">
        <div class="col-sm-6">
            <div class="statistika">
                <span class="broj"><?= count($pacijenti) ?></span>
                <span>Dodeljenih pacijenata</span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="statistika">
                <span class="broj"><?= count($termini) ?></span>
                <span>Nadolazećih termina</span>
            </div>
        </div>
    </div>

    <h5 class="naslov">Nadolazeći termini</h5>
<?php if (!$termini): ?>
    <p class="text-muted">Nema zakazanih termina.</p>
<?php endif; ?>
<?php foreach ($termini as $t): ?>
    <div class="termin">
        <div>
            <strong><?= e($t['pet_name']) ?></strong><br>
            <small>Vlasnik: <?= e($t['first_name'] . ' ' . $t['last_name']) ?> ·
                <?= datum($t['start_time'], true) ?></small>
        </div>
    </div>
<?php endforeach; ?>

<?php
include __DIR__ . '/includes/vet_end.php';
include __DIR__ . '/includes/footer.php';
?>