<?php
class Database
{
    private static $instance = null;
    private static $host = 'localhost';
    private static $username = 'root';
    private static $password = 'rpl12345';
    private static $dbName = 'perpus_kelompok4';
    private static $charset = 'utf8mb4';

    public function __construct()
    {
        // mencegah instansi langsung
    }

    public static function getConnection(): ?PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $configs = [
            ['host' => self::$host, 'username' => self::$username, 'password' => self::$password],
            ['host' => self::$host, 'username' => 'root', 'password' => ''],
            ['host' => '127.0.0.1', 'username' => self::$username, 'password' => self::$password],
            ['host' => '127.0.0.1', 'username' => 'root', 'password' => ''],
        ];

        foreach ($configs as $config) {
            try {
                $serverDsn = 'mysql:host=' . $config['host'] . ';charset=' . self::$charset;
                $serverPdo = new PDO($serverDsn, $config['username'], $config['password'], $options);
                $serverPdo->exec('CREATE DATABASE IF NOT EXISTS ' . self::$dbName);

                $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . self::$dbName . ';charset=' . self::$charset;
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $options);
                self::$host = $config['host'];
                self::$username = $config['username'];
                self::$password = $config['password'];

                return self::$instance;
            } catch (PDOException $e) {
                // lanjut ke konfigurasi berikutnya
            }
        }

        return null;
    }
}
