<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Pet;
use App\Appointment;

$user = Auth::requireRole(['OWNER']);

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$pol = ['MALE' => 'Muški', 'FEMALE' => 'Ženski', 'UNKNOWN' => 'Nepoznato'];
$ljubimci = Pet::forOwner($user['id']);
$termini  = Appointment::upcomingForOwner($user['id']);

$aktivna = 'pregled';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/owner_start.php';
?>

    <h2 class="naslov">Zdravo, <?= e($user['first_name']) ?>!</h2>
    <p class="text-muted">Dobro došli u vaš nalog.</p>

    <h5 class="naslov mt-4">Moji ljubimci</h5>
    <div class="row g-3 mb-4">
        <?php foreach ($ljubimci as $p): ?>
            <div class="col-sm-6 col-xl-4">
                <div class="pet-kartica">
                    <div class="pet-slika">
                        <?php if ($p['profile_photo']): ?>
                            <img src="<?= e($p['profile_photo']) ?>" alt="<?= e($p['name']) ?>">
                        <?php else: ?>
                            <i class="bi bi-camera"></i>
                        <?php endif; ?>
                    </div>
                    <h6 class="mt-2 mb-0"><?= e($p['name']) ?></h6>
                    <small class="text-muted">
                        <?= e($p['species']) ?> · <?= e($pol[$p['sex']] ?? '') ?>
                        <?= $p['breed'] ? ' · ' . e($p['breed']) : '' ?>
                    </small>
                    <a class="btn btn-primary btn-sm w-100 mt-2" href="e_karton.php?id=<?= (int) $p['id'] ?>">Pogledaj karton</a>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="col-sm-6 col-xl-4">
            <a href="ljubimac.php" class="pet-kartica dodaj-kartica text-decoration-none">
                <i class="bi bi-plus-square"></i>
                <span>Dodaj ljubimca</span>
            </a>
        </div>
    </div>

    <h5 class="naslov">Nadolazeći termini</h5>
<?php if (!$termini): ?>
    <p class="text-muted">Nemate zakazanih termina.</p>
<?php endif; ?>
<?php foreach ($termini as $t):
    $vreme = strtotime($t['start_time']); ?>
    <div class="termin">
        <div>
            <strong>Pregled: <?= e($t['pet_name']) ?></strong><br>
            <small>Dr <?= e($t['first_name'] . ' ' . $t['last_name']) ?> ·
                <?= date('d.m.Y.', $vreme) ?> u <?= date('H:i', $vreme) ?></small>
        </div>
        <a class="btn btn-primary btn-sm disabled" href="#">Detalji</a>
    </div>
<?php endforeach; ?>

<?php
include __DIR__ . '/includes/owner_end.php';
include __DIR__ . '/includes/footer.php';
?>