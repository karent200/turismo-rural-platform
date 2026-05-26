<?php
require_once __DIR__ . '/../connectdb.php';

class Review {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    public function create($service_id, $tourist_id, $reservation_id, $rating, $comment = '') {
        $sql = "INSERT INTO reviews (service_id, tourist_id, reservation_id, rating, comment) VALUES (?, ?, ?, ?, ?)";
        executeQuery($this->pdo, $sql, [$service_id, $tourist_id, $reservation_id, $rating, $comment]);
        return $this->pdo->lastInsertId();
    }

    public function getByService($service_id) {
        $sql = "SELECT r.*, u.name as tourist_name FROM reviews r JOIN users u ON r.tourist_id = u.id WHERE r.service_id = ? ORDER BY r.created_at DESC";
        $stmt = executeQuery($this->pdo, $sql, [$service_id]);
        return $stmt->fetchAll();
    }

    public function getAverageByService($service_id) {
        $sql = "SELECT COUNT(*) as total, COALESCE(ROUND(AVG(rating), 1), 0) as average FROM reviews WHERE service_id = ?";
        $stmt = executeQuery($this->pdo, $sql, [$service_id]);
        return $stmt->fetch();
    }

    public function hasReviewed($tourist_id, $reservation_id) {
        $sql = "SELECT id FROM reviews WHERE tourist_id = ? AND reservation_id = ?";
        $stmt = executeQuery($this->pdo, $sql, [$tourist_id, $reservation_id]);
        return (bool) $stmt->fetch();
    }

    public function getByProvider($provider_id) {
        $sql = "SELECT rv.*, u.name as tourist_name, s.name as service_name
                FROM reviews rv
                JOIN services s ON rv.service_id = s.id
                JOIN users u ON rv.tourist_id = u.id
                WHERE s.provider_id = ?
                ORDER BY rv.created_at DESC";
        $stmt = executeQuery($this->pdo, $sql, [$provider_id]);
        return $stmt->fetchAll();
    }

    public function getAverageByProvider($provider_id) {
        $sql = "SELECT s.id, s.name, COUNT(rv.id) as total, COALESCE(ROUND(AVG(rv.rating), 1), 0) as average
                FROM services s
                LEFT JOIN reviews rv ON rv.service_id = s.id
                WHERE s.provider_id = ?
                GROUP BY s.id, s.name
                ORDER BY s.name";
        $stmt = executeQuery($this->pdo, $sql, [$provider_id]);
        return $stmt->fetchAll();
    }

    public function getAll() {
        $sql = "SELECT rv.*, u.name as tourist_name, s.name as service_name, s.provider_id
                FROM reviews rv
                JOIN services s ON rv.service_id = s.id
                JOIN users u ON rv.tourist_id = u.id
                ORDER BY rv.created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function delete($id) {
        $sql = "DELETE FROM reviews WHERE id = ?";
        executeQuery($this->pdo, $sql, [$id]);
    }
}
