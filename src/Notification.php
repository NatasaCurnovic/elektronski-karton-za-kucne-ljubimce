<?php
namespace App;

class Notification
{
    public static function log(int $userId, string $type, string $subject, string $status, ?int $appointmentId = null): void
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO notifications (user_id, appointment_id, type, subject, status, sent_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $appointmentId,
            $type,
            $subject,
            $status,
            $status === 'SENT' ? date('Y-m-d H:i:s') : null,
        ]);
    }
}