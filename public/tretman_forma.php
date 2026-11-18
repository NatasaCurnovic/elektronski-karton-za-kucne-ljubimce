<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\Pet;
use App\MedicalRecord;

$user  = Auth::requireRole(['VETERINARIAN']);
$petId = (int) ($_GET['pet'] ?? 0);
$pet   = $petId ? Pet::findForVet($petId, $user['id']) : null;
if (!$pet) {
    http_response_code(404);
    die('Pacijent nije pronađen.');
}

$id    = (int) ($_GET['id'] ?? 0);
$zapis = null;
if ($id) {
    $zapis = MedicalRecord::findTreatment($id, $user['id']);
    if (!$zapis || (int) $zapis['pet_id'] !== $petId) {
        http_response_code(404);
        die('Zapis nije pronađen.');
    }
}

$greske = [];
$vrednosti = [
    'date'        => $zapis['treatment_date'] ?? date('Y-m-d'),
    'title'       => $zapis['title'] ?? '',
    'description' => $zapis['description'] ?? '',
    'notes'       => $zapis['notes'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_POST['csrf'] ?? '';
    if ($tok === '' || !hash_equals($_SESSION['csrf'], $tok)) {
        http_response_code(400);
        die('Nevažeći zahtev.');
    }

    foreach (['date', 'title', 'description', 'notes'] as $k) {
        $vrednosti[$k] = trim($_POST[$k] ?? '');
    }

    $dt = DateTime::createFromFormat('Y-m-d', $vrednosti['date']);
    if (!$dt || $dt->format('Y-m-d') !== $vrednosti['date']) {
        $greske[] = 'Unesite ispravan datum tretmana.';
    } elseif ($dt > new DateTime()) {
        $greske[] = 'Tretman ne može biti u budućnosti.';
    }
    if ($vrednosti['title'] === '') {
        $greske[] = 'Naziv tretmana je obavezan.';
    } elseif (mb_strlen($vrednosti['title']) > 150) {
        $greske[] = 'Naziv može imati najviše 150 karaktera.';
    }
    if ($vrednosti['description'] === '') {
        $greske[] = 'Opis tretmana je obavezan.';
    } elseif (mb_strlen($vrednosti['description']) > 2000) {
        $greske[] = 'Opis može imati najviše 2000 karaktera.';
    }
    if (mb_strlen($vrednosti['notes']) > 2000) {
        $greske[] = 'Napomena može imati najviše 2000 karaktera.';
    }

    if (!$greske) {
        $podaci = [
            'date'        => $vrednosti['date'],
            'title'       => $vrednosti['title'],
            'description' => $vrednosti['description'],
            'notes'       => $vrednosti['notes'] !== '' ? $vrednosti['notes'] : null,
        ];
        if ($zapis) {
            MedicalRecord::updateTreatment($id, $user['id'], $podaci);
        } else {
            MedicalRecord::createTreatment($petId, $user['id'], $podaci);
        }
        header('Location: pacijent.php?id=' . $petId . '&ok=1#tretmani');
        exit;
    }
}

$aktivna = 'pacijenti';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/vet_start.php';
?>

    <a href="pacijent.php?id=<?= $petId ?>" class="small"><i class="bi bi-arrow-left"></i> Nazad na karton</a>
    <h2 class="naslov mt-2"><?= $zapis ? 'Izmena tretmana' : 'Novi tretman' ?>: <?= e($pet['name']) ?></h2>

<?php foreach ($greske as $g): ?>
    <div class="alert alert-danger py-2"><?= e($g) ?></div>
<?php endforeach; ?>

    <form method="post" style="max-width: 640px;">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

        <div class="mb-3">
            <label class="form-label">Datum tretmana *</label>
            <input type="date" name="date" class="form-control" value="<?= e($vrednosti['date']) ?>"
                   max="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Naziv tretmana *</label>
            <input type="text" name="title" class="form-control" maxlength="150"
                   value="<?= e($vrednosti['title']) ?>" placeholder="npr. Vakcinacija protiv besnila" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Opis *</label>
            <textarea name="description" class="form-control" rows="4" maxlength="2000" required><?= e($vrednosti['description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Napomena</label>
            <textarea name="notes" class="form-control" rows="3" maxlength="2000"><?= e($vrednosti['notes']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Sačuvaj</button>
        <a href="pacijent.php?id=<?= $petId ?>" class="btn btn-outline-secondary">Otkaži</a>
    </form>

<?php
include __DIR__ . '/includes/vet_end.php';
include __DIR__ . '/includes/footer.php';
?>