<?php

namespace App;

class VetAccount
{
    // Administrator pravi veterinara. Lozinku niko ne zna, veterinar je postavlja preko linka (važi 48 sati).
    public static function create(array $u, int $stationId, ?string $licenca, ?string $specijalizacija): array
    {
        $pdo     = Database::getConnection();
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 48 * 3600);

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO users
                 (first_name, last_name, email, password_hash, phone, role,
                  is_active, is_email_verified, reset_token, reset_expires_at)
                 VALUES (?, ?, ?, ?, ?, "VETERINARIAN", 1, 1, ?, ?)'
            )->execute([
                $u['first_name'], $u['last_name'], $u['email'],
                password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
                $u['phone'] ?: null,
                $token, $expires,
            ]);
            $id = (int) $pdo->lastInsertId();

            $pdo->prepare(
                'INSERT INTO veterinarian_profiles (user_id, veterinary_station_id, license_number, specialization)
                 VALUES (?, ?, ?, ?)'
            )->execute([$id, $stationId, $licenca, $specijalizacija]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return ['id' => $id, 'token' => $token];
    }
}
