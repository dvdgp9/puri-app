ALTER TABLE actividades
    DROP FOREIGN KEY fk_actividades_edicion,
    DROP INDEX idx_actividades_edicion_instalacion,
    DROP COLUMN retirada_at,
    DROP COLUMN edicion_id;
DROP TABLE ediciones;
