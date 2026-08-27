<?php

require_once __DIR__ . '/../models/buku.php';

class BukuController
{
    private $BukuModel;

    public function __construct()
    {
        $this->BukuModel = new buku();
    }

    public function index(): void
    {
        $search     = $_GET['search'] ?? null;
        $kategori = isset($_GET['kategori_id']) ? (int) $_GET['kategori_id'] : null;

        $buku = $this->BukuModel->getAll($search, $kategori);
        $this->respond(true, 'Daftar buku berhasil diambil', $buku);
    }

    public function show(int $id): void
    {
        $buku = $this->BukuModel->getById($id);

        if (!$buku) {
            $this->respond(false, 'Buku tidak ditemukan', null, 404);
            return;
        }

        $this->respond(true, 'Detail buku berhasil diambil', $buku);
    }

    public function store(array $data): void
    {
        $errors = $this->validate($data);
        if ($errors) {
            $this->respond(false, implode(', ', $errors), null, 422);
            return;
        }

        if ($this->BukuModel->isIsbnExists($data['isbn'])) {
            $this->respond(false, 'ISBN sudah terdaftar', null, 409);
            return;
        }

        $id = $this->BukuModel->create(
            trim($data['judul']),
            trim($data['isbn']),
            (int) $data['kategori_id'],
            (int) $data['stok']
        );

        $this->respond(true, 'Buku berhasil ditambahkan', ['id' => $id], 201);
    }

    public function update(int $id, array $data): void
    {
        if (!$this->BukuModel->getById($id)) {
            $this->respond(false, 'Buku tidak ditemukan', null, 404);
            return;
        }

        $errors = $this->validate($data);
        if ($errors) {
            $this->respond(false, implode(', ', $errors), null, 422);
            return;
        }

        if ($this->BukuModel->isIsbnExists($data['isbn'], $id)) {
            $this->respond(false, 'ISBN sudah dipakai buku lain', null, 409);
            return;
        }

        $this->BukuModel->update(
            $id,
            trim($data['judul']),
            trim($data['isbn']),
            (int) $data['kategori_id'],
            (int) $data['stok']
        );

        $this->respond(true, 'Buku berhasil diperbarui');
    }

    public function destroy(int $id): void
    {
        if (!$this->BukuModel->getById($id)) {
            $this->respond(false, 'Buku tidak ditemukan', null, 404);
            return;
        }

        $this->BukuModel->delete($id);
        $this->respond(true, 'Buku berhasil dihapus');
    }

    private function validate(array $data): array
    {
        $errors = [];

        if (empty(trim($data['judul'] ?? ''))) {
            $errors[] = 'Judul wajib diisi';
        }
        if (empty(trim($data['isbn'] ?? ''))) {
            $errors[] = 'ISBN wajib diisi';
        }
        if (empty($data['kategori_id'])) {
            $errors[] = 'Kategori wajib dipilih';
        }
        if (!isset($data['stok']) || (int) $data['stok'] < 0) {
            $errors[] = 'Stok tidak valid';
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
