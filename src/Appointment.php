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

    // Veterinar ljubimca, samo ako ljubimac pripada tom vlasniku
    public static function vetForPet(int $petId, int $ownerId): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT p.veterinarian_id, u.first_name, u.last_name
             FROM pets p
             JOIN users u ON u.id = p.veterinarian_id
             WHERE p.id = ? AND p.owner_id = ? AND p.is_active = 1'
        );
        $stmt->execute([$petId, $ownerId]);
        return $stmt->fetch() ?: null;
    }

    public static function availableSlots(int $vetId, int $limit = 60): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT id, start_time, end_time
             FROM appointment_slots
             WHERE veterinarian_id = :vet AND status = "AVAILABLE" AND start_time > NOW()
             ORDER BY start_time
             LIMIT :lim'
        );
        $stmt->bindValue(':vet', $vetId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Zakazuje termin. Vraća 'ok', 'taken' (termin zauzet) ili 'invalid'.
    public static function book(int $ownerId, int $petId, int $slotId): string
    {
        $info = self::vetForPet($petId, $ownerId);
        if (!$info) {
            return 'invalid';
        }
        $vetId = (int) $info['veterinarian_id'];

        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();

            // Zauzmi termin samo ako je još slobodan, budući i od veterinara ljubimca
            $stmt = $pdo->prepare(
                'UPDATE appointment_slots SET status = "BOOKED"
                 WHERE id = ? AND veterinarian_id = ? AND status = "AVAILABLE" AND start_time > NOW()'
            );
            $stmt->execute([$slotId, $vetId]);
            if ($stmt->rowCount() !== 1) {
                $pdo->rollBack();
                return 'taken';
            }

            $stmt = $pdo->prepare(
                'INSERT INTO appointments (slot_id, pet_id, owner_id, veterinarian_id)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$slotId, $petId, $ownerId, $vetId]);

            $pdo->commit();
            return 'ok';
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return 'taken';
        }
    }

    // Svi termini vlasnika (za stranicu "Moji termini")
    public static function forOwner(int $ownerId): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT a.id, a.status, a.cancellation_reason, s.start_time, s.end_time,
                    p.name AS pet_name, u.first_name, u.last_name
             FROM appointments a
             JOIN appointment_slots s ON s.id = a.slot_id
             JOIN pets p ON p.id = a.pet_id
             JOIN users u ON u.id = a.veterinarian_id
             WHERE a.owner_id = ?
             ORDER BY s.start_time DESC
             LIMIT 100'
        );
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll();
    }

    // Otkazivanje od strane vlasnika. Vraća 'ok', 'too_late' ili 'invalid'.
    public static function cancelByOwner(int $id, int $ownerId): string
    {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT a.slot_id, a.veterinarian_id, s.start_time, s.end_time
                 FROM appointments a
                 JOIN appointment_slots s ON s.id = a.slot_id
                 WHERE a.id = ? AND a.owner_id = ? AND a.status = "SCHEDULED"
                 FOR UPDATE'
            );
            $stmt->execute([$id, $ownerId]);
            $row = $stmt->fetch();

            if (!$row) {
                $pdo->rollBack();
                return 'invalid';
            }
            // Pravilo: najkasnije 1 sat pre termina
            if (strtotime($row['start_time']) - time() < 3600) {
                $pdo->rollBack();
                return 'too_late';
            }

            $pdo->prepare(
                'UPDATE appointments SET status = "CANCELLED_BY_OWNER", cancelled_at = NOW() WHERE id = ?'
            )->execute([$id]);

            // Stari termin ostaje u istoriji, a veterinaru se vraća nov slobodan termin
            $pdo->prepare('UPDATE appointment_slots SET status = "CANCELLED" WHERE id = ?')
                ->execute([$row['slot_id']]);
            $pdo->prepare(
                'INSERT INTO appointment_slots (veterinarian_id, start_time, end_time) VALUES (?, ?, ?)'
            )->execute([$row['veterinarian_id'], $row['start_time'], $row['end_time']]);

            $pdo->commit();
            return 'ok';
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return 'invalid';
        }
    }

    // Zakazan budući termin tog veterinara, sa podacima o vlasniku (za formu otkazivanja)
    public static function findForVet(int $id, int $vetId): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT a.id, a.owner_id, s.start_time, s.end_time, p.name AS pet_name,
                    o.email AS owner_email, o.first_name AS owner_first, o.last_name AS owner_last
             FROM appointments a
             JOIN appointment_slots s ON s.id = a.slot_id
             JOIN pets p ON p.id = a.pet_id
             JOIN users o ON o.id = a.owner_id
             WHERE a.id = ? AND a.veterinarian_id = ? AND a.status = "SCHEDULED" AND s.start_time > NOW()'
        );
        $stmt->execute([$id, $vetId]);
        return $stmt->fetch() ?: null;
    }

    // Otkazivanje od strane veterinara, uz razlog. Vraća true ako je otkazano.
    public static function cancelByVet(int $id, int $vetId, string $reason): bool
    {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT a.slot_id
                 FROM appointments a
                 JOIN appointment_slots s ON s.id = a.slot_id
                 WHERE a.id = ? AND a.veterinarian_id = ? AND a.status = "SCHEDULED" AND s.start_time > NOW()
                 FOR UPDATE'
            );
            $stmt->execute([$id, $vetId]);
            $row = $stmt->fetch();
            if (!$row) {
                $pdo->rollBack();
                return false;
            }

            $pdo->prepare(
                'UPDATE appointments
                 SET status = "CANCELLED_BY_VETERINARIAN", cancellation_reason = ?, cancelled_at = NOW()
                 WHERE id = ?'
            )->execute([$reason, $id]);

            $pdo->prepare('UPDATE appointment_slots SET status = "CANCELLED" WHERE id = ?')
                ->execute([$row['slot_id']]);

            $pdo->commit();
            return true;
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }
}