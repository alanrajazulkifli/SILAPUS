<?php

require_once __DIR__ . '/Jwt.php';

/**
 * Class Auth
 * Middleware sederhana untuk memvalidasi JWT dan role user
 * sebelum request diteruskan ke controller.
 */
class Auth
{
    /**
     * Memastikan request punya token JWT yang valid.
     * Menghentikan request (401) jika tidak valid.
     * Mengembalikan payload user (id, username, role) jika valid.
     */
    public static function check(): array
    {
        $token = Jwt::getBearerToken();

        if (!$token) {
            self::unauthorized('Token tidak ditemukan');
        }

        $payload = Jwt::decode($token);

        if (!$payload) {
            self::unauthorized('Token tidak valid atau kedaluwarsa');
        }

        return $payload;
    }

    /**
     * Memastikan user yang login punya role tertentu (misalnya 'admin').
     */
    public static function checkRole(string $requiredRole): array
    {
        $payload = self::check();

        if (($payload['role'] ?? '') !== $requiredRole) {
            http_response_code(403);
            die(json_encode([
                'success' => false,
                'message' => 'Akses ditolak, khusus untuk role ' . $requiredRole,
            ]));
        }

        return $payload;
    }

    private static function unauthorized(string $message): void
    {
        http_response_code(401);
        die(json_encode([
            'success' => false,
            'message' => $message,
        ]));
    }
}
