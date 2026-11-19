<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\User;
use App\Veterinarian;

$user  = Auth::requireRole(['OWNER', 'VETERINARIAN']);
$jeVet = $user['role'] === 'VETERINARIAN';

$p   = User::profile($user['id']);
$vet = $jeVet ? Veterinarian::profile($user['id']) : null;

$v = [
    'first_name' => $p['first_name'],
    'last_name'  => $p['last_name'],
    'phone'      => $p['phone'] ?? '',
    'address'    => $p['address'] ?? '',
    'license'    => $vet['license_number'] ?? '',
    'spec'       => $vet['specialization'] ?? '',
];
$greskePodaci  = [];
$greskeLozinka = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_POST['csrf'] ?? '';
    if ($tok === '' || !hash_equals($_SESSION['csrf'], $tok)) {
        http_response_code(400);
        die('Nevažeći zahtev.');
    }

    // --- Lični podaci ---
    if (($_POST['akcija'] ?? '') === 'podaci') {
        foreach (['first_name', 'last_name', 'phone', 'address', 'license', 'spec'] as $k) {
            $v[$k] = trim($_POST[$k] ?? $v[$k]);
        }

        if ($v['first_name'] === '' || mb_strlen($v['first_name']) > 100) {
            $greskePodaci[] = 'Ime je obavezno (najviše 100 karaktera).';
        }
        if ($v['last_name'] === '' || mb_strlen($v['last_name']) > 100) {
            $greskePodaci[] = 'Prezime je obavezno (najviše 100 karaktera).';
        }
        if ($v['phone'] !== '' && !preg_match('/^[0-9+\/\s()-]{6,30}$/', $v['phone'])) {
            $greskePodaci[] = 'Telefon može sadržati samo cifre, razmake i znakove + / ( ) -.';
        }
        if (mb_strlen($v['address']) > 255) {
            $greskePodaci[] = 'Adresa može imati najviše 255 karaktera.';
        }
        if ($jeVet && (mb_strlen($v['license']) > 100 || mb_strlen($v['spec']) > 150)) {
            $greskePodaci[] = 'Broj licence (najviše 100) ili specijalizacija (najviše 150) su predugi.';
        }

        if (!$greskePodaci) {
            if ($jeVet && !Veterinarian::updateProfile($user['id'], $v['license'] ?: null, $v['spec'] ?: null)) {
                $greskePodaci[] = 'Taj broj licence je već u upotrebi.';
            } else {
                User::updateProfile($user['id'], $v['first_name'], $v['last_name'], $v['phone'] ?: null, $v['address'] ?: null);
                header('Location: profil.php?ok=podaci');
                exit;
            }
        }
    }

    // --- Promena lozinke ---
    if (($_POST['akcija'] ?? '') === 'lozinka') {
        $stara = $_POST['stara'] ?? '';
        $nova  = $_POST['nova'] ?? '';
        $potvr = $_POST['potvrda'] ?? '';

        if (strlen($nova) < 8 || !preg_match('/[A-Z]/', $nova) || !preg_match('/[a-z]/', $nova) || !preg_match('/[0-9]/', $nova)) {
            $greskeLozinka[] = 'Nova lozinka mora imati najmanje 8 karaktera, veliko i malo slovo i cifru.';
        }
        if ($nova !== $potvr) {
            $greskeLozinka[] = 'Nova lozinka i potvrda se ne poklapaju.';
        }
        if (!$greskeLozinka) {
            if (User::changePassword($user['id'], $stara, $nova)) {
                session_regenerate_id(true);
                header('Location: profil.php?ok=lozinka');
                exit;
            }
            $greskeLozinka[] = 'Stara lozinka nije tačna.';
        }
    }
}

$aktivna = 'profil';
include __DIR__ . '/includes/header.php';
include __DIR__ . ($jeVet ? '/includes/vet_start.php' : '/includes/owner_start.php');
?>

    <h2 class="naslov">Profil</h2>

<?php $ok = $_GET['ok'] ?? ''; ?>
<?php if ($ok === 'podaci'): ?>
    <div class="alert alert-success py-2">Podaci su sačuvani.</div>
<?php elseif ($ok === 'lozinka'): ?>
    <div class="alert alert-success py-2">Lozinka je promenjena.</div>
<?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="zapis">
                <h5 class="mb-3">Lični podaci</h5>
                <?php foreach ($greskePodaci as $g): ?>
                    <div class="alert alert-danger py-2"><?= e($g) ?></div>
                <?php endforeach; ?>

                <form method="post">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                    <input type="hidden" name="akcija" value="podaci">

                    <div class="mb-3">
                        <label class="form-label">E-mail (za prijavu)</label>
                        <input type="text" class="form-control" value="<?= e($p['email']) ?>" disabled>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Ime *</label>
                            <input type="text" name="first_name" class="form-control" maxlength="100"
                                   value="<?= e($v['first_name']) ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Prezime *</label>
                            <input type="text" name="last_name" class="form-control" maxlength="100"
                                   value="<?= e($v['last_name']) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="phone" class="form-control" maxlength="30" value="<?= e($v['phone']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresa</label>
                        <input type="text" name="address" class="form-control" maxlength="255" value="<?= e($v['address']) ?>">
                    </div>

                    <?php if ($jeVet): ?>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label">Broj licence</label>
                            <input type="text" name="license" class="form-control" maxlength="100" value="<?= e($v['license']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Specijalizacija</label>
                            <input type="text" name="spec" class="form-control" maxlength="150" value="<?= e($v['spec']) ?>">
                        </div>
                        <?php if ($vet): ?>
                            <div class="small text-muted mb-3">
                                Stanica: <strong><?= e($vet['station_name']) ?></strong>,
                                <?= e($vet['station_address'] . ', ' . $vet['station_city']) ?>
                                · <?= e($vet['station_phone']) ?>
                                (stanicu menja administrator)
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">Sačuvaj</button>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="zapis">
                <h5 class="mb-3">Promena lozinke</h5>
                <?php foreach ($greskeLozinka as $g): ?>
                    <div class="alert alert-danger py-2"><?= e($g) ?></div>
                <?php endforeach; ?>

                <form method="post" autocomplete="off">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                    <input type="hidden" name="akcija" value="lozinka">

                    <div class="mb-3">
                        <label class="form-label">Stara lozinka</label>
                        <input type="password" name="stara" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nova lozinka</label>
                        <input type="password" name="nova" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Potvrda nove lozinke</label>
                        <input type="password" name="potvrda" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Promeni lozinku</button>
                </form>
            </div>
        </div>
    </div>

<?php
include __DIR__ . ($jeVet ? '/includes/vet_end.php' : '/includes/owner_end.php');
include __DIR__ . '/includes/footer.php';
?>