<?php
require_once __DIR__ . '/../../config/db.php';

// LANJUT DI SINI

class Category
{
    private $db;
    private static $fallbackCategories = [
        ['id' => 1, 'nama_kategori' => 'Umum'],
        ['id' => 2, 'nama_kategori' => 'Teknologi'],
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(): array
    {
        if (!$this->db) {
            return self::$fallbackCategories;
        }

        $stmt = $this->db->query("SELECT * FROM categories ORDER BY nama_kategori ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id)
    {
        if (!$this->db) {
            foreach (self::$fallbackCategories as $category) {
                if ((int) $category['id'] === $id) {
                    return $category;
                }
            }
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cek apakah nama kategori sudah dipakai (case-insensitive).
     * Dipanggil sebelum create()/update() supaya tidak menabrak
     * constraint UNIQUE di database (yang kalau lolos akan
     * menyebabkan fatal error PDOException).
     */
    public function isNameExists(string $namaKategori, ?int $excludeId = null): bool
    {
        if (!$this->db) {
            foreach (self::$fallbackCategories as $category) {
                if ((int) $category['id'] === $excludeId) {
                    continue;
                }
                if (strtolower($category['nama_kategori']) === strtolower($namaKategori)) {
                    return true;
                }
            }
            return false;
        }

        $sql = "SELECT id FROM categories WHERE LOWER(nama_kategori) = LOWER(:nama_kategori)";
        $params = ['nama_kategori' => $namaKategori];

        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function create(string $namaKategori): int
    {
        if (!$this->db) {
            $id = count(self::$fallbackCategories) + 1;
            self::$fallbackCategories[] = ['id' => $id, 'nama_kategori' => $namaKategori];
            return $id;
        }

        $stmt = $this->db->prepare("INSERT INTO categories (nama_kategori) VALUES (:nama_kategori)");
        $stmt->execute(['nama_kategori' => $namaKategori]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $namaKategori): bool
    {
        if (!$this->db) {
            foreach (self::$fallbackCategories as &$category) {
                if ((int) $category['id'] === $id) {
                    $category['nama_kategori'] = $namaKategori;
                    return true;
                }
            }
            return false;
        }

        $stmt = $this->db->prepare("UPDATE categories SET nama_kategori = :nama_kategori WHERE id = :id");
        return $stmt->execute(['nama_kategori' => $namaKategori, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        if (!$this->db) {
            self::$fallbackCategories = array_values(array_filter(self::$fallbackCategories, function ($category) use ($id) {
                return (int) $category['id'] !== $id;
            }));
            return true;
        }

        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}