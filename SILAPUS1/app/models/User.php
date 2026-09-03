<?php

require_once __DIR__ . '/../../config/Database.php';

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * @return array|false
     */
    public function findByUsername(string $username)
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    public function verifyPassword(string $password, string $storedPassword): bool
    {
        if (password_verify($password, $storedPassword)) {
            return true;
        }

        return hash_equals($storedPassword, $password);
    }

    public function isLegacyPlainPassword(string $storedPassword, string $password): bool
    {
        $info = password_get_info($storedPassword);
        return ($info['algo'] === false) && hash_equals($storedPassword, $password);
    }

    public function updatePasswordHash(int $id, string $password): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password = :password WHERE id = :id');
        return $stmt->execute([
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'id'       => $id,
        ]);
    }

    /**
     * @return array|false
     */
    public function findById(int $id)
    {
        $stmt = $this->db->prepare('SELECT id, nama, username, role, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create(string $nama, string $username, string $password, string $role = 'siswa'): int
    {
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            'INSERT INTO users (nama, username, password, role) VALUES (:nama, :username, :password, :role)'
        );
        $stmt->execute([
            'nama'     => $nama,
            'username' => $username,
            'password' => $hashed,
            'role'     => $role,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Daftar semua anggota (admin & siswa) untuk halaman Data Anggota,
     * dengan pencarian nama/username dan filter role opsional.
     */
    public function getAll(?string $search = null, ?string $role = null): array
    {
        $sql = 'SELECT id, nama, username, role, created_at FROM users';
        $conditions = [];
        $params = [];

        if ($search) {
            $conditions[] = '(nama LIKE :search OR username LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        if ($role) {
            $conditions[] = 'role = :role';
            $params['role'] = $role;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY nama ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function isUsernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE username = :username';
        $params = ['username' => $username];

        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    /**
     * Update data anggota. Password bersifat opsional - kalau dikosongkan,
     * password lama tetap dipakai (tidak ditimpa).
     */
    public function update(int $id, string $nama, string $username, string $role, ?string $password = null): bool
    {
        if ($password) {
            $stmt = $this->db->prepare(
                'UPDATE users SET nama = :nama, username = :username, role = :role, password = :password WHERE id = :id'
            );
            return $stmt->execute([
                'nama'     => $nama,
                'username' => $username,
                'role'     => $role,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'id'       => $id,
            ]);
        }

        $stmt = $this->db->prepare(
            'UPDATE users SET nama = :nama, username = :username, role = :role WHERE id = :id'
        );
        return $stmt->execute([
            'nama'     => $nama,
            'username' => $username,
            'role'     => $role,
            'id'       => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
