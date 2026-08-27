<?php
// Controller untuk mengelola data kategori buku.
require_once __DIR__ . '/../models/Kategori.php';

class KategoriController
{
    private $KategoriModel;

    public function __construct()
    {
        if (class_exists('Kategori')) {
            $this->KategoriModel = new Kategori();
        } else {
            $this->KategoriModel = null;
        }
    }

    public function index(): void
    {
        try {
            if (!$this->KategoriModel) {
                $this->respond(false, 'Model kategori belum tersedia', null, 500);
                return;
            }

            $kategori = $this->KategoriModel->getAll();
            $this->respond(true, 'Daftar kategori berhasil diambil', $kategori);
        } catch (\Throwable $e) {
            $this->respond(false, 'Terjadi kesalahan pada server: ' . $e->getMessage(), null, 500);
        }
    }

    public function store(array $data): void
    {
        try {
            if (!$this->KategoriModel) {
                $this->respond(false, 'Model kategori belum tersedia', null, 500);
                return;
            }

            $nama = trim($data['nama_kategori'] ?? '');
            if ($nama === '') {
                $this->respond(false, 'Nama kategori wajib diisi', null, 422);
                return;
            }

            if ($this->KategoriModel->isNameExists($nama)) {
                $this->respond(false, "Kategori \"$nama\" sudah ada", null, 409);
                return;
            }

            $id = $this->KategoriModel->create($nama);
            $this->respond(true, 'Kategori berhasil ditambahkan', ['id' => $id], 201);
        } catch (PDOException $e) {
        
        
            $this->respond(false, "Kategori sudah ada", null, 409);
        } catch (\Throwable $e) {
            $this->respond(false, 'Terjadi kesalahan pada server: ' . $e->getMessage(), null, 500);
        }
    }

    public function update(int $id, array $data): void
    {
        try {
            if (!$this->KategoriModel) {
                $this->respond(false, 'Model kategori belum tersedia', null, 500);
                return;
            }

            if (!$this->KategoriModel->getById($id)) {
                $this->respond(false, 'Kategori tidak ditemukan', null, 404);
                return;
            }

            $nama = trim($data['nama_kategori'] ?? '');
            if ($nama === '') {
                $this->respond(false, 'Nama kategori wajib diisi', null, 422);
                return;
            }

            if ($this->KategoriModel->isNameExists($nama, $id)) {
                $this->respond(false, "Kategori \"$nama\" sudah dipakai kategori lain", null, 409);
                return;
            }

            $this->KategoriModel->update($id, $nama);
            $this->respond(true, 'Kategori berhasil diperbarui');
        } catch (PDOException $e) {
            $this->respond(false, "Kategori sudah dipakai kategori lain", null, 409);
        } catch (\Throwable $e) {
            $this->respond(false, 'Terjadi kesalahan pada server: ' . $e->getMessage(), null, 500);
        }
    }

    public function destroy(int $id): void
    {
        try {
            if (!$this->KategoriModel) {
                $this->respond(false, 'Model kategori belum tersedia', null, 500);
                return;
            }

            if (!$this->KategoriModel->getById($id)) {
                $this->respond(false, 'Kategori tidak ditemukan', null, 404);
                return;
            }

            $this->KategoriModel->delete($id);
            $this->respond(true, 'Kategori berhasil dihapus');
        } catch (PDOException $e) {
            $this->respond(false, 'Kategori masih dipakai oleh buku lain, tidak bisa dihapus', null, 409);
        } catch (\Throwable $e) {
            $this->respond(false, 'Terjadi kesalahan pada server: ' . $e->getMessage(), null, 500);
        }
    }

    private function respond(bool $success, string $message, $data = null, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
