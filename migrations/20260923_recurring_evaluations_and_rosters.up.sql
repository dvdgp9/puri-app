-- Aplicar después de 20260812_create_evaluaciones.up.sql.
ALTER TABLE inscritos
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN alta_at DATE NULL,
    ADD COLUMN baja_at DATE NULL,
    ADD INDEX idx_inscritos_actividad_activo (actividad_id, activo);

CREATE TABLE inscrito_vigencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inscrito_id INT NOT NULL,
    inicio DATE NOT NULL,
    fin DATE NULL,
    CONSTRAINT fk_vigencias_inscrito FOREIGN KEY (inscrito_id) REFERENCES inscritos(id) ON DELETE CASCADE,
    INDEX idx_vigencias_inscrito_fechas (inscrito_id, inicio, fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Los inscritos anteriores a la migración conservan todo su histórico.
UPDATE inscritos SET alta_at = '1000-01-01' WHERE alta_at IS NULL;
INSERT INTO inscrito_vigencias (inscrito_id, inicio)
SELECT id, '1000-01-01' FROM inscritos;

CREATE TABLE evaluacion_series (
    id INT AUTO_INCREMENT PRIMARY KEY,
    centro_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    tipo ENUM('trimestral', 'retos') NOT NULL,
    instrucciones TEXT NULL,
    primer_inicio DATE NOT NULL,
    primer_fin DATE NOT NULL,
    hasta DATE NOT NULL,
    archivada_at DATETIME NULL,
    created_by_admin_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_series_periodo CHECK (primer_fin >= primer_inicio AND hasta >= primer_inicio),
    CONSTRAINT fk_series_centro FOREIGN KEY (centro_id) REFERENCES centros(id),
    CONSTRAINT fk_series_admin FOREIGN KEY (created_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_series_centro_tipo (centro_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evaluacion_serie_actividades (
    serie_id INT NOT NULL,
    actividad_id INT NOT NULL,
    PRIMARY KEY (serie_id, actividad_id),
    CONSTRAINT fk_serie_actividades_serie FOREIGN KEY (serie_id) REFERENCES evaluacion_series(id) ON DELETE CASCADE,
    CONSTRAINT fk_serie_actividades_actividad FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evaluacion_serie_campos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    serie_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    tipo_dato ENUM('entero', 'decimal', 'duracion', 'texto_corto') NOT NULL,
    unidad VARCHAR(50) NULL,
    orden SMALLINT NOT NULL,
    CONSTRAINT fk_serie_campos_serie FOREIGN KEY (serie_id) REFERENCES evaluacion_series(id) ON DELETE CASCADE,
    UNIQUE KEY uq_serie_campos_orden (serie_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE evaluaciones
    ADD COLUMN serie_id INT NULL,
    ADD COLUMN ciclo SMALLINT NULL,
    ADD CONSTRAINT fk_evaluaciones_serie FOREIGN KEY (serie_id) REFERENCES evaluacion_series(id) ON DELETE SET NULL,
    ADD UNIQUE KEY uq_evaluaciones_serie_ciclo_actividad (serie_id, ciclo, actividad_id);
