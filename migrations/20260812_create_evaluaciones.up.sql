-- Migración UP: evaluaciones por actividad
-- Fecha: 2026-08-12
-- Alcance: crea planificación, campos, realizaciones y resultados.
-- Compatibilidad objetivo: MySQL/MariaDB con InnoDB y utf8mb4.
--
-- IMPORTANTE: ejecutar primero en un entorno de prueba y con copia de seguridad.
-- Este archivo no modifica las tablas existentes, pero añade claves foráneas hacia
-- actividades, admins, centros e inscritos.

CREATE TABLE evaluaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actividad_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    instrucciones TEXT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    archivada_at DATETIME NULL,
    created_by_admin_id INT NULL,
    updated_by_admin_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_evaluaciones_periodo
        CHECK (fecha_fin >= fecha_inicio),
    CONSTRAINT fk_evaluaciones_actividad
        FOREIGN KEY (actividad_id)
        REFERENCES actividades(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_evaluaciones_created_admin
        FOREIGN KEY (created_by_admin_id)
        REFERENCES admins(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_evaluaciones_updated_admin
        FOREIGN KEY (updated_by_admin_id)
        REFERENCES admins(id)
        ON DELETE SET NULL,

    INDEX idx_evaluaciones_actividad_periodo (actividad_id, fecha_inicio, fecha_fin),
    INDEX idx_evaluaciones_actividad_archivada (actividad_id, archivada_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evaluacion_campos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluacion_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    tipo_dato ENUM('entero', 'decimal', 'duracion', 'texto_corto') NOT NULL,
    unidad VARCHAR(50) NULL,
    orden SMALLINT NOT NULL DEFAULT 1,
    configuracion_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_evaluacion_campos_orden
        CHECK (orden > 0),
    CONSTRAINT fk_evaluacion_campos_evaluacion
        FOREIGN KEY (evaluacion_id)
        REFERENCES evaluaciones(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_evaluacion_campos_orden (evaluacion_id, orden),
    INDEX idx_evaluacion_campos_evaluacion (evaluacion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evaluacion_sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluacion_id INT NOT NULL,
    numero_intento SMALLINT NOT NULL DEFAULT 1,
    fecha_realizacion DATE NOT NULL,
    estado ENUM('en_curso', 'finalizada') NOT NULL DEFAULT 'en_curso',
    registrada_por_centro_id INT NULL,
    iniciada_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finalizada_at DATETIME NULL,
    reopened_at DATETIME NULL,
    reopened_by_admin_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_evaluacion_sesiones_intento
        CHECK (numero_intento > 0),
    CONSTRAINT chk_evaluacion_sesiones_finalizacion
        CHECK (
            (estado = 'en_curso' AND finalizada_at IS NULL)
            OR
            (estado = 'finalizada' AND finalizada_at IS NOT NULL)
        ),
    CONSTRAINT fk_evaluacion_sesiones_evaluacion
        FOREIGN KEY (evaluacion_id)
        REFERENCES evaluaciones(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_evaluacion_sesiones_centro
        FOREIGN KEY (registrada_por_centro_id)
        REFERENCES centros(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_evaluacion_sesiones_reopen_admin
        FOREIGN KEY (reopened_by_admin_id)
        REFERENCES admins(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_evaluacion_sesiones_intento (evaluacion_id, numero_intento),
    INDEX idx_evaluacion_sesiones_estado (evaluacion_id, estado),
    INDEX idx_evaluacion_sesiones_fecha (fecha_realizacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evaluacion_resultados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluacion_sesion_id INT NOT NULL,
    evaluacion_campo_id INT NOT NULL,
    inscrito_id INT NULL,
    participante_ref VARCHAR(64) NOT NULL,
    participante_nombre VARCHAR(150) NOT NULL,
    participante_apellidos VARCHAR(200) NOT NULL,
    estado ENUM('sin_evaluar', 'medido') NOT NULL DEFAULT 'sin_evaluar',
    valor_numero DECIMAL(12,3) NULL,
    valor_texto VARCHAR(255) NULL,
    calificador ENUM('exacto', 'mayor_que', 'menor_que') NULL,
    updated_by_centro_id INT NULL,
    updated_by_admin_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_evaluacion_resultados_valor
        CHECK (
            (
                estado = 'sin_evaluar'
                AND valor_numero IS NULL
                AND valor_texto IS NULL
                AND calificador IS NULL
            )
            OR
            (
                estado = 'medido'
                AND (
                    (valor_numero IS NOT NULL AND valor_texto IS NULL)
                    OR
                    (valor_numero IS NULL AND valor_texto IS NOT NULL)
                )
            )
        ),
    CONSTRAINT fk_evaluacion_resultado_sesion
        FOREIGN KEY (evaluacion_sesion_id)
        REFERENCES evaluacion_sesiones(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_evaluacion_resultado_campo
        FOREIGN KEY (evaluacion_campo_id)
        REFERENCES evaluacion_campos(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_evaluacion_resultado_inscrito
        FOREIGN KEY (inscrito_id)
        REFERENCES inscritos(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_evaluacion_resultado_centro
        FOREIGN KEY (updated_by_centro_id)
        REFERENCES centros(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_evaluacion_resultado_admin
        FOREIGN KEY (updated_by_admin_id)
        REFERENCES admins(id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_evaluacion_resultado_participante (evaluacion_sesion_id, evaluacion_campo_id, participante_ref),
    INDEX idx_evaluacion_resultados_sesion_estado (evaluacion_sesion_id, estado),
    INDEX idx_evaluacion_resultados_inscrito (inscrito_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
