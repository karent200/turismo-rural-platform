<?php

require_once __DIR__ . '/../connectdb.php';

class Availability
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    public function getByProvider($provider_id)
    {
        $sql = 'SELECT a.*, s.name as service_name
                FROM availability a
                JOIN services s ON a.service_id = s.id
                WHERE s.provider_id = ?
                AND a.date >= CURDATE()
                ORDER BY a.date ASC, s.name ASC';
        return executeQuery($this->pdo, $sql, [$provider_id])->fetchAll();
    }

    public function getByService($service_id, $provider_id)
    {
        $sql = 'SELECT a.*
                FROM availability a
                JOIN services s ON a.service_id = s.id
                WHERE a.service_id = ? AND s.provider_id = ?
                AND a.date >= CURDATE()
                ORDER BY a.date ASC';
        return executeQuery($this->pdo, $sql, [$service_id, $provider_id])->fetchAll();
    }

    public function serviceBelongsToProvider($service_id, $provider_id)
    {
        $sql = 'SELECT id FROM services WHERE id = ? AND provider_id = ?';
        return (bool) executeQuery($this->pdo, $sql, [$service_id, $provider_id])->fetch();
    }

    public function upsert($service_id, $date, $slots)
    {
        $sql = 'INSERT INTO availability (service_id, date, slots_available)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE slots_available = VALUES(slots_available)';
        executeQuery($this->pdo, $sql, [$service_id, $date, $slots]);
        return $this->pdo->lastInsertId();
    }

    public function delete($id, $provider_id)
    {
        $sql = 'DELETE a FROM availability a
                JOIN services s ON a.service_id = s.id
                WHERE a.id = ? AND s.provider_id = ?';
        $stmt = executeQuery($this->pdo, $sql, [$id, $provider_id]);
        return $stmt->rowCount() > 0;
    }
}
