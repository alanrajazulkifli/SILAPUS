<?php
// Controller untuk mengelola data kategori buku.
require_once __DIR__ . '/../models/Category.php';

class CategoryController
{
    private $categoryModel;

    public function __construct()
    {
        if (class_exists('Category')) {
            $this->categoryModel = new Category();
        } else {
            $this->categoryModel = null;
        }
    }

    public function index(): void
    {
        try {
            if (!$this->categoryModel) {
                $this->respond(false, 'Model kategori belum tersedia', null, 500);
                return;
            }

            $categories = $this->categoryModel->getAll();
            $this->respond(true, 'Daftar kategori berhasil diambil', $categories);
        } catch (\Throwable $e) {
            $this->respond(false, 'Terjadi kesalahan pada server: ' . $e->getMessage(), null, 500);
        }
    }

    public function store(array $data): void
    {
        try {
            if (!$this->categoryModel) {
                $this->respond(false, 'Model kategori belum tersedia', null, 500);
                return;
            }

            $nama = trim($data['nama_kategori'] ?? '');
            if ($nama === '') {
                $this->respond(false, 'Nama kategori wajib diisi', null, 422);
                return;
            }

            // Cek duplikat dulu SEBELUM insert, supaya tidak menabrak constraint
            // UNIQUE di database (yang sebelumnya bikin fatal error kalau lolos ke sini).
            if ($this->categoryModel->isNameExists($nama)) {
                $this->respond(false, "Kategori \"$nama\" sudah ada", null, 409);
                return;
            }

            $id = $this->categoryModel->create($nama);
            $this->respond(true, 'Kategori berhasil ditambahkan', ['id' => $id], 201);
        } catch (PDOException $e) {
            // Lapisan pengaman kedua: kalau ada request lain yang nyaris
            // bersamaan berhasil insert nama yang sama duluan (race condition).
            $this->respond(false, "Kategori sudah ada", null, 409);
        } catch (\Throwable $e) {
            $this->respond(false, 'Terjadi kesalahan pada server: ' . $e->getMessage(), null, 500);
        }
    }

    public function update(int $id, array $data): void
    {
        try {
            if (!$this->categoryModel) {
                $this->respond(false, 'Model kategori belum tersedia', null, 500);
                return;
            }

            if (!$this->categoryModel->getById($id)) {
                $this->respond(false, 'Kategori tidak ditemukan', null, 404);
                return;
            }

            $nama = trim($data['nama_kategori'] ?? '');
            if ($nama === '') {
                $this->respond(false, 'Nama kategori wajib diisi', null, 422);
                return;
            }

            if ($this->categoryModel->isNameExists($nama, $id)) {
                $this->respond(false, "Kategori \"$nama\" sudah dipakai kategori lain", null, 409);
                return;
            }

            $this->categoryModel->update($id, $nama);
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
            if (!$this->categoryModel) {
                $this->respond(false, 'Model kategori belum tersedia', null, 500);
                return;
            }

            if (!$this->categoryModel->getById($id)) {
                $this->respond(false, 'Kategori tidak ditemukan', null, 404);
                return;
            }

            $this->categoryModel->delete($id);
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
