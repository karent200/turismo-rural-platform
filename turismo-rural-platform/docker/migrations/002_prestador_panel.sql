-- Ejecutar si ya tenías la BD creada (phpMyAdmin o mysql client)
-- mysql -u turismo_user -p turismo_db < docker/migrations/002_prestador_panel.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS provider_profiles (
    user_id INT PRIMARY KEY,
    business_name VARCHAR(255) NOT NULL DEFAULT '',
    municipio VARCHAR(255) DEFAULT '',
    descripcion TEXT,
    telefono_contacto VARCHAR(20) DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO provider_profiles (user_id, business_name, municipio, descripcion, telefono_contacto)
SELECT id, 'Finca Vista Andina', 'La Unión', 'Alojamiento campestre y actividades para familias.', '3205550606'
FROM users WHERE email = 'prestador@test.com' LIMIT 1;

INSERT IGNORE INTO services (provider_id, name, type, description, capacity, price, location)
SELECT u.id, 'Casa Campesina Los Arrayanes', 'alojamiento', 'Habitaciones con vista al valle, desayuno incluido.', 5, 95000.00, 'La Unión, Nariño'
FROM users u WHERE u.email = 'prestador@test.com' LIMIT 1;

INSERT IGNORE INTO services (provider_id, name, type, description, capacity, price, location)
SELECT u.id, 'Tour Café de Altura', 'actividades', 'Recorrido por finca cafetera con degustación.', 8, 45000.00, 'Sandoná, Nariño'
FROM users u WHERE u.email = 'prestador@test.com' LIMIT 1;

INSERT IGNORE INTO services (provider_id, name, type, description, capacity, price, location)
SELECT u.id, 'Almuerzo Campesino', 'gastronomia', 'Menú típico nariñense con productos locales.', 20, 28000.00, 'La Unión, Nariño'
FROM users u WHERE u.email = 'prestador@test.com' LIMIT 1;
