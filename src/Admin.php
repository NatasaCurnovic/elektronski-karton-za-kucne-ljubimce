<?php

namespace App;

use PDO;

class Admin
{
    public static function counts(): array
    {
        return Database::getConnection()->query(
            'SELECT
                (SELECT COUNT(*) FROM users WHERE role = "OWNER")        AS owners,
                (SELECT COUNT(*) FROM users WHERE role = "VETERINARIAN") AS vets,
                (SELECT COUNT(*) FROM pets WHERE is_active = 1)          AS pets,
                (SELECT COUNT(*) FROM appointments a
                   JOIN appointment_slots s ON s.id = a.slot_id
                  WHERE a.status = "SCHEDULED" AND s.start_time > NOW()) AS upcoming'
        )->fetch();
    }

    // Lista korisnika sa opcionalnom pretragom (ime, prezime, e-mail) i filterom po ulozi
    public static function users(string $q = '', string $role = ''): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT id, first_name, last_name, email, role, is_active, is_email_verified, created_at
             FROM users
             WHERE (:q1 = "" OR first_name LIKE :q2 OR last_name LIKE :q3 OR email LIKE :q4)
               AND (:r1 = "" OR role = :r2)
             ORDER BY created_at DESC
             LIMIT 200'
        );
        $like = '%' . $q . '%';
        $stmt->execute([
            ':q1' => $q, ':q2' => $like, ':q3' => $like, ':q4' => $like,
            ':r1' => $role, ':r2' => $role,
        ]);
        return $stmt->fetchAll();
    }

    // Omogućava / onemogućava prijavu. Administrator ne može da onemogući samog sebe.
    public static function setActive(int $id, bool $aktivan, int $adminId): void
    {
        Database::getConnection()->prepare(
            'UPDATE users SET is_active = ? WHERE id = ? AND id <> ?'
        )->execute([$aktivan ? 1 : 0, $id, $adminId]);
    }

    public static function vetStatistics(): array
    {
        return Database::getConnection()->query(
            'SELECT veterinarian_name, assigned_pets, completed_examinations
             FROM vw_veterinarian_statistics
             ORDER BY assigned_pets DESC'
        )->fetchAll();
    }

    public static function stations(): array
    {
        return Database::getConnection()->query(
            'SELECT id, name, city FROM veterinary_stations ORDER BY name'
        )->fetchAll();
    }
}