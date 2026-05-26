<?php

require_once __DIR__ . '/../connectdb.php';

class ProviderProfile
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    public function getByUserId($user_id)
    {
        try {
            $sql = 'SELECT * FROM provider_profiles WHERE user_id = ?';
            $row = executeQuery($this->pdo, $sql, [$user_id])->fetch();
        } catch (Exception $e) {
            return [
                'user_id' => $user_id,
                'business_name' => '',
                'municipio' => '',
                'descripcion' => '',
                'telefono_contacto' => '',
            ];
        }
        if ($row) {
            return $row;
        }
        return [
            'user_id' => $user_id,
            'business_name' => '',
            'municipio' => '',
            'descripcion' => '',
            'telefono_contacto' => '',
        ];
    }

    public function upsert($user_id, $business_name, $municipio, $descripcion, $telefono)
    {
        $sql = 'INSERT INTO provider_profiles (user_id, business_name, municipio, descripcion, telefono_contacto)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    business_name = VALUES(business_name),
                    municipio = VALUES(municipio),
                    descripcion = VALUES(descripcion),
                    telefono_contacto = VALUES(telefono_contacto)';
        executeQuery($this->pdo, $sql, [$user_id, $business_name, $municipio, $descripcion, $telefono]);
        return true;
    }
}
