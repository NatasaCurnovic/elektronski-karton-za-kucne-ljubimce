<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Pet;

$user = Auth::requireRole(['OWNER']);

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Uklanjanje (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_SESSION['csrf'] ?? '';
    if ($tok === '' || !hash_equals($tok, $_POST['csrf'] ?? '')) {
        die('Neispravan zahtev.');
    }
    $id  = (int) ($_POST['id'] ?? 0);
    $pet = Pet::findForOwner($id, $user['id']);

    if (!$pet) {
        $_SESSION['flash'] = ['danger', 'Ljubimac nije pronađen.'];
    } elseif (Pet::hasUpcomingAppointments($id)) {
        $_SESSION['flash'] = ['warning', 'Ljubimac ima zakazan termin. Otkažite ga, pa onda uklonite ljubimca.'];
    } else {
        Pet::deactivate($id, $user['id']);
        $_SESSION['flash'] = ['success', 'Ljubimac "' . $pet['name'] . '" je uklonjen. Istorija pregleda je sačuvana.'];
    }
    header('Location: ljubimci.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pol      = ['MALE' => 'Muški', 'FEMALE' => 'Ženski', 'UNKNOWN' => 'Nepoznato'];
$ljubimci = Pet::forOwner($user['id']);

$aktivna = 'ljubimci';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/owner_start.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="naslov mb-0">Moji ljubimci</h2>
        <a href="ljubimac.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Dodaj ljubimca</a>
    </div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div>
<?php endif; ?>

<?php if (!$ljubimci): ?>
    <p class="text-muted">Još nemate nijednog ljubimca.</p>
<?php endif; ?>

    <div class="row g-3">
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
                    <div class="d-flex gap-2 mt-2">
                        <a href="ljubimac.php?id=<?= (int) $p['id'] ?>" class="btn btn-primary btn-sm flex-grow-1">Uredi</a>
                        <form method="post" action="ljubimci.php" class="m-0"
                              onsubmit="return confirm('Ukloniti ljubimca? Istorija pregleda ostaje sačuvana.');">
                            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button type="submit" class="btn btn-outline-primary btn-sm">Ukloni</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php
include __DIR__ . '/includes/owner_end.php';
include __DIR__ . '/includes/footer.php';
?>