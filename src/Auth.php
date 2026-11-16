<?php
namespace App;

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
            session_start();
        }
    }

    public static function user(): ?array
    {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function login(array $u): void
    {
        self::start();
        session_regenerate_id(true); // protiv krađe sesije
        $_SESSION['user'] = [
            'id'         => (int) $u['id'],
            'first_name' => $u['first_name'],
            'last_name'  => $u['last_name'],
            'role'       => $u['role'],
        ];
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }

    public static function homeFor(string $role): string
    {
        return match ($role) {
            'ADMIN'        => 'admin.php',
            'VETERINARIAN' => 'vet.php',
            default        => 'dashboard.php',
        };
    }

    // Provera na serveru: mora biti prijavljen, tražene uloge i nalog mora biti aktivan
    public static function requireRole(array $roles): array
    {
        $u = self::user();
        if (!$u || !User::isActive($u['id'])) {
            self::logout();
            header('Location: login.php');
            exit;
        }
        if (!in_array($u['role'], $roles, true)) {
            http_response_code(403);
            die('Nemate ovlašćenje za ovu stranicu.');
        }
        return $u;
    }
}