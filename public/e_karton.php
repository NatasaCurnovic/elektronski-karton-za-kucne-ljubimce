<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Pet;
use App\Veterinarian;
use App\MedicalRecord;

$user = Auth::requireRole(['OWNER']);

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function datum(?string $s, bool $vreme = false): string
{
    if (!$s) {
        return '';
    }
    $t = strtotime($s);
    return date('d.m.Y.', $t) . ($vreme ? ' u ' . date('H:i', $t) : '');
}

$pol      = ['MALE' => 'Muški', 'FEMALE' => 'Ženski', 'UNKNOWN' => 'Nepoznato'];
$ljubimci = Pet::forOwner($user['id']);
$id       = (int) ($_GET['id'] ?? 0);

// Ako vlasnik ima samo jednog ljubimca, odmah ga otvori
if (!$id && count($ljubimci) === 1) {
    header('Location: e_karton.php?id=' . (int) $ljubimci[0]['id']);
    exit;
}

$pet = null;
if ($id) {
    $pet = Pet::findForOwner($id, $user['id']); // samo svoj ljubimac
    if (!$pet) {
        http_response_code(404);
        die('Ljubimac nije pronađen.');
    }
    $vet        = Veterinarian::find((int) $pet['veterinarian_id']);
    $pregledi   = MedicalRecord::examinations($id);
    $tretmani   = MedicalRecord::treatments($id);
}

$aktivna = 'karton';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/owner_start.php';
?>

    <h2 class="naslov">E-karton</h2>

<?php if ($ljubimci && count($ljubimci) > 1): ?>
    <div class="mb-3 d-flex flex-wrap gap-2">
        <?php foreach ($ljubimci as $p): ?>
            <a href="e_karton.php?id=<?= (int) $p['id'] ?>"
               class="btn btn-sm <?= (int) $p['id'] === $id ? 'btn-primary' : 'btn-outline-primary' ?>">
                <?= e($p['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!$ljubimci): ?>
    <p class="text-muted">Nemate ljubimaca. <a href="ljubimac.php">Dodajte ljubimca</a>.</p>

<?php elseif (!$pet): ?>
    <p>Izaberite ljubimca čiji karton želite da vidite.</p>

<?php else: ?>

    <div class="karton-zaglavlje">
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
                <?= e($pet['species']) ?> · <?= e($pol[$pet['sex']] ?? '') ?>
                <?= $pet['breed'] ? ' · ' . e($pet['breed']) : '' ?>
            </div>
            <div class="small mt-1">
                Datum rođenja: <?= $pet['date_of_birth'] ? datum($pet['date_of_birth']) : 'nije uneto' ?><br>
                Mikročip: <?= $pet['microchip_number'] ? e($pet['microchip_number']) : 'nije unet' ?><br>
                <?php if ($vet): ?>
                    Veterinar: Dr <?= e($vet['first_name'] . ' ' . $vet['last_name']) ?>,
                    <?= e($vet['station']) ?>, <?= e($vet['city']) ?>
                <?php endif; ?>
            </div>
        </div>
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

<?php endif; ?>

<?php
include __DIR__ . '/includes/owner_end.php';
include __DIR__ . '/includes/footer.php';
?>