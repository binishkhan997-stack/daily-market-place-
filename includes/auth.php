<?php
// includes/auth.php

class Auth {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            return true;
        }
        return false;
    }

    public function register($username, $email, $password, $full_name, $user_type = 'customer') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash, full_name, user_type)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        try {
            return $stmt->execute([$username, $email, $password_hash, $full_name, $user_type]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function logout() {
        session_destroy();
    }

    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        }
        return null;
    }
}
