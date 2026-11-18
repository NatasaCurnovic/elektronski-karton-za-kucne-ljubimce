<?php
$stavke = [
    'pregled'     => ['Pregled',            'bi-grid',                'dashboard.php'],
    'obavestenja' => ['Obaveštenja',        'bi-bell',                null],
    'zakazivanje' => ['Zakazivanje termina', 'bi-calendar-plus',      'zakazivanje.php'],
    'termini'     => ['Moji termini', 'bi-calendar-check', 'moji_termini.php'],
    'karton'      => ['E-karton',           'bi-file-earmark-medical', 'e_karton.php'],
    'ljubimci'    => ['Ljubimci',           'bi-heart',               'ljubimci.php'],
    'kalendar'    => ['Kalendar',           'bi-calendar3',           null],
    'profil'      => ['Profil',             'bi-person',              null],
    'podesavanja' => ['Podešavanja',        'bi-gear',                null],
];
?>
<div class="row g-0 owner-wrap">
    <div class="col-md-3 col-lg-2 owner-meni">
        <button class="btn btn-primary w-100 d-md-none rounded-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#glavniMeni">
            <i class="bi bi-list"></i> Meni
        </button>
        <nav id="glavniMeni" class="collapse d-md-block py-2">
            <?php foreach ($stavke as $kljuc => [$naziv, $ikona, $url]): ?>
                <a href="<?= $url ?? '#' ?>"
                   class="<?= ($aktivna ?? '') === $kljuc ? 'aktivan' : '' ?> <?= $url ? '' : 'onemoguceno' ?>">
                    <i class="bi <?= $ikona ?>"></i> <?= htmlspecialchars($naziv, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
            <form method="post" action="logout.php" class="m-0">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit"><i class="bi bi-box-arrow-right"></i> Odjavi se</button>
            </form>
        </nav>
    </div>
    <div class="col-md-9 col-lg-10 p-4">