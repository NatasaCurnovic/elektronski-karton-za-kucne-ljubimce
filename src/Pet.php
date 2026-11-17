<?php
namespace App;

class Pet
{
    public static function forOwner(int $ownerId): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT id, name, species, breed, sex, profile_photo
             FROM pets WHERE owner_id = ? AND is_active = 1 ORDER BY name'
        );
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll();
    }

    // Vraća ljubimca samo ako pripada tom vlasniku (provera na serveru)
    public static function findForOwner(int $id, int $ownerId): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM pets WHERE id = ? AND owner_id = ? AND is_active = 1'
        );
        $stmt->execute([$id, $ownerId]);
        return $stmt->fetch() ?: null;
    }

    public static function microchipTaken(string $chip, int $exceptId = 0): bool
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT 1 FROM pets WHERE microchip_number = ? AND id <> ?'
        );
        $stmt->execute([$chip, $exceptId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function create(int $ownerId, array $d): int
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO pets (owner_id, veterinarian_id, name, species, breed, sex, date_of_birth, microchip_number, profile_photo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $ownerId, $d['veterinarian_id'], $d['name'], $d['species'],
            $d['breed'] ?: null, $d['sex'], $d['birth_date'] ?: null, $d['microchip'] ?: null,
            $d['photo'] ?? null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, int $ownerId, array $d): void
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE pets
             SET veterinarian_id = ?, name = ?, species = ?, breed = ?, sex = ?,
                 date_of_birth = ?, microchip_number = ?, profile_photo = ?
             WHERE id = ? AND owner_id = ? AND is_active = 1'
        );
        $stmt->execute([
            $d['veterinarian_id'], $d['name'], $d['species'], $d['breed'] ?: null,
            $d['sex'], $d['birth_date'] ?: null, $d['microchip'] ?: null, $d['photo'] ?? null,
            $id, $ownerId,
        ]);
    }

    public static function hasUpcomingAppointments(int $id): bool
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT 1 FROM appointments a
             JOIN appointment_slots s ON s.id = a.slot_id
             WHERE a.pet_id = ? AND a.status = "SCHEDULED" AND s.start_time > NOW()
             LIMIT 1'
        );
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }


    public static function deactivate(int $id, int $ownerId): void
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE pets SET is_active = 0 WHERE id = ? AND owner_id = ?'
        );
        $stmt->execute([$id, $ownerId]);
    }
}