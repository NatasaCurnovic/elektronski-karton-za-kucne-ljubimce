<?php
// Pokreće se iz komandne linije (Task Scheduler / cron), ne iz pregledača.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Samo iz komandne linije.');
}

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/includes/helpers.php';

use App\Database;
use App\Mailer;
use App\Notification;

$config = require __DIR__ . '/../config/config.php';
$pdo    = Database::getConnection();

// Zakazani termini koji počinju u okviru "sati unapred" koje je vlasnik izabrao
// (podrazumevano 24), a za koje podsetnik još nije poslat.
$termini = $pdo->query(
    'SELECT a.id, a.owner_id, s.start_time, s.end_time, p.name AS pet_name,
            o.email, o.first_name, o.last_name,
            v.first_name AS vet_first, v.last_name AS vet_last
     FROM appointments a
     JOIN appointment_slots s ON s.id = a.slot_id
     JOIN pets p ON p.id = a.pet_id
     JOIN users o ON o.id = a.owner_id
     JOIN users v ON v.id = a.veterinarian_id
     LEFT JOIN notification_preferences np ON np.user_id = a.owner_id
     WHERE a.status = "SCHEDULED"
       AND o.is_active = 1
       AND s.start_time > NOW()
       AND s.start_time <= DATE_ADD(NOW(), INTERVAL COALESCE(np.appointment_reminder_hours, 24) HOUR)
       AND COALESCE(np.appointment_reminders, 1) = 1
       AND NOT EXISTS (
            SELECT 1 FROM notifications n
            WHERE n.appointment_id = a.id AND n.type = "APPOINTMENT_REMINDER" AND n.status = "SENT"
       )'
)->fetchAll();

$poslato = 0;
foreach ($termini as $t) {
    $url     = $config['app']['base_url'] . '/moji_termini.php';
    $gcal    = gcal_link(
        'Veterinar: ' . $t['pet_name'],
        $t['start_time'],
        $t['end_time'],
        'Pregled ljubimca ' . $t['pet_name'] . ' kod dr ' . $t['vet_first'] . ' ' . $t['vet_last']
    );
    $subject = 'Podsetnik: termin za ' . $t['pet_name'];
    $html = '<p>Poštovani/a ' . e($t['first_name']) . ',</p>'
        . '<p>Podsećamo vas na termin za ljubimca <strong>' . e($t['pet_name']) . '</strong>'
        . ' kod dr ' . e($t['vet_first'] . ' ' . $t['vet_last'])
        . ': <strong>' . datum($t['start_time'], true) . '</strong>.</p>'
        . '<p><a href="' . e($gcal) . '">Dodaj u Google Calendar</a><br>'
        . '<a href="' . e($url) . '">Moji termini</a> (tu možete i otkazati termin, najkasnije 1 sat ranije)</p>';

    $ok = Mailer::send($t['email'], $t['first_name'] . ' ' . $t['last_name'], $subject, $html);
    Notification::log((int) $t['owner_id'], 'APPOINTMENT_REMINDER', $subject, $ok ? 'SENT' : 'FAILED', (int) $t['id']);

    echo ($ok ? 'Poslato: ' : 'Neuspešno: ') . $t['email'] . ' (termin ' . $t['id'] . ")\n";
    $poslato += $ok ? 1 : 0;
}

echo 'Gotovo. Poslato podsetnika: ' . $poslato . "\n";