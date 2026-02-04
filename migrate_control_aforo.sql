-- Migración: Control de Aforo - Fase 1
-- Añade campo tipo_control a actividades y crea tabla aforo_registros
-- IMPORTANTE: Ejecutar en base de datos pasarlistabdd
-- NO elimina ningún dato existente

-- 1. Añadir campo tipo_control a actividades
-- Default 'asistencia' para que todas las actividades existentes sigan funcionando igual
ALTER TABLE actividades 
ADD COLUMN tipo_control ENUM('asistencia', 'aforo') NOT NULL DEFAULT 'asistencia' 
COMMENT 'Tipo de control: asistencia individual o aforo (cantidad de personas)';

-- 2. Crear tabla para registros de aforo
CREATE TABLE IF NOT EXISTS aforo_registros (
    id INT PRIMARY KEY AUTO_INCREMENT,
    actividad_id INT NOT NULL,
    fecha DATE NOT NULL,
    cantidad INT NOT NULL DEFAULT 0 COMMENT 'Número de personas registradas',
    registrado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE,
    UNIQUE KEY unique_actividad_fecha (actividad_id, fecha) COMMENT 'Solo un registro por actividad y fecha'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Verificar que los cambios se aplicaron correctamente
DESCRIBE actividades;
DESCRIBE aforo_registros;

-- 4. Mostrar actividades con el nuevo campo (todas deberían tener 'asistencia')
SELECT id, nombre, tipo_control FROM actividades ORDER BY id;
