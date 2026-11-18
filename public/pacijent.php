<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\Pet;
use App\MedicalRecord;

$user = Auth::requireRole(['VETERINARIAN']);
$id   = (int) ($_GET['id'] ?? 0);
$pet  = $id ? Pet::findForVet($id, $user['id']) : null; // samo svoj pacijent

if (!$pet) {
    http_response_code(404);
    die('Pacijent nije pronađen.');
}

$pregledi = MedicalRecord::examinations($id);
$tretmani = MedicalRecord::treatments($id);

$aktivna = 'pacijenti';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/vet_start.php';
?>

    <a href="pacijenti.php" class="small"><i class="bi bi-arrow-left"></i> Nazad na pacijente</a>

    <div class="karton-zaglavlje mt-2">
        <div class="pet-slika karton-slika">
            <?php if ($pet['profile_photo']): ?>
                <img src="<?= e($pet['profile_photo']) ?>" alt="<?= e($pet['name']) ?>">
            <?php else: ?>
                <i class="bi bi-camera"></i>
            <?php endif; ?>
        </div>
        <div>
            <h4 class="mb-1"><?= e($pet['name']) ?></h4>
            <div class="text-muted">
                <?= e($pet['species']) ?> · <?= e(polNaziv($pet['sex'])) ?>
                <?= $pet['breed'] ? ' · ' . e($pet['breed']) : '' ?>
            </div>
            <div class="small mt-1">
                Datum rođenja: <?= $pet['date_of_birth'] ? datum($pet['date_of_birth']) : 'nije uneto' ?><br>
                Mikročip: <?= $pet['microchip_number'] ? e($pet['microchip_number']) : 'nije unet' ?>
            </div>
        </div>
    </div>

    <div class="zapis mt-3">
        <strong>Vlasnik:</strong> <?= e($pet['owner_first'] . ' ' . $pet['owner_last']) ?><br>
        <span class="small">
        E-mail: <?= e($pet['owner_email']) ?>
        · Telefon: <?= $pet['owner_phone'] ? e($pet['owner_phone']) : 'nije unet' ?>
            <?= $pet['owner_address'] ? '· Adresa: ' . e($pet['owner_address']) : '' ?>
    </span>
    </div>

    <ul class="nav nav-tabs mt-4" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPregledi" type="button">
                Pregledi (<?= count($pregledi) ?>)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabTretmani" type="button">
                Tretmani (<?= count($tretmani) ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content pt-3">
        <div class="tab-pane fade show active" id="tabPregledi">
            <?php if (!$pregledi): ?>
                <p class="text-muted">Još nema evidentiranih pregleda.</p>
            <?php endif; ?>
            <?php foreach ($pregledi as $r): ?>
                <div class="zapis">
                    <div class="d-flex justify-content-between flex-wrap">
                        <strong><?= e($r['description']) ?></strong>
                        <span class="text-muted small"><?= datum($r['examination_date'], true) ?></span>
                    </div>
                    <div class="small text-muted mb-1">Dr <?= e($r['first_name'] . ' ' . $r['last_name']) ?></div>
                    <?php if ($r['findings']): ?>
                        <div><strong>Nalaz:</strong> <?= nl2br(e($r['findings'])) ?></div>
                    <?php endif; ?>
                    <?php if ($r['notes']): ?>
                        <div><strong>Napomena:</strong> <?= nl2br(e($r['notes'])) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="tab-pane fade" id="tabTretmani">
            <?php if (!$tretmani): ?>
                <p class="text-muted">Još nema evidentiranih tretmana.</p>
            <?php endif; ?>
            <?php foreach ($tretmani as $r): ?>
                <div class="zapis">
                    <div class="d-flex justify-content-between flex-wrap">
                        <strong><?= e($r['title']) ?></strong>
                        <span class="text-muted small"><?= datum($r['treatment_date']) ?></span>
                    </div>
                    <div class="small text-muted mb-1">Dr <?= e($r['first_name'] . ' ' . $r['last_name']) ?></div>
                    <div><?= nl2br(e($r['description'])) ?></div>
                    <?php if ($r['notes']): ?>
                        <div><strong>Napomena:</strong> <?= nl2br(e($r['notes'])) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php
include __DIR__ . '/includes/vet_end.php';
include __DIR__ . '/includes/footer.php';
?>