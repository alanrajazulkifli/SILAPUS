<?php

require_once __DIR__ . '/../../config/db.php';

class Buku
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function baseQuery(): string
    {
        return 'SELECT buku.*, kategori.nama_kategori
                FROM buku
                JOIN kategori ON buku.kategori_id = kategori.id';
    }

    public function getAll(?string $search = null, ?int $kategoriId = null): array
    {
        $sql = $this->baseQuery();
        $params = [];
        $conditions = [];

        if ($search) {
            $conditions[] = '(buku.judul LIKE :search OR buku.isbn LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($kategoriId) {
            $conditions[] = 'buku.kategori_id = :kategori_id';
            $params['kategori_id'] = $kategoriId;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY buku.judul ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * @return array|false
     */
    public function getById(int $id)
    {
        $stmt = $this->db->prepare($this->baseQuery() . ' WHERE buku.id = :id');
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
        $stmt = $this->db->prepare('SELECT * FROM buku WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function isIsbnExists(string $isbn, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM buku WHERE isbn = :isbn';
        $params = ['isbn' => $isbn];

        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function create(string $judul, string $isbn, int $kategoriId, int $stok): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO buku (judul, isbn, kategori_id, stok) VALUES (:judul, :isbn, :kategori_id, :stok)'
        );
        $stmt->execute([
            'judul'       => $judul,
            'isbn'        => $isbn,
            'kategori_id' => $kategoriId,
            'stok'        => $stok,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $judul, string $isbn, int $kategoriId, int $stok): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE buku SET judul = :judul, isbn = :isbn, kategori_id = :kategori_id, stok = :stok WHERE id = :id'
        );
        return $stmt->execute([
            'judul'       => $judul,
            'isbn'        => $isbn,
            'kategori_id' => $kategoriId,
            'stok'        => $stok,
            'id'          => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM buku WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function decreaseStock(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE buku SET stok = stok - 1 WHERE id = :id AND stok > 0');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0; // pastikan baris benar-benar berkurang, bukan cuma query sukses
    }

    public function increaseStock(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE buku SET stok = stok + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}