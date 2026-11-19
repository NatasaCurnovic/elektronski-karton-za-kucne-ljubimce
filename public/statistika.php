<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\Admin;

Auth::requireRole(['ADMIN']);
$br   = Admin::counts();
$stat = Admin::vetStatistics();

$najvise = 1;
foreach ($stat as $s) {
    $najvise = max($najvise, (int) $s['assigned_pets'], (int) $s['completed_examinations']);
}

$aktivna = 'statistika';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/admin_start.php';
?>

<h2 class="naslov">Statistika</h2>

<div class="row g-3 my-2">
    <div class="col-sm-6 col-xl-3">
        <div class="statistika"><span class="broj"><?= (int) $br['owners'] ?></span><span>Vlasnika</span></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="statistika"><span class="broj"><?= (int) $br['vets'] ?></span><span>Veterinara</span></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="statistika"><span class="broj"><?= (int) $br['pets'] ?></span><span>Aktivnih ljubimaca</span></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="statistika"><span class="broj"><?= (int) $br['upcoming'] ?></span><span>Nadolazećih termina</span></div>
    </div>
</div>

<h5 class="naslov mt-4">Po veterinarima</h5>
<?php if (!$stat): ?>
    <p class="text-muted">Nema podataka.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table align-middle bg-white">
        <thead>
            <tr><th>Veterinar</th><th style="width: 30%;">Dodeljeni ljubimci</th><th style="width: 30%;">Obavljeni pregledi</th></tr>
        </thead>
        <tbody>
        <?php foreach ($stat as $s): ?>
            <tr>
                <td><?= e($s['veterinarian_name']) ?></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height: 10px;">
                            <div class="progress-bar" style="width: <?= round($s['assigned_pets'] / $najvise * 100) ?>%"></div>
                        </div>
                        <strong><?= (int) $s['assigned_pets'] ?></strong>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height: 10px;">
                            <div class="progress-bar bg-success" style="width: <?= round($s['completed_examinations'] / $najvise * 100) ?>%"></div>
                        </div>
                        <strong><?= (int) $s['completed_examinations'] ?></strong>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php
include __DIR__ . '/includes/admin_end.php';
include __DIR__ . '/includes/footer.php';
?>
