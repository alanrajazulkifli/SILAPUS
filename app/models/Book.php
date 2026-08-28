<?php

require_once __DIR__ . '/../../config/Database.php';

class Book
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function baseQuery(): string
    {
        return 'SELECT books.*, categories.nama_kategori
                FROM books
                JOIN categories ON books.category_id = categories.id';
    }

    public function getAll(?string $search = null, ?int $categoryId = null): array
    {
        $sql = $this->baseQuery();
        $params = [];
        $conditions = [];

        if ($search) {
            $conditions[] = '(books.judul LIKE :search OR books.isbn LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($categoryId) {
            $conditions[] = 'books.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY books.judul ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * @return array|false
     */
    public function getById(int $id)
    {
        $stmt = $this->db->prepare($this->baseQuery() . ' WHERE books.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Ambil data buku dengan row lock (FOR UPDATE).
     * Dipakai di dalam transaksi peminjaman agar stok tidak berkurang ganda
     * kalau ada 2 siswa mengajukan pinjam buku yang sama secara bersamaan.
     *
     * @return array|false
     */
    public function getByIdForUpdate(int $id)
    {
        $stmt = $this->db->prepare('SELECT * FROM books WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function isIsbnExists(string $isbn, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM books WHERE isbn = :isbn';
        $params = ['isbn' => $isbn];

        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function create(string $judul, string $isbn, int $categoryId, int $stok): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO books (judul, isbn, category_id, stok) VALUES (:judul, :isbn, :category_id, :stok)'
        );
        $stmt->execute([
            'judul'       => $judul,
            'isbn'        => $isbn,
            'category_id' => $categoryId,
            'stok'        => $stok,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $judul, string $isbn, int $categoryId, int $stok): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE books SET judul = :judul, isbn = :isbn, category_id = :category_id, stok = :stok WHERE id = :id'
        );
        return $stmt->execute([
            'judul'       => $judul,
            'isbn'        => $isbn,
            'category_id' => $categoryId,
            'stok'        => $stok,
            'id'          => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM books WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function decreaseStock(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE books SET stok = stok - 1 WHERE id = :id AND stok > 0');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0; // pastikan baris benar-benar berkurang, bukan cuma query sukses
    }

    public function increaseStock(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE books SET stok = stok + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
