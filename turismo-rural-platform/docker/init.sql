-- init.sql: Base de datos MySQL UTF-8
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'prestador', 'turista') DEFAULT 'turista',
    telefono VARCHAR(20) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT,
    name VARCHAR(255) NOT NULL,
    type ENUM('alojamiento', 'recreacion', 'gastronomia', 'actividades') NOT NULL,
    description TEXT,
    capacity INT DEFAULT 1,
    price DECIMAL(10,2),
    location VARCHAR(255),
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT,
    date DATE NOT NULL,
    slots_available INT DEFAULT 10,
    UNIQUE KEY unique_service_date (service_id, date),
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tourist_id INT,
    service_id INT,
    reservation_date DATE NOT NULL,
    personas INT DEFAULT 1,
    telefono VARCHAR(20) DEFAULT '',
    status ENUM('pendiente', 'confirmada', 'cancelada') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tourist_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provider_profiles (
    user_id INT PRIMARY KEY,
    business_name VARCHAR(255) NOT NULL DEFAULT '',
    municipio VARCHAR(255) DEFAULT '',
    descripcion TEXT,
    telefono_contacto VARCHAR(20) DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos usuarios
INSERT IGNORE INTO users (name, email, password, role) VALUES 
('Admin Principal', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('María González', 'maria@glamping.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'prestador'),
('Carlos Pérez', 'carlos@finca.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'prestador'),
('Ana López', 'ana@turista.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'turista'),
('José Hernández', 'jose@turista.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'turista'),
('Prestador Demo', 'prestador@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'prestador'),
('Turista Demo', 'turista@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'turista'),
('alejo', 'alejo@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'prestador'),
('karent', 'karent@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'turista');

-- Servicios
INSERT IGNORE INTO services (provider_id, name, type, description, capacity, price, location) VALUES 
(2, 'Glamping Raíz de Nariño', 'alojamiento', 'Alojamiento en domos ecológicos con vista al río Güejía.', 4, 180.00, 'Verde Pasto, Nariño'),
(2, 'Cabaña El Retiro', 'alojamiento', 'Cabaña tradicional nariñense con chimenea.', 6, 120.00, 'La Unión, Nariño'),
(2, 'Eco Lodge Montaña', 'alojamiento', 'Experiencia única en plena selva andina.', 2, 250.00, 'Pastasso, Nariño'),
(3, 'Piscina Natural Río Azul', 'recreacion', 'Baño en piscina natural con agua cristalina.', 30, 15.00, 'El Encano, Nariño'),
(3, 'Laguna de la Cocha', 'recreacion', 'Paseo en lancha por la laguna.', 20, 25.00, 'La Laguna, Nariño'),
(3, 'Senderismo Páramo', 'actividades', 'Caminata guiada por el páramo.', 15, 35.00, 'Páramo de Barbillas'),
(2, 'Restaurante Dueño de Casa', 'gastronomia', 'Comida nariñense auténtica.', 40, 20.00, 'Centro, Pasto'),
(3, 'Fonda Campestre', 'gastronomia', 'Comidas típicas de la región.', 25, 18.00, 'La Unión, Nariño'),
(2, 'Baño de Selva', 'actividades', 'Spa natural con plantas medicinales.', 10, 45.00, 'Verde Pasto, Nariño'),
(3, 'Café Tour Nariño', 'actividades', 'Tour por plantaciones de café.', 12, 30.00, 'Ancuya, Nariño');

-- Perfiles de prestadores
INSERT IGNORE INTO provider_profiles (user_id, business_name, municipio, descripcion, telefono_contacto) VALUES
(2, 'Raíz Glamping Nariño', 'Pasto', 'Experiencias de ecoturismo y alojamiento rural en el sur colombiano.', '3175550101'),
(3, 'Aventuras El Encano', 'El Encano', 'Recreación, gastronomía y tours por la Laguna de la Cocha.', '3185550202'),
(6, 'Finca Vista Andina', 'La Unión', 'Alojamiento campestre y actividades para familias. Ideal para fines de semana.', '3205550606');

-- Servicios del prestador demo (prestador@test.com, id 6)
INSERT IGNORE INTO services (provider_id, name, type, description, capacity, price, location) VALUES
(6, 'Casa Campesina Los Arrayanes', 'alojamiento', 'Habitaciones con vista al valle, desayuno incluido.', 5, 95000.00, 'La Unión, Nariño'),
(6, 'Tour Café de Altura', 'actividades', 'Recorrido por finca cafetera con degustación.', 8, 45000.00, 'Sandoná, Nariño'),
(6, 'Almuerzo Campesino', 'gastronomia', 'Menú típico nariñense con productos locales.', 20, 28000.00, 'La Unión, Nariño');

-- Reservas de ejemplo (prestador demo)
INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-06-10', 2, '3001234567', 'pendiente'
FROM users t, services s WHERE t.email = 'turista@test.com' AND s.name = 'Casa Campesina Los Arrayanes' LIMIT 1;

INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-06-15', 4, '3009876543', 'pendiente'
FROM users t, services s WHERE t.email = 'turista@test.com' AND s.name = 'Casa Campesina Los Arrayanes' LIMIT 1;

INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-06-20', 3, '3105551234', 'confirmada'
FROM users t, services s WHERE t.email = 'ana@turista.co' AND s.name = 'Tour Café de Altura' LIMIT 1;

INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-06-22', 6, '3155559876', 'confirmada'
FROM users t, services s WHERE t.email = 'jose@turista.co' AND s.name = 'Almuerzo Campesino' LIMIT 1;

-- Disponibilidades (fechas futuras)
INSERT IGNORE INTO availability (service_id, date, slots_available) VALUES 
(1, '2026-06-10', 4), (1, '2026-06-11', 4), (1, '2026-06-12', 3),
(2, '2026-06-10', 6), (2, '2026-06-15', 5), (3, '2026-06-20', 2),
(4, '2026-06-18', 30), (5, '2026-06-25', 20);

INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-10', 5 FROM services s WHERE s.name = 'Casa Campesina Los Arrayanes' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-15', 4 FROM services s WHERE s.name = 'Casa Campesina Los Arrayanes' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-18', 8 FROM services s WHERE s.name = 'Tour Café de Altura' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-12', 20 FROM services s WHERE s.name = 'Almuerzo Campesino' LIMIT 1;

-- Servicios adicionales para prestador demo (id=6)
INSERT IGNORE INTO services (provider_id, name, type, description, capacity, price, location) VALUES
(6, 'Cabaña El Mirador', 'alojamiento', 'Cabaña con vista panorámica a la laguna', 6, 120000.00, 'El Encano, Nariño'),
(6, 'Paseo en Canoa', 'recreacion', 'Recorrido en canoa por la laguna', 10, 35000.00, 'Laguna de la Cocha'),
(6, 'Cata de Café', 'gastronomia', 'Degustación de café de altura nariñense', 12, 25000.00, 'Sandoná, Nariño'),
(6, 'Cabalgata Ecológica', 'actividades', 'Cabalgata guiada por senderos ecológicos', 8, 55000.00, 'La Unión, Nariño'),
(6, 'Cabaña "El Refugio" - Nuestra Señora de la Merced', 'alojamiento', 'Cabaña con chimenea y vista a la montaña - única en su estilo', 6, 150000.00, 'Ipiales, Nariño');

-- Servicio para alejo
INSERT IGNORE INTO services (provider_id, name, type, description, capacity, price, location) VALUES
(8, 'Hospedaje Finca El Encanto', 'alojamiento', 'Hospedaje rural con desayuno incluido', 4, 80000.00, 'Tangua, Nariño');

-- Reservas adicionales para turista demo (id=7)
INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-07-01', 3, '3001234567', 'pendiente'
FROM users t, services s WHERE t.email = 'turista@test.com' AND s.name = 'Cabaña El Mirador' LIMIT 1;

INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-06-28', 5, '3001234567', 'pendiente'
FROM users t, services s WHERE t.email = 'turista@test.com' AND s.name = 'Paseo en Canoa' LIMIT 1;

INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-07-05', 2, '3001234567', 'pendiente'
FROM users t, services s WHERE t.email = 'turista@test.com' AND s.name = 'Cata de Café' LIMIT 1;

INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-06-20', 4, '3001234567', 'pendiente'
FROM users t, services s WHERE t.email = 'turista@test.com' AND s.name = 'Cabalgata Ecológica' LIMIT 1;

INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-06-12', 2, '3001234567', 'confirmada'
FROM users t, services s WHERE t.email = 'turista@test.com' AND s.name = 'Casa Campesina Los Arrayanes' LIMIT 1;

INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-07-10', 6, '3001234567', 'confirmada'
FROM users t, services s WHERE t.email = 'turista@test.com' AND s.name = 'Tour Café de Altura' LIMIT 1;

-- Reservas de otros turistas
INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-06-18', 2, '3157894561', 'confirmada'
FROM users t, services s WHERE t.email = 'karent@gmail.com' AND s.name = 'Casa Campesina Los Arrayanes' LIMIT 1;

INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-06-25', 8, '3157894561', 'pendiente'
FROM users t, services s WHERE t.email = 'karent@gmail.com' AND s.name = 'Almuerzo Campesino' LIMIT 1;

INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-07-15', 4, '3157894561', 'pendiente'
FROM users t, services s WHERE t.email = 'karent@gmail.com' AND s.name = 'Cabaña El Mirador' LIMIT 1;

INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-07-02', 5, '3101112233', 'confirmada'
FROM users t, services s WHERE t.email = 'ana@turista.co' AND s.name = 'Almuerzo Campesino' LIMIT 1;

INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-07-20', 3, '3204455667', 'pendiente'
FROM users t, services s WHERE t.email = 'jose@turista.co' AND s.name = 'Casa Campesina Los Arrayanes' LIMIT 1;

INSERT IGNORE INTO reservations (tourist_id, service_id, reservation_date, personas, telefono, status)
SELECT t.id, s.id, '2026-06-30', 2, '3204455667', 'pendiente'
FROM users t, services s WHERE t.email = 'jose@turista.co' AND s.name = 'Cabalgata Ecológica' LIMIT 1;

-- Disponibilidades adicionales
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-10', 6 FROM services s WHERE s.name = 'Cabaña El Mirador' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-01', 6 FROM services s WHERE s.name = 'Cabaña El Mirador' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-15', 6 FROM services s WHERE s.name = 'Cabaña El Mirador' LIMIT 1;

INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-28', 10 FROM services s WHERE s.name = 'Paseo en Canoa' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-08', 10 FROM services s WHERE s.name = 'Paseo en Canoa' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-20', 10 FROM services s WHERE s.name = 'Paseo en Canoa' LIMIT 1;

INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-05', 12 FROM services s WHERE s.name = 'Cata de Café' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-12', 12 FROM services s WHERE s.name = 'Cata de Café' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-25', 12 FROM services s WHERE s.name = 'Cata de Café' LIMIT 1;

INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-20', 8 FROM services s WHERE s.name = 'Cabalgata Ecológica' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-30', 8 FROM services s WHERE s.name = 'Cabalgata Ecológica' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-10', 8 FROM services s WHERE s.name = 'Cabalgata Ecológica' LIMIT 1;

INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-01', 4 FROM services s WHERE s.name = 'Hospedaje Finca El Encanto' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-15', 4 FROM services s WHERE s.name = 'Hospedaje Finca El Encanto' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-08-01', 4 FROM services s WHERE s.name = 'Hospedaje Finca El Encanto' LIMIT 1;

-- Más disponibilidades para servicios existentes
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-12', 5 FROM services s WHERE s.name = 'Casa Campesina Los Arrayanes' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-18', 5 FROM services s WHERE s.name = 'Casa Campesina Los Arrayanes' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-20', 5 FROM services s WHERE s.name = 'Casa Campesina Los Arrayanes' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-20', 5 FROM services s WHERE s.name = 'Casa Campesina Los Arrayanes' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-25', 5 FROM services s WHERE s.name = 'Casa Campesina Los Arrayanes' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-08-01', 5 FROM services s WHERE s.name = 'Casa Campesina Los Arrayanes' LIMIT 1;

INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-05', 8 FROM services s WHERE s.name = 'Tour Café de Altura' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-10', 8 FROM services s WHERE s.name = 'Tour Café de Altura' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-18', 8 FROM services s WHERE s.name = 'Tour Café de Altura' LIMIT 1;

INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-12', 20 FROM services s WHERE s.name = 'Almuerzo Campesino' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-25', 20 FROM services s WHERE s.name = 'Almuerzo Campesino' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-02', 20 FROM services s WHERE s.name = 'Almuerzo Campesino' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-18', 20 FROM services s WHERE s.name = 'Almuerzo Campesino' LIMIT 1;

INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-15', 4 FROM services s WHERE s.name = 'Glamping Raíz de Nariño' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-20', 4 FROM services s WHERE s.name = 'Glamping Raíz de Nariño' LIMIT 1;

INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-06-20', 6 FROM services s WHERE s.name = 'Cabaña El Retiro' LIMIT 1;

INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-05', 30 FROM services s WHERE s.name = 'Piscina Natural Río Azul' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-12', 30 FROM services s WHERE s.name = 'Piscina Natural Río Azul' LIMIT 1;

INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-01', 20 FROM services s WHERE s.name = 'Laguna de la Cocha' LIMIT 1;
INSERT IGNORE INTO availability (service_id, date, slots_available)
SELECT s.id, '2026-07-10', 20 FROM services s WHERE s.name = 'Laguna de la Cocha' LIMIT 1;

CREATE INDEX idx_services_type ON services(type);
CREATE INDEX idx_reservations_status ON reservations(status);
CREATE INDEX idx_availability_service_date ON availability(service_id, date);