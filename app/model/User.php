<?php

require_once __DIR__ . '/../../config/db.php';

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
        $stmt = $this->db->prepare('SELECT * FROM tb_users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    /**
     * @return array|false
     */
    public function findById(int $id)
    {
        $stmt = $this->db->prepare('SELECT id, nama, username, role, created_at FROM tb_users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create(string $nama, string $username, string $password, string $role = 'siswa'): int
    {
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            'INSERT INTO tb_users (nama, username, password, role) VALUES (:nama, :username, :password, :role)'
        );
        $stmt->execute([
            'nama'     => $nama,
            'username' => $username,
            'password' => $hashed,
            'role'     => $role,
        ]);

        return (int) $this->db->lastInsertId();
    }
}