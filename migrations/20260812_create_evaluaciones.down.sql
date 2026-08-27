-- Migración DOWN: evaluaciones por actividad
-- Fecha: 2026-08-12
--
-- ADVERTENCIA: esta reversión elimina definitivamente evaluaciones y resultados.
-- Ejecutar solo tras confirmar el entorno y disponer de una copia de seguridad.

DROP TABLE IF EXISTS evaluacion_resultados;
DROP TABLE IF EXISTS evaluacion_sesiones;
DROP TABLE IF EXISTS evaluacion_campos;
DROP TABLE IF EXISTS evaluaciones;

