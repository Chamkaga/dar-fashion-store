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

    public function updateProfileFull($id, $fullname, $email, $phone = null, $shipping_address = null, $billing_address = null) {
        $sql = "UPDATE users SET fullname = :fullname, email = :email, phone = :phone, shipping_address = :shipping, billing_address = :billing WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":fullname" => $fullname,
            ":email" => $email,
            ":phone" => $phone,
            ":shipping" => $shipping_address,
            ":billing" => $billing_address,
            ":id" => $id
        ]);
    }

    public function updatePassword($id, $newPassword) {
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":password" => password_hash($newPassword, PASSWORD_DEFAULT),
            ":id" => $id
        ]);
    }

    public function updateProfilePicture($id, $path) {
        $sql = "UPDATE users SET profile_picture = :path WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":path" => $path,
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
