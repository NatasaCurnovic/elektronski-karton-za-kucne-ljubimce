<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\Auth;
use App\Pet;

$user = Auth::requireRole(['VETERINARIAN']);
$q    = trim($_GET['q'] ?? '');
$pacijenti = Pet::forVet($user['id'], $q);

$aktivna = 'pacijenti';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/vet_start.php';
?>

    <h2 class="naslov">Pacijenti</h2>

    <form method="get" action="pacijenti.php" class="d-flex gap-2 mb-3" style="max-width: 420px;">
        <input type="text" name="q" class="form-control" placeholder="Pretraga po imenu ljubimca" value="<?= e($q) ?>">
        <button type="submit" class="btn btn-primary">Traži</button>
    </form>

<?php if (!$pacijenti): ?>
    <p class="text-muted">Nema pacijenata<?= $q !== '' ? ' za ovu pretragu' : '' ?>.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle bg-white">
            <thead>
            <tr>
                <th>Ljubimac</th>
                <th>Vrsta i rasa</th>
                <th>Vlasnik</th>
                <th>Poslednji pregled</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pacijenti as $p): ?>
                <tr>
                    <td>
                    <span class="mala-slika">
                        <?php if ($p['profile_photo']): ?>
                            <img src="<?= e($p['profile_photo']) ?>" alt="">
                        <?php else: ?>
                            <i class="bi bi-camera"></i>
                        <?php endif; ?>
                    </span>
                        <strong class="ms-2"><?= e($p['name']) ?></strong>
                    </td>
                    <td><?= e($p['species']) ?><?= $p['breed'] ? ', ' . e($p['breed']) : '' ?></td>
                    <td><?= e($p['owner_first'] . ' ' . $p['owner_last']) ?></td>
                    <td><?= $p['last_exam'] ? datum($p['last_exam']) : '<span class="text-muted">nema</span>' ?></td>
                    <td class="text-end">
                        <a href="pacijent.php?id=<?= (int) $p['id'] ?>" class="btn btn-primary btn-sm">E-karton</a>
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