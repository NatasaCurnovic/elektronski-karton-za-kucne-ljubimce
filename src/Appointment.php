<?php

namespace App;

use PDO;

class Appointment
{
    public static function upcomingForOwner(int $ownerId, int $limit = 5): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT a.id, s.start_time, p.name AS pet_name, u.first_name, u.last_name
             FROM appointments a
             JOIN appointment_slots s ON s.id = a.slot_id
             JOIN pets p ON p.id = a.pet_id
             JOIN users u ON u.id = a.veterinarian_id
             WHERE a.owner_id = :owner AND a.status = "SCHEDULED" AND s.start_time > NOW()
             ORDER BY s.start_time
             LIMIT :lim'
        );
        $stmt->bindValue(':owner', $ownerId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function upcomingForVet(int $vetId, int $limit = 5): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT a.id, s.start_time, p.name AS pet_name, o.first_name, o.last_name
             FROM appointments a
             JOIN appointment_slots s ON s.id = a.slot_id
             JOIN pets p ON p.id = a.pet_id
             JOIN users o ON o.id = a.owner_id
             WHERE a.veterinarian_id = :vet AND a.status = "SCHEDULED" AND s.start_time > NOW()
             ORDER BY s.start_time
             LIMIT :lim'
        );
        $stmt->bindValue(':vet', $vetId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}