<?php

class User {
    private $conn;
    private $table = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($fullname, $email, $password) {
        $sql = "INSERT INTO users (fullname, email, password) VALUES (:fullname, :email, :password)";
        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ":fullname" => $fullname,
            ":email" => $email,
            ":password" => password_hash($password, PASSWORD_DEFAULT)
        ]);
    }

    public function login($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":email" => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        return $this->conn->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>