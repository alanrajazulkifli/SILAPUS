<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../../config/Jwt.php';

class AuthController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function login(array $data): void
    {
        try {
            $username = trim($data['username'] ?? '');
            $password = trim($data['password'] ?? '');

            if ($username === '' || $password === '') {
                $this->respond(false, 'Username dan kata sandi wajib diisi', null, 422);
                return;
            }

            $user = $this->userModel->findByUsername($username);
            $passwordMatches = $user ? $this->userModel->verifyPassword($password, $user['password']) : false;

            if (!$user || !$passwordMatches) {
                $this->respond(false, 'Username atau kata sandi salah', null, 401);
                return;
            }

            if ($this->userModel->isLegacyPlainPassword($user['password'], $password)) {
                $this->userModel->updatePasswordHash((int) $user['id'], $password);
            }

            $token = Jwt::encode([
                'id'       => $user['id'],
                'username' => $user['username'],
                'role'     => $user['role'],
            ]);

            $this->respond(true, 'Login berhasil', [
                'token' => $token,
                'user'  => [
                    'id'       => $user['id'],
                    'nama'     => $user['nama'],
                    'username' => $user['username'],
                    'role'     => $user['role'],
                ],
            ]);
        } catch (\Throwable $e) {
            // \Throwable menangkap SEMUA jenis error - baik Exception biasa
            // (mis. PDOException karena tabel belum ada) MAUPUN Error/Fatal
            // (mis. manggil method di variabel null karena koneksi DB gagal).
            // Tanpa ini, error semacam itu keluar sebagai HTML mentah dan
            // merusak response JSON yang diharapkan frontend.
            $this->respond(
                false,
                'Terjadi kesalahan pada server: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function register(array $data): void
    {
        try {
            $nama     = trim($data['nama'] ?? '');
            $username = trim($data['username'] ?? '');
            $password = trim($data['password'] ?? '');
            $role     = $data['role'] ?? 'siswa';

            if ($nama === '' || $username === '' || $password === '') {
                $this->respond(false, 'Semua field wajib diisi', null, 422);
                return;
            }

            if ($this->userModel->findByUsername($username)) {
                $this->respond(false, 'Username sudah digunakan', null, 409);
                return;
            }

            $id = $this->userModel->create($nama, $username, $password, $role);
            $this->respond(true, 'Akun berhasil dibuat', ['id' => $id], 201);
        } catch (\Throwable $e) {
            $this->respond(
                false,
                'Terjadi kesalahan pada server: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    private function respond(bool $success, string $message, $data = null, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ]);
    }
}
