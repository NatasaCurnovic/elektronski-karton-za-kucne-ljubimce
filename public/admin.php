<?php
require __DIR__ . '/../vendor/autoload.php';

$user = App\Auth::requireRole(['ADMIN']);
include __DIR__ . '/includes/header.php';
?>
    <div class="container py-4">
        <h2 class="naslov">Administracija</h2>
        <p>Dobro došli, dr <?= htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') ?>.</p>
    </div>
<?php include __DIR__ . '/includes/footer.php'; ?>