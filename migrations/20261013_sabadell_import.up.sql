-- Importador municipal optativo. Solo un superadmin puede concederlo.
ALTER TABLE admins ADD COLUMN puede_importar_sabadell TINYINT(1) NOT NULL DEFAULT 0;
