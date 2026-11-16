<?php
require __DIR__ . '/../vendor/autoload.php';

$user = App\Auth::requireRole(['OWNER']);
include __DIR__ . '/includes/header.php';
?>
    <div class="container py-4">
        <h2 class="naslov">Zdravo, <?= htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') ?>!</h2>
        <p>Dobro došli u vaš nalog.</p>
    </div>
<?php include __DIR__ . '/includes/footer.php'; ?>