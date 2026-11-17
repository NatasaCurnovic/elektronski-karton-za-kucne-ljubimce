<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Pet;
use App\Veterinarian;
use App\ImageUpload;

$user = Auth::requireRole(['OWNER']);

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$id  = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$pet = null;
if ($id) {
    $pet = Pet::findForOwner($id, $user['id']);
    if (!$pet) {
        http_response_code(404);
        die('Ljubimac nije pronađen.');
    }
}

$old = [
    'name'            => $pet['name'] ?? '',
    'species'         => $pet['species'] ?? '',
    'breed'           => $pet['breed'] ?? '',
    'sex'             => $pet['sex'] ?? '',
    'birth_date'      => $pet['date_of_birth'] ?? '',
    'microchip'       => $pet['microchip_number'] ?? '',
    'veterinarian_id' => $pet['veterinarian_id'] ?? '',
];
$errors = [];
$vets   = Veterinarian::all();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_SESSION['csrf'] ?? '';
    if ($tok === '' || !hash_equals($tok, $_POST['csrf'] ?? '')) {
        die('Neispravan zahtev.');
    }
    foreach ($old as $k => $v) {
        $old[$k] = trim($_POST[$k] ?? '');
    }

    if (mb_strlen($old['name']) < 1 || mb_strlen($old['name']) > 100) {
        $errors['name'] = 'Unesite ime ljubimca (do 100 karaktera).';
    }
    if (!in_array($old['species'], ['Pas', 'Mačka', 'Zec', 'Ostalo'], true)) {
        $errors['species'] = 'Izaberite vrstu ljubimca.';
    }
    if (mb_strlen($old['breed']) > 100) {
        $errors['breed'] = 'Rasa je predugačka.';
    }
    if (!in_array($old['sex'], ['MALE', 'FEMALE', 'UNKNOWN'], true)) {
        $errors['sex'] = 'Izaberite pol.';
    }
    if ($old['birth_date'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $old['birth_date']);
        if (!$d || $d->format('Y-m-d') !== $old['birth_date'] || $d > new DateTime('today')) {
            $errors['birth_date'] = 'Unesite ispravan datum rođenja.';
        }
    }
    if ($old['microchip'] !== '') {
        if (!preg_match('/^[0-9]{15}$/', $old['microchip'])) {
            $errors['microchip'] = 'Broj mikročipa ima 15 cifara.';
        } elseif (Pet::microchipTaken($old['microchip'], $id)) {
            $errors['microchip'] = 'Ovaj mikročip je već evidentiran.';
        }
    }
    $old['veterinarian_id'] = (int) $old['veterinarian_id'];
    if ($old['veterinarian_id'] === 0 || !Veterinarian::exists($old['veterinarian_id'])) {
        $errors['veterinarian_id'] = 'Izaberite veterinara.';
    }

    $greskaSlike = ImageUpload::check($_FILES['photo'] ?? []);
    if ($greskaSlike) {
        $errors['photo'] = $greskaSlike;
    }

    if (!$errors) {
        $staraSlika = $pet['profile_photo'] ?? null;
        $novaSlika  = $staraSlika;
        if (!empty($_POST['remove_photo'])) {
            $novaSlika = null;
        }
        if (($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $novaSlika = ImageUpload::savePet($_FILES['photo']);
            } catch (RuntimeException $ex) {
                $errors['photo'] = $ex->getMessage();
            }
        }
    }

    if (!$errors) {
        $old['photo'] = $novaSlika;
        try {
            if ($pet) {
                Pet::update($id, $user['id'], $old);
                $_SESSION['flash'] = ['success', 'Podaci o ljubimcu su sačuvani.'];
            } else {
                Pet::create($user['id'], $old);
                $_SESSION['flash'] = ['success', 'Ljubimac "' . $old['name'] . '" je dodat.'];
            }
            if ($staraSlika && $staraSlika !== $novaSlika) {
                ImageUpload::delete($staraSlika); // zamenjena ili uklonjena slika
            }
            header('Location: ljubimci.php');
            exit;
        } catch (PDOException $ex) {
            if ($novaSlika && $novaSlika !== $staraSlika) {
                ImageUpload::delete($novaSlika);
            }
            $errors['microchip'] = 'Ovaj mikročip je već evidentiran.';
        }
    }
}

function polje(string $id, string $oznaka, array $errors, array $old, string $tip = 'text', string $ph = '', bool $obavezno = false): void
{
    $cls = isset($errors[$id]) ? 'is-invalid' : '';
    echo '<div class="mb-3"><label class="form-label" for="' . $id . '">' . e($oznaka) . ($obavezno ? ' *' : '') . '</label>';
    echo '<input type="' . $tip . '" class="form-control ' . $cls . '" id="' . $id . '" name="' . $id . '"';
    echo ' placeholder="' . e($ph) . '" value="' . e((string) ($old[$id] ?? '')) . '">';
    echo '<div class="invalid-feedback">' . e($errors[$id] ?? '') . '</div></div>';
}

$aktivna = 'ljubimci';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/owner_start.php';
?>

    <h2 class="naslov"><?= $pet ? 'Izmena ljubimca' : 'Dodavanje ljubimca' ?></h2>

    <form id="petForm" method="post" action="ljubimac.php" enctype="multipart/form-data" novalidate style="max-width: 640px;">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="mb-3">
            <label class="form-label" for="photo">Slika ljubimca</label>
            <div class="d-flex gap-3 align-items-center">
                <div id="photoPreview" class="pet-slika foto-prikaz">
                    <?php if (!empty($pet['profile_photo'])): ?>
                        <img src="<?= e($pet['profile_photo']) ?>" alt="">
                    <?php else: ?>
                        <i class="bi bi-camera"></i>
                    <?php endif; ?>
                </div>
                <div class="flex-grow-1">
                    <input type="file" class="form-control <?= isset($errors['photo']) ? 'is-invalid' : '' ?>"
                           id="photo" name="photo" accept="image/jpeg,image/png,image/webp"
                           data-preview="#photoPreview">
                    <div class="invalid-feedback"><?= e($errors['photo'] ?? 'JPG, PNG ili WebP, najviše 2 MB.') ?></div>
                    <div class="form-text">Nije obavezno. JPG, PNG ili WebP, najviše 2 MB.</div>
                    <?php if (!empty($pet['profile_photo'])): ?>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="remove_photo" name="remove_photo" value="1">
                            <label class="form-check-label" for="remove_photo">Ukloni trenutnu sliku</label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php polje('name', 'Ime ljubimca', $errors, $old, 'text', '', true); ?>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="species">Vrsta *</label>
                <select class="form-select <?= isset($errors['species']) ? 'is-invalid' : '' ?>" id="species" name="species">
                    <option value="">Izaberite</option>
                    <?php foreach (['Pas', 'Mačka', 'Zec', 'Ostalo'] as $v): ?>
                        <option value="<?= e($v) ?>" <?= $old['species'] === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= e($errors['species'] ?? '') ?></div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="sex">Pol *</label>
                <select class="form-select <?= isset($errors['sex']) ? 'is-invalid' : '' ?>" id="sex" name="sex">
                    <option value="">Izaberite</option>
                    <option value="MALE" <?= $old['sex'] === 'MALE' ? 'selected' : '' ?>>Muški</option>
                    <option value="FEMALE" <?= $old['sex'] === 'FEMALE' ? 'selected' : '' ?>>Ženski</option>
                    <option value="UNKNOWN" <?= $old['sex'] === 'UNKNOWN' ? 'selected' : '' ?>>Nepoznato</option>
                </select>
                <div class="invalid-feedback"><?= e($errors['sex'] ?? '') ?></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6"><?php polje('breed', 'Rasa', $errors, $old, 'text', 'npr. Labrador'); ?></div>
            <div class="col-md-6"><?php polje('birth_date', 'Datum rođenja', $errors, $old, 'date'); ?></div>
        </div>

        <?php polje('microchip', 'Broj mikročipa (15 cifara)', $errors, $old, 'text', '', false); ?>

        <div class="mb-3">
            <label class="form-label" for="veterinarian_id">Veterinar *</label>
            <select class="form-select <?= isset($errors['veterinarian_id']) ? 'is-invalid' : '' ?>" id="veterinarian_id" name="veterinarian_id">
                <option value="">Izaberite veterinara</option>
                <?php foreach ($vets as $v): ?>
                    <option value="<?= (int) $v['id'] ?>" <?= (int) $old['veterinarian_id'] === (int) $v['id'] ? 'selected' : '' ?>>
                        Dr <?= e($v['first_name'] . ' ' . $v['last_name']) ?> (<?= e($v['station']) ?>, <?= e($v['city']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback"><?= e($errors['veterinarian_id'] ?? '') ?></div>
        </div>

        <div class="d-flex gap-2">
            <a href="ljubimci.php" class="btn btn-outline-primary">Odustani</a>
            <button type="submit" class="btn btn-primary flex-grow-1">Sačuvaj</button>
        </div>
    </form>

    <script src="js/photo_preview.js"></script>
    <script>
        document.getElementById('petForm').addEventListener('submit', function (ev) {
            const f = ev.target;
            let ok = true;
            function proveri(id, uslov) {
                f.querySelector('#' + id).classList.toggle('is-invalid', !uslov);
                if (!uslov) ok = false;
            }
            const v = id => f.querySelector('#' + id).value.trim();
            proveri('name', v('name').length >= 1 && v('name').length <= 100);
            proveri('species', v('species') !== '');
            proveri('sex', v('sex') !== '');
            proveri('birth_date', v('birth_date') === '' || new Date(v('birth_date')) <= new Date());
            proveri('microchip', v('microchip') === '' || /^[0-9]{15}$/.test(v('microchip')));
            proveri('veterinarian_id', v('veterinarian_id') !== '');
            if (!proveriSliku(f.querySelector('#photo'))) ok = false;
            if (!ok) ev.preventDefault();
        });
    </script>

<?php
include __DIR__ . '/includes/owner_end.php';
include __DIR__ . '/includes/footer.php';
?>