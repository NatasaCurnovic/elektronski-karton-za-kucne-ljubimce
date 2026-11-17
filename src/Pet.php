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
}