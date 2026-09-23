ALTER TABLE evaluaciones DROP FOREIGN KEY fk_evaluaciones_serie;
ALTER TABLE evaluaciones DROP INDEX uq_evaluaciones_serie_ciclo_actividad;
ALTER TABLE evaluaciones DROP COLUMN serie_id, DROP COLUMN ciclo;
DROP TABLE evaluacion_serie_actividades;
DROP TABLE evaluacion_serie_campos;
DROP TABLE evaluacion_series;
DROP TABLE inscrito_vigencias;
ALTER TABLE inscritos DROP INDEX idx_inscritos_actividad_activo;
ALTER TABLE inscritos DROP COLUMN activo, DROP COLUMN alta_at, DROP COLUMN baja_at;
