<?php

class User {
    private $conn;
    private $table = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($fullname, $email, $password, $phone = null, $role = 'customer') {
        $sql = "
            INSERT INTO users (fullname, email, phone, password, role)
            VALUES (:fullname, :email, :phone, :password, :role)
        ";
        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ":fullname" => $fullname,
            ":email" => $email,
            ":phone" => $phone,
            ":password" => password_hash($password, PASSWORD_DEFAULT),
            ":role" => $role
        ]);
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":email" => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $sql = "SELECT id, fullname, email, phone, role, status, created_at FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":id" => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateProfile($id, $fullname, $phone) {
        $sql = "UPDATE users SET fullname = :fullname, phone = :phone WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":fullname" => $fullname,
            ":phone" => $phone,
            ":id" => $id
        ]);
    }

    public function login($email) {
        return $this->findByEmail($email);
    }

    public function getAll() {
        return $this->conn->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
