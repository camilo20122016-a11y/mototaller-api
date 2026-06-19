-- ============================================================
-- Script SQL: Base de datos mototaller_api
-- Evidencia: GA7-220501096-AA5-EV01
-- Autor: Camilo Rios — SENA ADSO
-- ============================================================

CREATE DATABASE IF NOT EXISTS mototaller_api
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE mototaller_api;

-- ============================================================
-- Tabla: usuarios
-- Almacena credenciales de acceso al sistema MotoTaller
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id               INT          NOT NULL AUTO_INCREMENT,
    nombre           VARCHAR(100) NOT NULL,
    email            VARCHAR(100) NOT NULL UNIQUE,
    password_hash    VARCHAR(255) NOT NULL,  -- Contraseña encriptada con bcrypt
    rol              ENUM('admin','tecnico','recepcion') NOT NULL DEFAULT 'tecnico',
    activo           TINYINT(1)   NOT NULL DEFAULT 1,
    fecha_registro   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso    TIMESTAMP    NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB CHARACTER SET utf8mb4;

-- Usuario de prueba (contraseña: Admin123*)
INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES
('Administrador', 'admin@mototaller.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
