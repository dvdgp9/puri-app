-- Migración UP: email de coordinadores y outbox de observaciones
-- Fecha: 2026-08-12
-- IMPORTANTE: ejecutar primero en pruebas y con copia de seguridad.

ALTER TABLE admins
    ADD COLUMN email VARCHAR(254) NULL AFTER apellidos,
    ADD INDEX idx_admins_email (email);

CREATE TABLE notificacion_observacion_eventos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    observacion_id INT NULL,
    actividad_id INT NOT NULL,
    centro_id INT NOT NULL,
    fecha_observacion DATE NOT NULL,
    contenido_hash CHAR(64) NOT NULL,
    observacion_snapshot TEXT NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    cuerpo_texto MEDIUMTEXT NOT NULL,
    destinatarios_total INT NOT NULL DEFAULT 0,
    estado ENUM('pendiente', 'enviado', 'parcial', 'fallido', 'sin_destinatarios') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_notificacion_evento_observacion
        FOREIGN KEY (observacion_id)
        REFERENCES observaciones(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_notificacion_evento_actividad
        FOREIGN KEY (actividad_id)
        REFERENCES actividades(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_notificacion_evento_centro
        FOREIGN KEY (centro_id)
        REFERENCES centros(id)
        ON DELETE CASCADE,

    INDEX idx_notificacion_evento_estado (estado, created_at),
    INDEX idx_notificacion_evento_observacion (actividad_id, fecha_observacion, created_at),
    INDEX idx_notificacion_evento_hash (contenido_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notificacion_observacion_destinatarios (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    evento_id BIGINT NOT NULL,
    admin_id INT NULL,
    destinatario_email VARCHAR(254) NOT NULL,
    estado ENUM('pendiente', 'enviado', 'fallido') NOT NULL DEFAULT 'pendiente',
    intentos SMALLINT NOT NULL DEFAULT 0,
    proximo_intento_at DATETIME NULL,
    enviado_at DATETIME NULL,
    ultimo_error VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_notificacion_destinatario_intentos
        CHECK (intentos >= 0),
    CONSTRAINT fk_notificacion_destinatario_evento
        FOREIGN KEY (evento_id)
        REFERENCES notificacion_observacion_eventos(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_notificacion_destinatario_admin
        FOREIGN KEY (admin_id)
        REFERENCES admins(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_notificacion_destinatario_evento_email (evento_id, destinatario_email),
    INDEX idx_notificacion_destinatario_reintento (estado, proximo_intento_at, intentos)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

