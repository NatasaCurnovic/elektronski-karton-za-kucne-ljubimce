<?php

namespace App;

class Slot
{
    // Budući termini veterinara (slobodni i zauzeti), sa imenom ljubimca ako je zauzet
    public static function forVet(int $vetId): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT s.id, s.start_time, s.end_time, s.status, p.name AS pet_name, a.id AS appointment_id
             FROM appointment_slots s
             LEFT JOIN appointments a ON a.slot_id = s.id AND a.status = "SCHEDULED"
             LEFT JOIN pets p ON p.id = a.pet_id
             WHERE s.veterinarian_id = ? AND s.status <> "CANCELLED" AND s.start_time > NOW()
             ORDER BY s.start_time'
        );
        $stmt->execute([$vetId]);
        return $stmt->fetchAll();
    }

    // Da li se novi interval preklapa sa nekim postojećim terminom tog veterinara
    public static function overlaps(int $vetId, string $start, string $end): bool
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT COUNT(*) FROM appointment_slots
             WHERE veterinarian_id = ? AND status <> "CANCELLED"
               AND start_time < ? AND end_time > ?'
        );
        $stmt->execute([$vetId, $end, $start]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(int $vetId, string $start, string $end): void
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO appointment_slots (veterinarian_id, start_time, end_time) VALUES (?, ?, ?)'
        );
        $stmt->execute([$vetId, $start, $end]);
    }

    // Uklanja samo slobodan termin koji pripada tom veterinaru
    public static function removeAvailable(int $id, int $vetId): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE appointment_slots SET status = "CANCELLED"
             WHERE id = ? AND veterinarian_id = ? AND status = "AVAILABLE"'
        );
        $stmt->execute([$id, $vetId]);
        return $stmt->rowCount() > 0;
    }
    //Termin se ne briše iz baze nego dobija status CANCELLED, da nijedna veza (npr. sa starim otkazanim rezervacijama) ne pukne.
}

