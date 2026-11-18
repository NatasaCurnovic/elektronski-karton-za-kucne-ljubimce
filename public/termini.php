<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\Slot;
use App\Database;

$user   = Auth::requireRole(['VETERINARIAN']);
$greske = [];
$vr = ['date' => '', 'from' => '09:00', 'to' => '12:00', 'duration' => '30'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_POST['csrf'] ?? '';
    if ($tok === '' || !hash_equals($_SESSION['csrf'], $tok)) {
        http_response_code(400);
        die('Nevažeći zahtev.');
    }

    // Uklanjanje jednog slobodnog termina
    if (($_POST['action'] ?? '') === 'remove') {
        Slot::removeAvailable((int) ($_POST['slot_id'] ?? 0), $user['id']);
        header('Location: termini.php?uklonjen=1');
        exit;
    }

    // Dodavanje novih termina
    foreach (['date', 'from', 'to', 'duration'] as $k) {
        $vr[$k] = trim($_POST[$k] ?? '');
    }
    $trajanje = (int) $vr['duration'];
    $start = DateTime::createFromFormat('Y-m-d H:i', $vr['date'] . ' ' . $vr['from']);
    $kraj  = DateTime::createFromFormat('Y-m-d H:i', $vr['date'] . ' ' . $vr['to']);

    if (!$start || !$kraj) {
        $greske[] = 'Unesite ispravan datum i vreme.';
    } else {
        if ($start <= new DateTime()) {
            $greske[] = 'Početak mora biti u budućnosti.';
        }
        if ($kraj <= $start) {
            $greske[] = 'Kraj mora biti posle početka.';
        } elseif (($kraj->getTimestamp() - $start->getTimestamp()) > 10 * 3600) {
            $greske[] = 'Najviše 10 sati odjednom.';
        }
    }
    if (!in_array($trajanje, [15, 20, 30, 45, 60], true)) {
        $greske[] = 'Izaberite trajanje termina.';
    }

    if (!$greske) {
        $napravljeno = 0;
        $preskoceno  = 0;
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        $t = clone $start;
        while (true) {
            $kraj_termina = (clone $t)->modify('+' . $trajanje . ' minutes');
            if ($kraj_termina > $kraj) {
                break;
            }
            $s = $t->format('Y-m-d H:i:s');
            $e = $kraj_termina->format('Y-m-d H:i:s');

            if (Slot::overlaps($user['id'], $s, $e)) {
                $preskoceno++;
            } else {
                Slot::create($user['id'], $s, $e);
                $napravljeno++;
            }
            $t = $kraj_termina;
        }
        $pdo->commit();

        header('Location: termini.php?dodato=' . $napravljeno . '&preskoceno=' . $preskoceno);
        exit;
    }
}

$termini = Slot::forVet($user['id']);

$aktivna = 'termini';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/vet_start.php';
?>

    <h2 class="naslov">Termini</h2>

<?php if (isset($_GET['dodato'])): ?>
    <div class="alert alert-success py-2">
        Dodato termina: <?= (int) $_GET['dodato'] ?>.
        <?php if ((int) ($_GET['preskoceno'] ?? 0) > 0): ?>
            Preskočeno zbog preklapanja: <?= (int) $_GET['preskoceno'] ?>.
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php if (isset($_GET['uklonjen'])): ?>
    <div class="alert alert-success py-2">Termin je uklonjen.</div>
<?php endif; ?>
<?php if (($_GET['otkazan'] ?? '') === '1'): ?>
    <div class="alert alert-success py-2">Termin je otkazan i vlasnik je obavešten e-mailom.</div>
<?php elseif (($_GET['otkazan'] ?? '') === '2'): ?>
    <div class="alert alert-warning py-2">Termin je otkazan, ali e-mail vlasniku nije poslat.</div>
<?php endif; ?>
<?php foreach ($greske as $g): ?>
    <div class="alert alert-danger py-2"><?= e($g) ?></div>
<?php endforeach; ?>

    <div class="zapis mb-4">
        <h5 class="mb-3">Dodaj slobodne termine</h5>
        <form method="post" class="row g-2 align-items-end">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <div class="col-sm-6 col-lg-3">
                <label class="form-label">Datum</label>
                <input type="date" name="date" class="form-control" min="<?= date('Y-m-d') ?>"
                       value="<?= e($vr['date']) ?>" required>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Od</label>
                <input type="time" name="from" class="form-control" value="<?= e($vr['from']) ?>" required>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Do</label>
                <input type="time" name="to" class="form-control" value="<?= e($vr['to']) ?>" required>
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label">Trajanje jednog termina</label>
                <select name="duration" class="form-select">
                    <?php foreach ([15, 20, 30, 45, 60] as $m): ?>
                        <option value="<?= $m ?>" <?= (string) $m === $vr['duration'] ? 'selected' : '' ?>><?= $m ?> min</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <button type="submit" class="btn btn-primary w-100">Dodaj</button>
            </div>
        </form>
    </div>

    <h5 class="naslov">Budući termini</h5>
<?php if (!$termini): ?>
    <p class="text-muted">Još nema definisanih termina.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle bg-white">
            <thead>
            <tr><th>Datum</th><th>Vreme</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($termini as $t): ?>
                <tr>
                    <td><?= datum($t['start_time']) ?></td>
                    <td><?= date('H:i', strtotime($t['start_time'])) ?> – <?= date('H:i', strtotime($t['end_time'])) ?></td>
                    <td>
                        <?php if ($t['status'] === 'AVAILABLE'): ?>
                            <span class="badge text-bg-success">Slobodan</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Zauzet</span>
                            <?= $t['pet_name'] ? ' · ' . e($t['pet_name']) : '' ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if ($t['status'] === 'AVAILABLE'): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="slot_id" value="<?= (int) $t['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">Ukloni</button>
                            </form>
                        <?php elseif ($t['appointment_id']): ?>
                            <a href="otkazi_termin.php?id=<?= (int) $t['appointment_id'] ?>"
                               class="btn btn-outline-danger btn-sm">Otkaži termin</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
include __DIR__ . '/includes/vet_end.php';
include __DIR__ . '/includes/footer.php';
?>