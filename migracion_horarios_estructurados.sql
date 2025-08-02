-- Script de migración para añadir estructura de horarios a la tabla actividades
-- Fase 1: Añadir nuevos campos manteniendo el campo `horario` existente

-- Añadir campos para horarios estructurados
ALTER TABLE actividades 
ADD COLUMN dias_semana SET('Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo') NULL,
ADD COLUMN hora_inicio TIME NULL,
ADD COLUMN hora_fin TIME NULL;

-- Verificar que los campos se han añadido correctamente
DESCRIBE actividades;

-- Crear un índice para optimizar consultas por días de la semana
-- (opcional, dependiendo del uso que se vaya a dar)
-- CREATE INDEX idx_actividades_dias ON actividades(dias_semana);

-- Nota: En la siguiente fase se creará un script para migrar los datos existentes
-- del campo `horario` a los nuevos campos estructurados
