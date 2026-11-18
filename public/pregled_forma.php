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
    $zapis = MedicalRecord::findExamination($id, $user['id']);
    if (!$zapis || (int) $zapis['pet_id'] !== $petId) {
        http_response_code(404);
        die('Zapis nije pronađen.');
    }
}

$greske = [];
$vrednosti = [
    'date'        => $zapis ? date('Y-m-d\TH:i', strtotime($zapis['examination_date'])) : date('Y-m-d\TH:i'),
    'description' => $zapis['description'] ?? '',
    'findings'    => $zapis['findings'] ?? '',
    'notes'       => $zapis['notes'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_POST['csrf'] ?? '';
    if ($tok === '' || !hash_equals($_SESSION['csrf'], $tok)) {
        http_response_code(400);
        die('Nevažeći zahtev.');
    }

    foreach (['date', 'description', 'findings', 'notes'] as $k) {
        $vrednosti[$k] = trim($_POST[$k] ?? '');
    }

    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $vrednosti['date']);
    if (!$dt) {
        $greske[] = 'Unesite ispravan datum i vreme pregleda.';
    } elseif ($dt > new DateTime()) {
        $greske[] = 'Pregled ne može biti u budućnosti.';
    }
    if ($vrednosti['description'] === '') {
        $greske[] = 'Opis pregleda je obavezan.';
    } elseif (mb_strlen($vrednosti['description']) > 255) {
        $greske[] = 'Opis može imati najviše 255 karaktera.';
    }
    if (mb_strlen($vrednosti['findings']) > 2000 || mb_strlen($vrednosti['notes']) > 2000) {
        $greske[] = 'Nalaz i napomena mogu imati najviše 2000 karaktera.';
    }

    if (!$greske) {
        $podaci = [
            'date'        => $dt->format('Y-m-d H:i:s'),
            'description' => $vrednosti['description'],
            'findings'    => $vrednosti['findings'] !== '' ? $vrednosti['findings'] : null,
            'notes'       => $vrednosti['notes'] !== '' ? $vrednosti['notes'] : null,
        ];
        if ($zapis) {
            MedicalRecord::updateExamination($id, $user['id'], $podaci);
        } else {
            MedicalRecord::createExamination($petId, $user['id'], $podaci);
        }
        header('Location: pacijent.php?id=' . $petId . '&ok=1');
        exit;
    }
}

$aktivna = 'pacijenti';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/vet_start.php';
?>

    <a href="pacijent.php?id=<?= $petId ?>" class="small"><i class="bi bi-arrow-left"></i> Nazad na karton</a>
    <h2 class="naslov mt-2"><?= $zapis ? 'Izmena pregleda' : 'Novi pregled' ?>: <?= e($pet['name']) ?></h2>

<?php foreach ($greske as $g): ?>
    <div class="alert alert-danger py-2"><?= e($g) ?></div>
<?php endforeach; ?>

    <form method="post" style="max-width: 640px;">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

        <div class="mb-3">
            <label class="form-label">Datum i vreme pregleda *</label>
            <input type="datetime-local" name="date" class="form-control" value="<?= e($vrednosti['date']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Opis pregleda *</label>
            <input type="text" name="description" class="form-control" maxlength="255"
                   value="<?= e($vrednosti['description']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nalaz</label>
            <textarea name="findings" class="form-control" rows="4" maxlength="2000"><?= e($vrednosti['findings']) ?></textarea>
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