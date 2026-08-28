<?php

require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../../config/db.php';

class UserController{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /** Menampilkan semua user (Khusus Admin) */
    public function index(): void
    {
        $users = $this->userModel->getAll();
        $this->respond(true, 'Daftar pengguna berhasil diambil', $users);
    }

    /** Menampilkan detail user berdasarkan ID */
    public function show(int $id): void
    {
        $user = $this->userModel->getById($id);

        if (!$user) {
            $this->respond(false, 'Pengguna tidak ditemukan', null, 404);
            return;
        }

        // Hapus password dari respon JSON demi keamanan
        unset($user['password']);

        $this->respond(true, 'Detail pengguna berhasil diambil', $user);
    }

    /** Registrasi / Tambah User Baru */
    public function store(array $data): void
    {
        $nama = trim($data['nama'] ?? '');
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'siswa'; // Default sesuai DB: 'siswa'

        // Validasi input wajib
        if (empty($nama) || empty($username) || empty($password)) {
            $this->respond(false, 'Nama, username, dan password wajib diisi', null, 422);
            return;
        }

        // Validasi enum role
        if (!in_array($role, ['admin', 'siswa'])) {
            $this->respond(false, 'Role tidak valid (pilih admin atau siswa)', null, 422);
            return;
        }

        // Cek keunikan username
        if ($this->userModel->getByUsername($username)) {
            $this->respond(false, 'Username sudah digunakan', null, 409);
            return;
        }

        // Hash password sebelum disimpan
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $userId = $this->userModel->create([
            'nama' => $nama,
            'username' => $username,
            'password' => $hashedPassword,
            'role' => $role
        ]);

        if (!$userId) {
            $this->respond(false, 'Gagal menambahkan pengguna', null, 500);
            return;
        }

        $this->respond(true, 'Pengguna berhasil ditambahkan', ['id' => $userId], 201);
    }

    /** Update Data User */
    public function update(int $id, array $data): void
    {
        $existingUser = $this->userModel->getById($id);
        if (!$existingUser) {
            $this->respond(false, 'Pengguna tidak ditemukan', null, 404);
            return;
        }

        $nama = trim($data['nama'] ?? $existingUser['nama']);
        $username = trim($data['username'] ?? $existingUser['username']);
        $role = $data['role'] ?? $existingUser['role'];

        if (!in_array($role, ['admin', 'siswa'])) {
            $this->respond(false, 'Role tidak valid', null, 422);
            return;
        }

        // Jika username diubah, pastikan tidak duplikat
        if ($username !== $existingUser['username'] && $this->userModel->getByUsername($username)) {
            $this->respond(false, 'Username sudah digunakan oleh pengguna lain', null, 409);
            return;
        }

        // Jika password diisi, update hash-nya. Jika kosong, pakai yang lama.
        $password = !empty($data['password']) 
            ? password_hash($data['password'], PASSWORD_BCRYPT) 
            : $existingUser['password'];

        $updated = $this->userModel->update($id, [
            'nama' => $nama,
            'username' => $username,
            'password' => $password,
            'role' => $role
        ]);

        if (!$updated) {
            $this->respond(false, 'Gagal memperbarui pengguna', null, 500);
            return;
        }

        $this->respond(true, 'Data pengguna berhasil diperbarui');
    }

    /** Hapus User */
    public function destroy(int $id): void
    {
        $user = $this->userModel->getById($id);
        if (!$user) {
            $this->respond(false, 'Pengguna tidak ditemukan', null, 404);
            return;
        }

        if (!$this->userModel->delete($id)) {
            $this->respond(false, 'Gagal menghapus pengguna', null, 500);
            return;
        }

        $this->respond(true, 'Pengguna berhasil dihapus');
    }

    /** Helper Format Respon JSON */
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