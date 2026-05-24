<?php

require_once __DIR__ . "/../models/User.php";

class UserController {
    private $user;

    public function __construct($db) {
        $this->user = new User($db);
    }

    public function register($fullname, $email, $password, $phone = null) {
        return $this->user->create($fullname, $email, $password, $phone);
    }

    public function login($email) {
        return $this->user->login($email);
    }

    public function authenticate($email, $password) {
        $user = $this->user->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        if (($user['status'] ?? 'active') !== 'active') {
            return false;
        }

        return $user;
    }

    public function getUsers() {
        return $this->user->getAll();
    }
}
?>
