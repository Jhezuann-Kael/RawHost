<?php
class Database
{
    private $conn;

    public function __construct()
    {
        // Create DB if not exists
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`");
        } catch (PDOException $e) {
            die("DB Connection Error: " . $e->getMessage());
        }
    }

    public function connect()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET time_zone = '-04:00'");
        } catch (PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        return $this->conn;
    }
}
