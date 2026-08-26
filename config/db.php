<?php

class Database {
    private static $instance = null;

    public static function getConnection()
    {
        if (self::$instance === null) {
            $host   = 'localhost';
            $dbname = 'db_silapus';
            $user   = 'root';
            $pass   = 'rpl12345';

            try {
                self::$instance = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (PDOException $e) {
                die('Koneksi database gagal: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
?>