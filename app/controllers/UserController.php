<?php

require_once __DIR__ . "/../models/User.php";

class UserController {
    private $user;

    public function __construct($db) {
        $this->user = new User($db);
    }

    public function register($fullname, $email, $password) {
        return $this->user->create($fullname, $email, $password);
    }

    public function login($email) {
        return $this->user->login($email);
    }

    public function getUsers() {
        return $this->user->getAll();
    }
}
?>