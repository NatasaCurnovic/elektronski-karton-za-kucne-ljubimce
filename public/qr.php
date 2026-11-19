<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\Pet;
use App\PetQr;

$user = Auth::requireRole(['OWNER']);

// Uključivanje / isključivanje javne stranice
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_POST['csrf'] ?? '';
    if ($tok === '' || !hash_equals($_SESSION['csrf'], $tok)) {
        http_response_code(400);
        die('Nevažeći zahtev.');
    }
    $petId = (int) ($_POST['pet'] ?? 0);
    if ($petId && PetQr::ownedBy($petId, $user['id'])) {
        PetQr::setActive($petId, ($_POST['akcija'] ?? '') === 'ukljuci');
    }
    header('Location: qr.php');
    exit;
}

$config = require __DIR__ . '/../config/config.php';
$pets   = Pet::forOwner($user['id']);

$aktivna = 'qr';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/owner_start.php';
?>

    <h2 class="naslov">QR kodovi</h2>
    <p class="text-muted">
        QR kod otvara javnu stranicu ljubimca sa osnovnim podacima i kontaktom veterinara.
        Podaci o vama se ne prikazuju. Štampajte ga i okačite na ogrlicu.
    </p>

<?php if (!$pets): ?>
    <p class="text-muted">Nemate dodatih ljubimaca.</p>
<?php endif; ?>

    <div class="row g-3">
        <?php foreach ($pets as $p): ?>
            <?php
            $id    = (int) $p['id'];
            $red   = PetQr::forPet($id);
            $uklj  = !$red || (int) $red['is_active'] === 1;
            $link  = $uklj ? $config['app']['base_url'] . '/ljubimac_javno.php?t=' . PetQr::tokenFor($id) : '';
            ?>
            <div class="col-md-6 col-xl-4">
                <div class="zapis text-center h-100">
                    <h5><?= e($p['name']) ?></h5>

                    <?php if ($uklj): ?>
                        <img src="qr_slika.php?pet=<?= $id ?>" alt="QR kod za <?= e($p['name']) ?>"
                             width="220" height="220" class="my-2">
                        <div class="small mb-2"><a href="<?= e($link) ?>" target="_blank"><?= e($link) ?></a></div>
                        <a href="qr_slika.php?pet=<?= $id ?>&download=1" class="btn btn-primary btn-sm">
                            <i class="bi bi-download"></i> Preuzmi PNG
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                            <i class="bi bi-printer"></i> Štampaj
                        </button>
                    <?php else: ?>
                        <p class="text-muted my-4">Javna stranica je isključena.</p>
                    <?php endif; ?>

                    <form method="post" class="mt-2">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                        <input type="hidden" name="pet" value="<?= $id ?>">
                        <input type="hidden" name="akcija" value="<?= $uklj ? 'iskljuci' : 'ukljuci' ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <?= $uklj ? 'Isključi javnu stranicu' : 'Uključi javnu stranicu' ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php
include __DIR__ . '/includes/owner_end.php';
include __DIR__ . '/includes/footer.php';
?>