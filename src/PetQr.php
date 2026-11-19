<?php

namespace App;

class PetQr
{
    // Da li ljubimac pripada tom vlasniku
    public static function ownedBy(int $petId, int $ownerId): bool
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT COUNT(*) FROM pets WHERE id = ? AND owner_id = ? AND is_active = 1'
        );
        $stmt->execute([$petId, $ownerId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function forPet(int $petId): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT public_token, is_active FROM pet_qr_codes WHERE pet_id = ?'
        );
        $stmt->execute([$petId]);
        return $stmt->fetch() ?: null;
    }

    // Vraća token ljubimca; ako ga nema, pravi nov (nasumičnih 64 heksadecimalna znaka)
    public static function tokenFor(int $petId): string
    {
        $red = self::forPet($petId);
        if ($red) {
            return $red['public_token'];
        }
        $token = bin2hex(random_bytes(32));
        try {
            Database::getConnection()->prepare(
                'INSERT INTO pet_qr_codes (pet_id, public_token) VALUES (?, ?)'
            )->execute([$petId, $token]);
            return $token;
        } catch (\PDOException $e) {
            // Neko je upravo napravio red za istog ljubimca, pročitaj ga
            return self::forPet($petId)['public_token'];
        }
    }

    public static function setActive(int $petId, bool $aktivan): void
    {
        self::tokenFor($petId); // osiguraj da red postoji
        Database::getConnection()->prepare(
            'UPDATE pet_qr_codes SET is_active = ? WHERE pet_id = ?'
        )->execute([$aktivan ? 1 : 0, $petId]);
    }

    // Javni podaci o ljubimcu. Namerno bez ikakvih podataka vlasnika.
    public static function publicPet(string $token): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT p.name, p.species, p.breed, p.sex, p.microchip_number, p.profile_photo,
                    v.first_name AS vet_first, v.last_name AS vet_last, v.email AS vet_email
             FROM pet_qr_codes q
             JOIN pets p ON p.id = q.pet_id
             JOIN users v ON v.id = p.veterinarian_id
             WHERE q.public_token = ? AND q.is_active = 1 AND p.is_active = 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }
}