<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\Admin;

$user = Auth::requireRole(['ADMIN']);
$br   = Admin::counts();

$aktivna = 'pregled';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/admin_start.php';
?>

<h2 class="naslov">Administracija</h2>
<p class="text-muted">Dobro došli, <?= e($user['first_name']) ?>.</p>

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

<?php
include __DIR__ . '/includes/admin_end.php';
include __DIR__ . '/includes/footer.php';
?>
