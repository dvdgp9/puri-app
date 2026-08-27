-- Migración DOWN: email de coordinadores y outbox de observaciones
-- Esta reversión elimina el histórico de entregas creado por la migración UP.

DROP TABLE notificacion_observacion_destinatarios;
DROP TABLE notificacion_observacion_eventos;

ALTER TABLE admins
    DROP INDEX idx_admins_email,
    DROP COLUMN email;

