<?php
require __DIR__ . '/../vendor/autoload.php';

use App\User;
use App\Veterinarian;
use App\Mailer;
use App\Notification;

session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$errors  = [];
$success = false;
$mailSent = false;
$step    = 1;
$old = [
        'first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'address' => '',
        'pet_name' => '', 'species' => '', 'breed' => '', 'sex' => '', 'birth_date' => '',
        'veterinarian_id' => '',
];
$vets = Veterinarian::all();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        die('Neispravan zahtev.');
    }
    foreach ($old as $k => $v) {
        $old[$k] = trim($_POST[$k] ?? '');
    }
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    // Korak 1: korisnik
    if (mb_strlen($old['first_name']) < 2 || mb_strlen($old['first_name']) > 100) {
        $errors['first_name'] = 'Ime mora imati 2 do 100 karaktera.';
    }
    if (mb_strlen($old['last_name']) < 2 || mb_strlen($old['last_name']) > 100) {
        $errors['last_name'] = 'Prezime mora imati 2 do 100 karaktera.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 191) {
        $errors['email'] = 'Unesite ispravnu e-mail adresu.';
    } elseif (User::findByEmail($old['email'])) {
        $errors['email'] = 'Nalog sa ovom e-mail adresom već postoji.';
    }
    if ($old['phone'] !== '' && !preg_match('/^\+?[0-9 \/-]{6,20}$/', $old['phone'])) {
        $errors['phone'] = 'Unesite ispravan broj telefona.';
    }
    if (mb_strlen($old['address']) > 255) {
        $errors['address'] = 'Adresa je predugačka.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Lozinka mora imati najmanje 8 karaktera, uz slova i bar jednu cifru.';
    }
    if ($password !== $confirm) {
        $errors['password_confirm'] = 'Lozinke se ne poklapaju.';
    }

    // Korak 2: ljubimac
    if (mb_strlen($old['pet_name']) < 1 || mb_strlen($old['pet_name']) > 100) {
        $errors['pet_name'] = 'Unesite ime ljubimca (do 100 karaktera).';
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

    // Korak 3: veterinar
    $vetId = (int) $old['veterinarian_id'];
    if ($vetId === 0 || !Veterinarian::exists($vetId)) {
        $errors['veterinarian_id'] = 'Izaberite veterinara.';
    }

    if (!$errors) {
        try {
            $res    = User::createWithPet($old, $password, $old, $vetId);
            $config = require __DIR__ . '/../config/config.php';
            $link   = $config['app']['base_url'] . '/activate.php?token=' . $res['token'];

            $subject = 'Aktivacija naloga';
            $html = '<p>Poštovani/a ' . e($old['first_name']) . ',</p>'
                    . '<p>Hvala što ste se registrovali. Kliknite na link da aktivirate nalog:</p>'
                    . '<p><a href="' . e($link) . '">' . e($link) . '</a></p>'
                    . '<p>Link važi 24 sata.</p>';

            $mailSent = Mailer::send($old['email'], $old['first_name'] . ' ' . $old['last_name'], $subject, $html);
            Notification::log($res['owner_id'], 'ACCOUNT_ACTIVATION', $subject, $mailSent ? 'SENT' : 'FAILED');
            $success = true;
        } catch (PDOException $ex) {
            $errors['email'] = 'Nalog sa ovom e-mail adresom već postoji.';
        }
    }

    // Na kom koraku da se prikaže prva greška
    if ($errors) {
        $k1 = ['first_name', 'last_name', 'email', 'phone', 'address', 'password', 'password_confirm'];
        $k2 = ['pet_name', 'species', 'breed', 'sex', 'birth_date'];
        if (array_intersect(array_keys($errors), $k1)) {
            $step = 1;
        } elseif (array_intersect(array_keys($errors), $k2)) {
            $step = 2;
        } else {
            $step = 3;
        }
    }
}

function polje(string $id, string $oznaka, array $errors, array $old, string $tip = 'text', string $placeholder = '', bool $obavezno = false): void
{
    $cls = isset($errors[$id]) ? 'is-invalid' : '';
    echo '<div class="mb-3"><label class="form-label" for="' . $id . '">' . e($oznaka) . ($obavezno ? ' *' : '') . '</label>';
    echo '<input type="' . $tip . '" class="form-control ' . $cls . '" id="' . $id . '" name="' . $id . '"';
    echo ' placeholder="' . e($placeholder) . '"';
    if ($tip !== 'password') {
        echo ' value="' . e($old[$id] ?? '') . '"';
    }
    echo '><div class="invalid-feedback">' . e($errors[$id] ?? '') . '</div></div>';
}

include __DIR__ . '/includes/header.php';
?>

    <div class="row g-0 register-wrap">
        <div class="col-md-5 d-none d-md-block register-slika"></div>

        <div class="col-md-7 d-flex align-items-center">
            <div class="register-forma w-100">

                <?php if ($success): ?>
                    <h2 class="naslov">Nalog je kreiran</h2>
                    <?php if ($mailSent): ?>
                        <p>Poslali smo aktivacioni link na <strong><?= e($old['email']) ?></strong>. Kliknite na njega da aktivirate nalog (link važi 24 sata).</p>
                    <?php else: ?>
                        <p class="text-danger">Nalog je kreiran, ali aktivacioni e-mail nije mogao da bude poslat. Pokušajte ponovo kasnije.</p>
                    <?php endif; ?>
                <?php else: ?>

                    <!-- Indikator koraka -->
                    <div class="koraci mb-4">
                        <span class="korak-broj" data-ind="1">1</span><span class="korak-linija"></span>
                        <span class="korak-broj" data-ind="2">2</span><span class="korak-linija"></span>
                        <span class="korak-broj" data-ind="3">3</span>
                    </div>

                    <form id="registerForm" method="post" action="register.php" data-start="<?= $step ?>" novalidate>
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

                        <!-- KORAK 1 -->
                        <div class="korak" data-korak="1">
                            <h2 class="naslov">Kreirajte svoj nalog</h2>
                            <p class="small">Već imate nalog? <a href="login.php">Prijavi se</a></p>
                            <div class="row">
                                <div class="col-md-6"><?php polje('first_name', 'Ime', $errors, $old, 'text', '', true); ?></div>
                                <div class="col-md-6"><?php polje('last_name', 'Prezime', $errors, $old, 'text', '', true); ?></div>
                            </div>
                            <?php
                            polje('email', 'E-mail adresa', $errors, $old, 'email', '', true);
                            polje('phone', 'Broj telefona', $errors, $old, 'text', '+381 60 123 4567');
                            polje('address', 'Adresa', $errors, $old, 'text', 'Ulica i broj, grad');
                            polje('password', 'Lozinka', $errors, $old, 'password', 'Minimalno 8 karaktera', true);
                            polje('password_confirm', 'Potvrdi lozinku', $errors, $old, 'password', 'Ponovite lozinku', true);
                            ?>
                            <button type="button" class="btn btn-primary w-100 btn-dalje">Nastavite</button>
                        </div>

                        <!-- KORAK 2 -->
                        <div class="korak d-none" data-korak="2">
                            <h2 class="naslov">Dodajte svog ljubimca</h2>
                            <p class="small">Unesite osnovne podatke o svom ljubimcu.</p>
                            <?php polje('pet_name', 'Ime ljubimca', $errors, $old, 'text', '', true); ?>
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
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-nazad">Nazad</button>
                                <button type="button" class="btn btn-primary flex-grow-1 btn-dalje">Nastavite</button>
                            </div>
                        </div>

                        <!-- KORAK 3 -->
                        <div class="korak d-none" data-korak="3">
                            <h2 class="naslov">Izaberite veterinara</h2>
                            <p class="small">Izaberite veterinara kod kojeg želite da se vaš ljubimac leči.</p>

                            <?php if (!$vets): ?>
                                <div class="alert alert-warning">Trenutno nema dostupnih veterinara.</div>
                            <?php endif; ?>

                            <div id="vetLista" class="<?= isset($errors['veterinarian_id']) ? 'is-invalid' : '' ?>">
                                <?php foreach ($vets as $v): ?>
                                    <label class="vet-kartica">
                                        <input type="radio" name="veterinarian_id" value="<?= (int) $v['id'] ?>"
                                                <?= (int) $old['veterinarian_id'] === (int) $v['id'] ? 'checked' : '' ?>>
                                        <i class="bi bi-person-circle vet-ikona"></i>
                                        <span>
                                    <strong>Dr. <?= e($v['first_name'] . ' ' . $v['last_name']) ?></strong><br>
                                    <small><?= e($v['station']) ?>, <?= e($v['city']) ?></small>
                                    <?php if ($v['specialization']): ?>
                                        <br><small class="text-muted"><?= e($v['specialization']) ?></small>
                                    <?php endif; ?>
                                </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="invalid-feedback d-block" id="vetGreska"><?= e($errors['veterinarian_id'] ?? '') ?></div>

                            <div class="d-flex gap-2 mt-3">
                                <button type="button" class="btn btn-outline-primary btn-nazad">Nazad</button>
                                <button type="button" class="btn btn-primary flex-grow-1 btn-dalje">Nastavite</button>
                            </div>
                        </div>
                    </form>

                    <!-- Prozor za potvrdu -->
                    <div class="modal fade" id="potvrdaModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content text-center p-3">
                                <h5 class="naslov mb-3">Želite li da kreirate nalog?</h5>
                                <button type="button" class="btn btn-primary w-100" id="btnKreiraj">Kreiraj</button>
                                <button type="button" class="btn btn-link mt-1" data-bs-dismiss="modal">Odustani</button>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="js/register.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>