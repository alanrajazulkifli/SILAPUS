<?php

require_once __DIR__ . '/../models/Book.php';

class BookController
{
    private $bookModel;

    public function __construct()
    {
        $this->bookModel = new Book();
    }

    public function index(): void
    {
        $search     = $_GET['search'] ?? null;
        $categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;

        $books = $this->bookModel->getAll($search, $categoryId);
        $this->respond(true, 'Daftar buku berhasil diambil', $books);
    }

    public function show(int $id): void
    {
        $book = $this->bookModel->getById($id);

        if (!$book) {
            $this->respond(false, 'Buku tidak ditemukan', null, 404);
            return;
        }

        $this->respond(true, 'Detail buku berhasil diambil', $book);
    }

    public function store(array $data): void
    {
        $errors = $this->validate($data);
        if ($errors) {
            $this->respond(false, implode(', ', $errors), null, 422);
            return;
        }

        if ($this->bookModel->isIsbnExists($data['isbn'])) {
            $this->respond(false, 'ISBN sudah terdaftar', null, 409);
            return;
        }

        $id = $this->bookModel->create(
            trim($data['judul']),
            trim($data['isbn']),
            (int) $data['category_id'],
            (int) $data['stok']
        );

        $this->respond(true, 'Buku berhasil ditambahkan', ['id' => $id], 201);
    }

    public function update(int $id, array $data): void
    {
        if (!$this->bookModel->getById($id)) {
            $this->respond(false, 'Buku tidak ditemukan', null, 404);
            return;
        }

        $errors = $this->validate($data);
        if ($errors) {
            $this->respond(false, implode(', ', $errors), null, 422);
            return;
        }

        if ($this->bookModel->isIsbnExists($data['isbn'], $id)) {
            $this->respond(false, 'ISBN sudah dipakai buku lain', null, 409);
            return;
        }

        $this->bookModel->update(
            $id,
            trim($data['judul']),
            trim($data['isbn']),
            (int) $data['category_id'],
            (int) $data['stok']
        );

        $this->respond(true, 'Buku berhasil diperbarui');
    }

    public function destroy(int $id): void
    {
        if (!$this->bookModel->getById($id)) {
            $this->respond(false, 'Buku tidak ditemukan', null, 404);
            return;
        }

        $this->bookModel->delete($id);
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
        if (empty($data['category_id'])) {
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
