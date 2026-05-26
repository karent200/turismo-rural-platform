<?php
// models/Service.php

require_once __DIR__ . '/../connectdb.php';

class Service {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    public function create($provider_id, $name, $type, $description, $capacity, $price, $location) {
        $sql = "INSERT INTO services (provider_id, name, type, description, capacity, price, location) VALUES (?, ?, ?, ?, ?, ?, ?)";
        executeQuery($this->pdo, $sql, [$provider_id, $name, $type, $description, $capacity, $price, $location]);
        return $this->pdo->lastInsertId();
    }

    public function getAll($type = null, $location = null, $provider_id = null) {
        $sql = "SELECT s.*, u.name as provider_name, pp.business_name, pp.municipio, pp.descripcion, pp.telefono_contacto,
                       COALESCE(rv.avg_rating, 0) as avg_rating, COALESCE(rv.total_reviews, 0) as total_reviews
                FROM services s
                LEFT JOIN users u ON s.provider_id = u.id
                LEFT JOIN provider_profiles pp ON s.provider_id = pp.user_id
                LEFT JOIN (
                    SELECT service_id, ROUND(AVG(rating), 1) as avg_rating, COUNT(*) as total_reviews
                    FROM reviews
                    GROUP BY service_id
                ) rv ON s.id = rv.service_id";
        $params = [];
        $conditions = [];
        
        if ($type) {
            $conditions[] = "s.type = ?";
            $params[] = $type;
        }
        if ($location) {
            $conditions[] = "s.location LIKE ?";
            $params[] = '%' . $location . '%';
        }
        if ($provider_id) {
            $conditions[] = "s.provider_id = ?";
            $params[] = $provider_id;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY s.created_at DESC";
        
        $stmt = executeQuery($this->pdo, $sql, $params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $sql = "SELECT s.*, u.name as provider_name, pp.business_name, pp.municipio, pp.descripcion, pp.telefono_contacto,
                       COALESCE(rv.avg_rating, 0) as avg_rating, COALESCE(rv.total_reviews, 0) as total_reviews
                FROM services s
                LEFT JOIN users u ON s.provider_id = u.id
                LEFT JOIN provider_profiles pp ON s.provider_id = pp.user_id
                LEFT JOIN (
                    SELECT service_id, ROUND(AVG(rating), 1) as avg_rating, COUNT(*) as total_reviews
                    FROM reviews
                    GROUP BY service_id
                ) rv ON s.id = rv.service_id
                WHERE s.id = ?";
        return executeQuery($this->pdo, $sql, [$id])->fetch();
    }

    public function delete($id) {
        $sql = "DELETE FROM services WHERE id = ?";
        $stmt = executeQuery($this->pdo, $sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function update($id, $name, $type, $description, $capacity, $price, $location) {
        $sql = "UPDATE services SET name=?, type=?, description=?, capacity=?, price=?, location=? WHERE id=?";
        $stmt = executeQuery($this->pdo, $sql, [$name, $type, $description, $capacity, $price, $location, $id]);
        return $stmt->rowCount() > 0;
    }
}
?>