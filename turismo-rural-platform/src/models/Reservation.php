<?php
// models/Reservation.php

require_once __DIR__ . '/../connectdb.php';

class Reservation {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    public function create($tourist_id, $service_id, $date, $personas = 1, $telefono = '') {
        $sql = "INSERT INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status) VALUES (?, ?, ?, ?, ?, 'pendiente')";
        executeQuery($this->pdo, $sql, [$tourist_id, $service_id, $date, $personas, $telefono]);
        
        $sqlUpdate = "UPDATE availability SET slots_available = slots_available - ? WHERE service_id = ? AND date = ? AND slots_available > 0";
        executeQuery($this->pdo, $sqlUpdate, [$personas, $service_id, $date]);
        
        return $this->pdo->lastInsertId();
    }

    public function getByUser($user_id, $status = null) {
        $sql = "SELECT r.*, s.name as service_name, s.type as service_type, s.price as service_price 
                FROM reservations r 
                JOIN services s ON r.service_id = s.id 
                WHERE r.tourist_id = ?";
        $params = [$user_id];
        
        if ($status) {
            $sql .= " AND r.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY r.reservation_date DESC";
        
        $stmt = executeQuery($this->pdo, $sql, $params);
        return $stmt->fetchAll();
    }

    public function getAll() {
        $sql = "SELECT r.*, s.name as service_name, s.price as service_price, u.name as tourist_name 
                FROM reservations r 
                JOIN services s ON r.service_id = s.id 
                JOIN users u ON r.tourist_id = u.id
                ORDER BY r.created_at DESC";
        
        $stmt = executeQuery($this->pdo, $sql);
        return $stmt->fetchAll();
    }

    public function getByProvider($provider_id) {
        $sql = "SELECT r.*, s.name as service_name, s.price as service_price, u.name as tourist_name, u.email as tourist_email
                FROM reservations r 
                JOIN services s ON r.service_id = s.id 
                JOIN users u ON r.tourist_id = u.id
                WHERE s.provider_id = ?
                ORDER BY r.created_at DESC";
        
        $stmt = executeQuery($this->pdo, $sql, [$provider_id]);
        return $stmt->fetchAll();
    }

    public function getPendingByProvider($provider_id) {
        $sql = "SELECT r.*, s.name as service_name, u.name as tourist_name, u.email as tourist_email
                FROM reservations r 
                JOIN services s ON r.service_id = s.id 
                JOIN users u ON r.tourist_id = u.id
                WHERE s.provider_id = ? AND r.status = 'pendiente'
                ORDER BY r.reservation_date ASC";
        
        $stmt = executeQuery($this->pdo, $sql, [$provider_id]);
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $sql = "SELECT r.*, s.provider_id, s.name as service_name
                FROM reservations r
                JOIN services s ON r.service_id = s.id
                WHERE r.id = ?";
        return executeQuery($this->pdo, $sql, [$id])->fetch();
    }

    public function userOwnsReservation($reservation_id, $user_id) {
        $r = $this->findById($reservation_id);
        return $r && (int) $r['tourist_id'] === (int) $user_id;
    }

    public function providerOwnsReservation($reservation_id, $provider_id) {
        $r = $this->findById($reservation_id);
        return $r && (int) $r['provider_id'] === (int) $provider_id;
    }

    public function updateStatus($id, $status) {
        $sql = "UPDATE reservations SET status = ? WHERE id = ?";
        executeQuery($this->pdo, $sql, [$status, $id]);
    }
}
?>