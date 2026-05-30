<?php

require_once __DIR__ . '/../bootstrap.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host = app_env('DB_HOST', 'localhost');
        $this->db_name = app_env('DB_NAME', 'fashion_storedb');
        $this->username = app_env('DB_USER', 'root');
        $this->password = app_env('DB_PASSWORD', 'Chamkaga@2025');
    }

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4',
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            error_log('Database Connection Failed: ' . $e->getMessage());
        }

        return $this->conn;
    }
}
