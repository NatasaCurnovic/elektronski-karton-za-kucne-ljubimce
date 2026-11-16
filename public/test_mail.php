<?php
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = require __DIR__ . '/../config/config.php';
$m = $config['mail'];

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $m['host'];
    $mail->Port       = $m['port'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $m['username'];
    $mail->Password   = $m['password'];
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($m['from'], $m['from_name']);
    $mail->addAddress('test@primer.rs', 'Test Korisnik');

    $mail->Subject = 'Test poruka';
    $mail->Body    = 'Ako vidiš ovu poruku u Mailtrap-u, slanje radi.';

    $mail->send();
    echo 'Poruka je poslata. Proveri Mailtrap.';
} catch (Exception $e) {
    echo 'Greška: ' . $mail->ErrorInfo;
}