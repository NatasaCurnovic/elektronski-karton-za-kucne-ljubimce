<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\PetQr;

header('X-Robots-Tag: noindex'); // da pretraživači ne indeksiraju ove stranice

$token = $_GET['t'] ?? '';
$pet   = preg_match('/^[a-f0-9]{64}$/', $token) ? PetQr::publicPet($token) : null;

if (!$pet) {
    http_response_code(404);
}

include __DIR__ . '/includes/header.php';
?>

    <div class="container py-5" style="max-width: 560px;">
        <?php if (!$pet): ?>
            <div class="alert alert-warning">Ova stranica nije dostupna.</div>
        <?php else: ?>
            <div class="zapis text-center">
                <div class="pet-slika mx-auto mb-3" style="width: 180px; height: 180px;">
                    <?php if ($pet['profile_photo']): ?>
                        <img src="<?= e($pet['profile_photo']) ?>" alt="<?= e($pet['name']) ?>">
                    <?php else: ?>
                        <i class="bi bi-camera"></i>
                    <?php endif; ?>
                </div>

                <h2 class="naslov"><?= e($pet['name']) ?></h2>
                <p class="text-muted mb-1">
                    <?= e($pet['species']) ?><?= $pet['breed'] ? ' · ' . e($pet['breed']) : '' ?> · <?= e(polNaziv($pet['sex'])) ?>
                </p>
                <?php if ($pet['microchip_number']): ?>
                    <p class="small">Mikročip: <?= e($pet['microchip_number']) ?></p>
                <?php endif; ?>

                <hr>
                <p class="mb-1">Ako ste pronašli ovog ljubimca, javite se njegovom veterinaru:</p>
                <p class="mb-0">
                    <strong>dr <?= e($pet['vet_first'] . ' ' . $pet['vet_last']) ?></strong><br>
                    <a href="mailto:<?= e($pet['vet_email']) ?>"><?= e($pet['vet_email']) ?></a>
                </p>
            </div>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>