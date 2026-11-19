<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\Admin;

$user = Auth::requireRole(['ADMIN']);

// Omogućavanje / onemogućavanje prijave
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tok = $_POST['csrf'] ?? '';
    if ($tok === '' || !hash_equals($_SESSION['csrf'], $tok)) {
        http_response_code(400);
        die('Nevažeći zahtev.');
    }
    Admin::setActive((int) ($_POST['id'] ?? 0), ($_POST['akcija'] ?? '') === 'omoguci', $user['id']);
    header('Location: korisnici.php?' . http_build_query([
        'q' => $_POST['q'] ?? '', 'uloga' => $_POST['uloga'] ?? '', 'ok' => 1,
    ]));
    exit;
}

$q     = trim($_GET['q'] ?? '');
$uloga = $_GET['uloga'] ?? '';
if (!in_array($uloga, ['OWNER', 'VETERINARIAN', 'ADMIN'], true)) {
    $uloga = '';
}
$korisnici = Admin::users($q, $uloga);

$nazivUloge = ['OWNER' => 'Vlasnik', 'VETERINARIAN' => 'Veterinar', 'ADMIN' => 'Administrator'];

$aktivna = 'korisnici';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/admin_start.php';
?>

<h2 class="naslov">Korisnici</h2>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success py-2">Promena je sačuvana.</div>
<?php endif; ?>

<form method="get" class="row g-2 mb-3" style="max-width: 720px;">
    <div class="col-sm-6">
        <input type="text" name="q" class="form-control" placeholder="Ime, prezime ili e-mail" value="<?= e($q) ?>">
    </div>
    <div class="col-sm-4">
        <select name="uloga" class="form-select">
            <option value="">Sve uloge</option>
            <?php foreach ($nazivUloge as $k => $n): ?>
                <option value="<?= $k ?>" <?= $uloga === $k ? 'selected' : '' ?>><?= $n ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-2">
        <button type="submit" class="btn btn-primary w-100">Traži</button>
    </div>
</form>

<?php if (!$korisnici): ?>
    <p class="text-muted">Nema korisnika za ovu pretragu.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover align-middle bg-white">
        <thead>
            <tr><th>Ime i prezime</th><th>E-mail</th><th>Uloga</th><th>Prijava</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($korisnici as $k): ?>
            <tr>
                <td><?= e($k['first_name'] . ' ' . $k['last_name']) ?></td>
                <td><?= e($k['email']) ?></td>
                <td><?= e($nazivUloge[$k['role']] ?? $k['role']) ?></td>
                <td>
                    <?php if ($k['is_active']): ?>
                        <span class="badge text-bg-success">Omogućena</span>
                    <?php else: ?>
                        <span class="badge text-bg-danger">Onemogućena</span>
                    <?php endif; ?>
                    <?php if (!$k['is_email_verified']): ?>
                        <span class="badge text-bg-warning">Nije aktiviran</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <?php if ((int) $k['id'] !== (int) $user['id']): ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                            <input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
                            <input type="hidden" name="q" value="<?= e($q) ?>">
                            <input type="hidden" name="uloga" value="<?= e($uloga) ?>">
                            <input type="hidden" name="akcija" value="<?= $k['is_active'] ? 'onemoguci' : 'omoguci' ?>">
                            <button type="submit"
                                    class="btn btn-sm <?= $k['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                <?= $k['is_active'] ? 'Onemogući' : 'Omogući' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <small class="text-muted">to ste vi</small>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="small text-muted">Prikazano najviše 200 korisnika, najnoviji prvi.</p>
<?php endif; ?>

<?php
include __DIR__ . '/includes/admin_end.php';
include __DIR__ . '/includes/footer.php';
?>
