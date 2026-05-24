<?php

class Database {
    private $host = "localhost";
    private $db_name = "fashion_storedb";
    private $username = "root";
    private $password = "Chamkaga@2025";
    public $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );

            // show errors (important for development)
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            error_log("Database Connection Failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}
?>
