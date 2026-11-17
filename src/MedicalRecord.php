<?php
namespace App;

class MedicalRecord
{
    public static function examinations(int $petId): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT e.id, e.examination_date, e.description, e.findings, e.notes,
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
            'SELECT t.id, t.treatment_date, t.title, t.description, t.notes,
                    u.first_name, u.last_name
             FROM treatments t
             JOIN users u ON u.id = t.veterinarian_id
             WHERE t.pet_id = ?
             ORDER BY t.treatment_date DESC, t.id DESC'
        );
        $stmt->execute([$petId]);
        return $stmt->fetchAll();
    }
}