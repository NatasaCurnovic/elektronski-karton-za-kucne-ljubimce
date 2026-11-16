<?php
namespace App;

use PDO;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function findForLogin(string $email): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT id, first_name, last_name, password_hash, role, is_active, is_email_verified
             FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function isActive(int $id): bool
    {
        $stmt = Database::getConnection()->prepare('SELECT is_active FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    // Aktivira nalog ako je token ispravan i nije istekao
    public static function activate(string $token): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE users
             SET is_email_verified = 1, activation_token = NULL, activation_expires_at = NULL
             WHERE activation_token = ? AND activation_expires_at > ? AND is_email_verified = 0'
        );
        $stmt->execute([$token, date('Y-m-d H:i:s')]);
        return $stmt->rowCount() === 1;
    }

    // Pravi token za reset lozinke (važi 1 sat). Vraća podatke o korisniku ili null.
    public static function createResetToken(string $email): ?array
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT id, first_name, last_name FROM users
             WHERE email = ? AND is_active = 1 AND is_email_verified = 1'
        );
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if (!$u) {
            return null;
        }

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);

        $pdo->prepare('UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?')
            ->execute([$token, $expires, $u['id']]);

        $u['token'] = $token;
        return $u;
    }

    public static function isResetTokenValid(string $token): bool
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT 1 FROM users WHERE reset_token = ? AND reset_expires_at > ?'
        );
        $stmt->execute([$token, date('Y-m-d H:i:s')]);
        return (bool) $stmt->fetchColumn();
    }

    // Postavlja novu lozinku i poništava token (jednokratan je)
    public static function resetPassword(string $token, string $password): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE users
             SET password_hash = ?, reset_token = NULL, reset_expires_at = NULL
             WHERE reset_token = ? AND reset_expires_at > ?'
        );
        $stmt->execute([
            password_hash($password, PASSWORD_BCRYPT),
            $token,
            date('Y-m-d H:i:s'),
        ]);
        return $stmt->rowCount() === 1;
    }

    // Upisuje korisnika i vraća aktivacioni token
    // Upisuje vlasnika i njegovog prvog ljubimca u jednoj transakciji.
    // Vraća aktivacioni token.
    public static function createWithPet(array $u, string $password, array $pet, int $vetId): array
    {
        $pdo     = Database::getConnection();
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 24 * 3600);

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users
                 (first_name, last_name, email, password_hash, phone, address, role,
                  is_active, is_email_verified, activation_token, activation_expires_at)
                 VALUES (?, ?, ?, ?, ?, ?, "OWNER", 1, 0, ?, ?)'
            );
            $stmt->execute([
                $u['first_name'], $u['last_name'], $u['email'],
                password_hash($password, PASSWORD_BCRYPT),
                $u['phone'] ?: null, $u['address'] ?: null,
                $token, $expires,
            ]);
            $ownerId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO pets (owner_id, veterinarian_id, name, species, breed, sex, date_of_birth)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $ownerId, $vetId, $pet['pet_name'], $pet['species'],
                $pet['breed'] ?: null, $pet['sex'], $pet['birth_date'] ?: null,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return ['owner_id' => $ownerId, 'token' => $token];
    }
}