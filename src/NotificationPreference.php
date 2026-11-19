<?php

namespace App;

class NotificationPreference
{
    // Podešavanja korisnika; ako nema reda, važe podrazumevana
    public static function forUser(int $userId): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT appointment_reminders, appointment_reminder_hours
             FROM notification_preferences WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: ['appointment_reminders' => 1, 'appointment_reminder_hours' => 24];
    }

    public static function save(int $userId, bool $podsetnici, int $sati): void
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO notification_preferences (user_id, appointment_reminders, appointment_reminder_hours)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                appointment_reminders = VALUES(appointment_reminders),
                appointment_reminder_hours = VALUES(appointment_reminder_hours)'
        );
        $stmt->execute([$userId, $podsetnici ? 1 : 0, $sati]);
    }
}