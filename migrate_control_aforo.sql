-- Migración: Control de Aforo
-- Fecha: 2026-02-04
-- Descripción: Crea tabla registros_aforo para el nuevo sistema de aforo
-- NOTA: El campo tipo_control ya existe en actividades (migración previa)

-- La tabla anterior aforo_registros fue eliminada manualmente
-- Esta nueva tabla soporta múltiples registros por día (por hora/sesión)

-- Crear tabla para registros de aforo
CREATE TABLE IF NOT EXISTS registros_aforo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actividad_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    num_personas INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL COMMENT 'ID del centro que registró (para auditoría)',
    
    -- Foreign key a actividades
    CONSTRAINT fk_aforo_actividad 
        FOREIGN KEY (actividad_id) 
        REFERENCES actividades(id) 
        ON DELETE CASCADE,
    
    -- Índices para consultas frecuentes
    INDEX idx_aforo_actividad_fecha (actividad_id, fecha),
    INDEX idx_aforo_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificación (opcional, para confirmar que la migración fue exitosa)
-- SELECT * FROM registros_aforo LIMIT 1;
-- DESCRIBE registros_aforo;

-- SELECT COUNT(*) AS tabla_creada FROM INFORMATION_SCHEMA.TABLES 
-- WHERE TABLE_NAME = 'registros_aforo';
