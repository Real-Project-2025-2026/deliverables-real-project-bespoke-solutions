-- =====================================================
-- Muniverse - Script de Base de Datos
-- =====================================================
-- INSTRUCCIONES:
-- 1. Crea una base de datos llamada "muniverse_db" en phpMyAdmin
-- 2. Selecciona la base de datos
-- 3. Ve a la pestaña "SQL" y pega todo este contenido
-- 4. Haz clic en "Continuar" para ejecutar
-- =====================================================

-- Crear base de datos (opcional, si no la has creado manualmente)
-- CREATE DATABASE IF NOT EXISTS muniverse_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE muniverse_db;

-- =====================================================
-- TABLA: user_roles (tipos de usuario)
-- =====================================================
DROP TABLE IF EXISTS user_availability;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS event_participants;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS users;

CREATE TABLE user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar roles
INSERT INTO user_roles (role_name, description) VALUES
('normal', 'Usuario normal que puede crear y unirse a eventos'),
('business', 'Usuario de negocio que puede ofrecer espacios patrocinados');

-- =====================================================
-- TABLA: users
-- Almacena los usuarios registrados
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL DEFAULT 1,
    onboarding_completed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES user_roles(id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: user_preferences
-- Preferencias de categorías del usuario
-- =====================================================
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category ENUM('culture', 'food', 'games', 'language', 'sports') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category (user_id, category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: user_availability
-- Disponibilidad semanal del usuario
-- =====================================================
CREATE TABLE user_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    day_of_week TINYINT NOT NULL, -- 0=Lunes, 1=Martes, ..., 6=Domingo
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_day (user_id, day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: events
-- Almacena los eventos/actividades
-- =====================================================
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    category ENUM('culture', 'food', 'games', 'language', 'sports') DEFAULT 'sports',
    description TEXT,
    location VARCHAR(255),
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    max_participants INT DEFAULT NULL,
    participants INT DEFAULT 0,
    is_sponsored TINYINT(1) DEFAULT 0, -- 1 si es un espacio ofrecido por business
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_event_date (event_date),
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    INDEX idx_sponsored (is_sponsored)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: event_participants
-- Relación entre usuarios y eventos (inscripciones)
-- =====================================================
CREATE TABLE event_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participation (event_id, user_id),
    INDEX idx_event (event_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATOS DE EJEMPLO
-- =====================================================

-- Usuarios de prueba (contraseñas: demo123)
-- Usuario 1: Normal con onboarding completo
-- Usuario 2-4: Normales con onboarding completo
-- Usuario 5: Business
INSERT INTO users (name, email, password, role_id, onboarding_completed, created_at) VALUES
('Usuario Demo', 'demo@muniverse.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, NOW()),
('Carlos García', 'carlos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, NOW()),
('María López', 'maria@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, NOW()),
('Pablo Martínez', 'pablo@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, NOW()),
('Café Central Madrid', 'cafeteria@business.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, NOW()),
('Club Deportivo Norte', 'club@business.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, NOW());

-- Preferencias de usuarios
INSERT INTO user_preferences (user_id, category) VALUES
(1, 'sports'), (1, 'culture'), (1, 'food'),
(2, 'sports'), (2, 'games'),
(3, 'culture'), (3, 'language'), (3, 'food'),
(4, 'sports'), (4, 'games'), (4, 'culture');

-- Disponibilidad de usuarios (ejemplo)
INSERT INTO user_availability (user_id, day_of_week, start_time, end_time) VALUES
-- Usuario Demo: disponible L-V tarde y S mañana
(1, 0, '18:00:00', '21:00:00'),
(1, 1, '18:00:00', '21:00:00'),
(1, 2, '18:00:00', '21:00:00'),
(1, 3, '18:00:00', '21:00:00'),
(1, 4, '17:00:00', '22:00:00'),
(1, 5, '09:00:00', '14:00:00'),
-- Carlos: disponible L-M-J tarde
(2, 0, '19:00:00', '21:00:00'),
(2, 1, '19:00:00', '21:00:00'),
(2, 3, '19:00:00', '21:00:00'),
-- María: disponible fines de semana
(3, 5, '10:00:00', '18:00:00'),
(3, 6, '10:00:00', '18:00:00');

-- Eventos de ejemplo (variados por categoría)
INSERT INTO events (user_id, title, category, description, location, event_date, event_time, max_participants, participants, is_sponsored, created_at) VALUES
-- Eventos de usuarios normales
(1, 'Running matutino por el parque', 'sports', 'Salida de running para todos los niveles. Traer agua y ropa cómoda.', 'Parque del Retiro, Madrid', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:00:00', 15, 3, 0, NOW()),
(1, 'Club de lectura: Novela contemporánea', 'culture', 'Comentaremos la última novela del mes. Ambiente tranquilo y café incluido.', 'Biblioteca Municipal', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '18:00:00', 12, 5, 0, NOW()),
(2, 'Torneo de juegos de mesa', 'games', 'Traed vuestros juegos favoritos. Tendremos Catan, Carcassonne y más.', 'Centro Cultural Norte', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '17:00:00', 20, 8, 0, NOW()),
(2, 'Intercambio de idiomas ES-EN', 'language', 'Practica inglés con nativos. Todos los niveles bienvenidos.', 'Irish Pub Downtown', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:00:00', 30, 12, 0, NOW()),
(3, 'Ruta gastronómica por Lavapiés', 'food', 'Descubre los mejores restaurantes del barrio. Tapas variadas.', 'Metro Lavapiés', DATE_ADD(CURDATE(), INTERVAL 4 DAY), '13:00:00', 10, 6, 0, NOW()),
(3, 'Visita guiada: Museo del Prado', 'culture', 'Tour guiado por las obras maestras. Entrada incluida.', 'Museo del Prado', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '11:00:00', 15, 9, 0, NOW()),
(4, 'Partido de pádel amateur', 'sports', 'Buscamos jugadores nivel intermedio. Traed raqueta.', 'Club Padel Centro', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '19:00:00', 4, 2, 0, NOW()),
(4, 'Noche de rol: D&D', 'games', 'Campaña nueva, principiantes bienvenidos. El máster proporciona material.', 'Tienda de Comics Sol', DATE_ADD(CURDATE(), INTERVAL 6 DAY), '18:00:00', 6, 4, 0, NOW()),

-- Espacios patrocinados (de usuarios business)
(5, 'Espacio para quedadas: Café Central', 'food', 'Ofrecemos nuestro espacio para quedadas. Descuento del 10% para grupos de Muniverse.', 'Café Central, Calle Mayor 15', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', NULL, 0, 1, NOW()),
(5, 'Taller de Latte Art', 'food', 'Aprende a hacer arte en el café. Incluye materiales y consumición.', 'Café Central, Calle Mayor 15', DATE_ADD(CURDATE(), INTERVAL 8 DAY), '17:00:00', 8, 3, 1, NOW()),
(6, 'Espacio deportivo: Club Norte', 'sports', 'Canchas disponibles para reservas grupales. Descuentos especiales.', 'Club Deportivo Norte', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', NULL, 0, 1, NOW()),
(6, 'Clase grupal de yoga', 'sports', 'Sesión de yoga para grupos. Instructor incluido.', 'Club Deportivo Norte - Sala 2', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00:00', 20, 7, 1, NOW());

-- Inscripciones de ejemplo
INSERT INTO event_participants (event_id, user_id, joined_at) VALUES
(1, 2, NOW()), (1, 3, NOW()), (1, 4, NOW()),
(2, 2, NOW()), (2, 3, NOW()), (2, 4, NOW()),
(3, 1, NOW()), (3, 3, NOW()), (3, 4, NOW()),
(4, 1, NOW()), (4, 3, NOW()), (4, 4, NOW()),
(5, 1, NOW()), (5, 2, NOW()), (5, 4, NOW()),
(6, 1, NOW()), (6, 2, NOW()), (6, 4, NOW()),
(7, 1, NOW()), (7, 3, NOW()),
(8, 1, NOW()), (8, 2, NOW()), (8, 3, NOW()),
(10, 1, NOW()), (10, 2, NOW()), (10, 3, NOW()),
(12, 1, NOW()), (12, 2, NOW()), (12, 3, NOW()), (12, 4, NOW());

-- =====================================================
-- NOTAS IMPORTANTES
-- =====================================================
-- 1. Todas las contraseñas de prueba son: demo123
-- 2. Usuario demo normal: demo@muniverse.com / demo123
-- 3. Usuario demo business: cafeteria@business.com / demo123
-- 4. Los eventos tienen fechas relativas a la fecha actual
-- 5. Puedes modificar config/db.php con tus credenciales de MySQL
