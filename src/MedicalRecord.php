<?php
namespace App;

class MedicalRecord
{
    public static function examinations(int $petId): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT e.id, e.veterinarian_id, e.examination_date, e.description, e.findings, e.notes,
                    u.first_name, u.last_name
             FROM examinations e
             JOIN users u ON u.id = e.veterinarian_id
             WHERE e.pet_id = ?
             ORDER BY e.examination_date DESC'
        );
        $stmt->execute([$petId]);
        return $stmt->fetchAll();
    }

    public static function treatments(int $petId): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT t.id, t.veterinarian_id, t.title, t.description, t.treatment_date, t.notes,
                    u.first_name, u.last_name
             FROM treatments t
             JOIN users u ON u.id = t.veterinarian_id
             WHERE t.pet_id = ?
             ORDER BY t.treatment_date DESC'
        );
        $stmt->execute([$petId]);
        return $stmt->fetchAll();
    }

    // Vraća pregled samo ako ga je napisao taj veterinar
    public static function findExamination(int $id, int $vetId): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM examinations WHERE id = ? AND veterinarian_id = ?'
        );
        $stmt->execute([$id, $vetId]);
        return $stmt->fetch() ?: null;
    }

    public static function createExamination(int $petId, int $vetId, array $d): void
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO examinations (pet_id, veterinarian_id, examination_date, description, findings, notes)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$petId, $vetId, $d['date'], $d['description'], $d['findings'], $d['notes']]);
    }

//Uslov AND veterinarian_id = ? je zaštita: veterinar ne može da menja tuđe zapise ni ako ručno upiše ID u adresu.
    public static function updateExamination(int $id, int $vetId, array $d): void
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE examinations
             SET examination_date = ?, description = ?, findings = ?, notes = ?
             WHERE id = ? AND veterinarian_id = ?'
        );
        $stmt->execute([$d['date'], $d['description'], $d['findings'], $d['notes'], $id, $vetId]);
    }

    public static function findTreatment(int $id, int $vetId): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM treatments WHERE id = ? AND veterinarian_id = ?'
        );
        $stmt->execute([$id, $vetId]);
        return $stmt->fetch() ?: null;
    }

    public static function createTreatment(int $petId, int $vetId, array $d): void
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO treatments (pet_id, veterinarian_id, title, description, treatment_date, notes)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$petId, $vetId, $d['title'], $d['description'], $d['date'], $d['notes']]);
    }

    public static function updateTreatment(int $id, int $vetId, array $d): void
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE treatments
             SET title = ?, description = ?, treatment_date = ?, notes = ?
             WHERE id = ? AND veterinarian_id = ?'
        );
        $stmt->execute([$d['title'], $d['description'], $d['date'], $d['notes'], $id, $vetId]);
    }
}