<?php
require_once __DIR__ . '/../../config/db.php';

// LANJUT DI SINI

class Kategori
{
    private $db;
    private static $fallbackKategori = [
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
            return self::$fallbackKategori;
        }

        $stmt = $this->db->query("SELECT * FROM tb_kategori ORDER BY nama_kategori ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id)
    {
        if (!$this->db) {
            foreach (self::$fallbackKategori as $kategori) {
                if ((int) $kategori['id'] === $id) {
                    return $kategori;
                }
            }
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM tb_kategori WHERE id = :id");
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
            foreach (self::$fallbackKategori as $kategori) {
                if ((int) $kategori['id'] === $excludeId) {
                    continue;
                }
                if (strtolower($kategori['nama_kategori']) === strtolower($namaKategori)) {
                    return true;
                }
            }
            return false;
        }

        $sql = "SELECT id FROM tb_kategori WHERE LOWER(nama_kategori) = LOWER(:nama_kategori)";
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
            $id = count(self::$fallbackKategori) + 1;
            self::$fallbackKategori[] = ['id' => $id, 'nama_kategori' => $namaKategori];
            return $id;
        }

        $stmt = $this->db->prepare("INSERT INTO tb_kategori (nama_kategori) VALUES (:nama_kategori)");
        $stmt->execute(['nama_kategori' => $namaKategori]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $namaKategori): bool
    {
        if (!$this->db) {
            foreach (self::$fallbackKategori as &$kategori) {
                if ((int) $kategori['id'] === $id) {
                    $kategori['nama_kategori'] = $namaKategori;
                    return true;
                }
            }
            return false;
        }

        $stmt = $this->db->prepare("UPDATE tb_kategori SET nama_kategori = :nama_kategori WHERE id = :id");
        return $stmt->execute(['nama_kategori' => $namaKategori, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        if (!$this->db) {
            self::$fallbackKategori = array_values(array_filter(self::$fallbackKategori, function ($kategori) use ($id) {
                return (int) $kategori['id'] !== $id;
            }));
            return true;
        }

        $stmt = $this->db->prepare("DELETE FROM tb_kategori WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}