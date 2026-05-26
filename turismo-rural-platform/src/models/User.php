<?php
// models/User.php

require_once __DIR__ . '/../connectdb.php';

class User {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        return executeQuery($this->pdo, $sql, [$email])->fetch();
    }

    public function emailExists($email) {
        return (bool) $this->findByEmail($email);
    }

    public function findById($id) {
        $sql = "SELECT id, name, email, role, telefono, created_at FROM users WHERE id = ?";
        return executeQuery($this->pdo, $sql, [$id])->fetch();
    }

    public function create($name, $email, $password, $role = 'turista', $telefono = '') {
        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $sql = "INSERT INTO users (name, email, password, role, telefono) VALUES (?, ?, ?, ?, ?)";
        executeQuery($this->pdo, $sql, [$name, $email, $hashed, $role, $telefono]);
        return $this->pdo->lastInsertId();
    }

    public function getAll($role = null) {
        $sql = "SELECT id, name, email, role, created_at FROM users";
        if ($role) $sql .= " WHERE role = ?";
        $params = $role ? [$role] : [];
        $stmt = executeQuery($this->pdo, $sql, $params);
        return $stmt->fetchAll();
    }

    public function delete($id) {
        $sql = "DELETE FROM users WHERE id = ?";
        executeQuery($this->pdo, $sql, [$id]);
    }

    public function update($id, $name, $email, $telefono = '') {
        $sql = "UPDATE users SET name = ?, email = ?, telefono = ? WHERE id = ?";
        executeQuery($this->pdo, $sql, [$name, $email, $telefono, $id]);
    }

    public function changePassword($id, $newPassword) {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        executeQuery($this->pdo, $sql, [$hashed, $id]);
    }
}
?>