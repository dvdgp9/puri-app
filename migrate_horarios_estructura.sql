-- Migración de estructura de horarios - Fase 1
-- Añadir campos estructurados para horarios manteniendo compatibilidad

-- Añadir nuevos campos para horarios estructurados
ALTER TABLE actividades 
ADD COLUMN dias_semana SET('Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo') NULL COMMENT 'Días de la semana en que se realiza la actividad',
ADD COLUMN hora_inicio TIME NULL COMMENT 'Hora de inicio de la actividad',
ADD COLUMN hora_fin TIME NULL COMMENT 'Hora de finalización de la actividad';

-- Verificar que los campos se añadieron correctamente
DESCRIBE actividades;

-- Mostrar datos actuales para verificar que no se perdió información
SELECT id, nombre, horario, dias_semana, hora_inicio, hora_fin 
FROM actividades 
ORDER BY id;
