<?php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->loadEnv();
        $this->host = getenv("DB_HOST") ?: "localhost";
        $this->db_name = getenv("DB_NAME") ?: "fashion_storedb";
        $this->username = getenv("DB_USER") ?: "root";
        $this->password = getenv("DB_PASSWORD") ?: "";
    }

    private function loadEnv() {
        $envPath = __DIR__ . "/../../.env";

        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === "" || str_starts_with($line, "#") || !str_contains($line, "=")) {
                continue;
            }

            [$key, $value] = explode("=", $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if (getenv($key) === false) {
                putenv($key . "=" . $value);
                $_ENV[$key] = $value;
            }
        }
    }

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
