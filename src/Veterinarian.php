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

    public static function find(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT u.first_name, u.last_name, s.name AS station, s.city, s.phone
             FROM users u
             JOIN veterinarian_profiles vp ON vp.user_id = u.id
             JOIN veterinary_stations s ON s.id = vp.veterinary_station_id
             WHERE u.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // Stručni podaci veterinara i njegova stanica
    public static function profile(int $userId): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT vp.license_number, vp.specialization,
                    s.name AS station_name, s.address AS station_address,
                    s.city AS station_city, s.phone AS station_phone
             FROM veterinarian_profiles vp
             JOIN veterinary_stations s ON s.id = vp.veterinary_station_id
             WHERE vp.user_id = ?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    // Vraća false ako je broj licence već u upotrebi
    public static function updateProfile(int $userId, ?string $licenca, ?string $specijalizacija): bool
    {
        try {
            Database::getConnection()->prepare(
                'UPDATE veterinarian_profiles SET license_number = ?, specialization = ? WHERE user_id = ?'
            )->execute([$licenca, $specijalizacija, $userId]);
            return true;
        } catch (\PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                return false;
            }
            throw $e;
        }
    }
}