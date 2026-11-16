<?php
namespace App;

class Veterinarian
{
    public static function all(): array
    {
        $sql = 'SELECT u.id, u.first_name, u.last_name, s.name AS station, s.city, vp.specialization
                FROM users u
                JOIN veterinarian_profiles vp ON vp.user_id = u.id
                JOIN veterinary_stations s ON s.id = vp.veterinary_station_id
                WHERE u.role = "VETERINARIAN" AND u.is_active = 1
                ORDER BY u.last_name, u.first_name';
        return Database::getConnection()->query($sql)->fetchAll();
    }

    public static function exists(int $id): bool
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT 1 FROM users WHERE id = ? AND role = "VETERINARIAN" AND is_active = 1'
        );
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }
}