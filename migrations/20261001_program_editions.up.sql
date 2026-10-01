-- Aplicar después de 20260923_recurring_evaluations_and_rosters.up.sql.
CREATE TABLE ediciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    centro_id INT NOT NULL,
    fecha_inicio DATE NULL,
    fecha_fin DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ediciones_centro FOREIGN KEY (centro_id) REFERENCES centros(id) ON DELETE CASCADE,
    INDEX idx_ediciones_centro_fechas (centro_id, fecha_inicio, fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE actividades
    ADD COLUMN edicion_id INT NULL,
    ADD COLUMN retirada_at DATE NULL,
    ADD CONSTRAINT fk_actividades_edicion FOREIGN KEY (edicion_id) REFERENCES ediciones(id) ON DELETE SET NULL,
    ADD INDEX idx_actividades_edicion_instalacion (edicion_id, instalacion_id, retirada_at);

-- Conservar las actividades existentes juntas, con sus IDs e historial intactos.
-- No inferir cursos anteriores por el año natural: un curso puede cruzar dos años.
INSERT INTO ediciones (centro_id, fecha_inicio, fecha_fin)
SELECT i.centro_id, MIN(a.fecha_inicio),
       CASE WHEN COUNT(a.fecha_fin) = COUNT(*) THEN MAX(a.fecha_fin) ELSE NULL END
FROM actividades a JOIN instalaciones i ON i.id = a.instalacion_id
GROUP BY i.centro_id;

UPDATE actividades a
JOIN instalaciones i ON i.id = a.instalacion_id
JOIN ediciones e ON e.centro_id = i.centro_id
SET a.edicion_id = e.id;
