<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\PetQr;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$user  = Auth::requireRole(['OWNER']);
$petId = (int) ($_GET['pet'] ?? 0);

// Samo vlasnik svog ljubimca
if (!$petId || !PetQr::ownedBy($petId, $user['id'])) {
    http_response_code(404);
    die('Nije pronađeno.');
}

$config = require __DIR__ . '/../config/config.php';
$token  = PetQr::tokenFor($petId);
$url    = $config['app']['base_url'] . '/ljubimac_javno.php?t=' . $token;

$rezultat = (new PngWriter())->write(new QrCode($url));

header('Content-Type: image/png');
if (isset($_GET['download'])) {
    header('Content-Disposition: attachment; filename="qr-ljubimac-' . $petId . '.png"');
}
echo $rezultat->getString();