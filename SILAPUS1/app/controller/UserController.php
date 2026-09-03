<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Loan.php';

class UserController
{
    private $userModel;
    private $loanModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->loanModel = new Loan();
    }

    /** Daftar anggota (khusus admin), bisa difilter search & role */
    public function index(): void
    {
        $search = $_GET['search'] ?? null;
        $role   = $_GET['role'] ?? null;

        $users = $this->userModel->getAll($search, $role);
        $this->respond(true, 'Daftar anggota berhasil diambil', $users);
    }

    public function store(array $data): void
    {
        $errors = $this->validate($data, false);
        if ($errors) {
            $this->respond(false, implode(', ', $errors), null, 422);
            return;
        }

        $username = trim($data['username']);
        if ($this->userModel->isUsernameExists($username)) {
            $this->respond(false, 'Username sudah digunakan', null, 409);
            return;
        }

        $id = $this->userModel->create(
            trim($data['nama']),
            $username,
            trim($data['password']),
            $data['role']
        );

        $this->respond(true, 'Anggota berhasil ditambahkan', ['id' => $id], 201);
    }

    public function update(int $id, array $data): void
    {
        if (!$this->userModel->findById($id)) {
            $this->respond(false, 'Anggota tidak ditemukan', null, 404);
            return;
        }

        $errors = $this->validate($data, true);
        if ($errors) {
            $this->respond(false, implode(', ', $errors), null, 422);
            return;
        }

        $username = trim($data['username']);
        if ($this->userModel->isUsernameExists($username, $id)) {
            $this->respond(false, 'Username sudah dipakai anggota lain', null, 409);
            return;
        }

        $password = trim($data['password'] ?? '');

        $this->userModel->update(
            $id,
            trim($data['nama']),
            $username,
            $data['role'],
            $password !== '' ? $password : null
        );

        $this->respond(true, 'Anggota berhasil diperbarui');
    }

    /** Hapus anggota. currentUserId dipakai agar admin tidak bisa hapus akunnya sendiri. */
    public function destroy(int $id, int $currentUserId): void
    {
        if (!$this->userModel->findById($id)) {
            $this->respond(false, 'Anggota tidak ditemukan', null, 404);
            return;
        }

        if ($id === $currentUserId) {
            $this->respond(false, 'Tidak bisa menghapus akun sendiri', null, 409);
            return;
        }

        $activeLoans = $this->loanModel->countActiveByUser($id);
        if ($activeLoans > 0) {
            $this->respond(false, 'Anggota masih memiliki peminjaman aktif, tidak bisa dihapus', null, 409);
            return;
        }

        $this->userModel->delete($id);
        $this->respond(true, 'Anggota berhasil dihapus');
    }

    private function validate(array $data, bool $isUpdate): array
    {
        $errors = [];

        if (empty(trim($data['nama'] ?? ''))) {
            $errors[] = 'Nama wajib diisi';
        }
        if (empty(trim($data['username'] ?? ''))) {
            $errors[] = 'Username wajib diisi';
        }
        if (!$isUpdate && empty(trim($data['password'] ?? ''))) {
            $errors[] = 'Password wajib diisi';
        }
        if (empty($data['role']) || !in_array($data['role'], ['admin', 'siswa'], true)) {
            $errors[] = 'Role wajib dipilih (admin/siswa)';
        }

        return $errors;
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
