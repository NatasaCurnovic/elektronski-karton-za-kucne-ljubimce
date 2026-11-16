<?php
namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    public static function send(string $to, string $name, string $subject, string $html): bool
    {
        $config = require __DIR__ . '/../config/config.php';
        $m = $config['mail'];

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host     = $m['host'];
            $mail->Port     = $m['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $m['username'];
            $mail->Password = $m['password'];
            $mail->CharSet  = 'UTF-8';

            $mail->setFrom($m['from'], $m['from_name']);
            $mail->addAddress($to, $name);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = strip_tags($html);

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}