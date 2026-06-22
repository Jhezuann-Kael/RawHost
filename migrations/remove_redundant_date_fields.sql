-- Eliminar campos created_date y expired_date que son redundantes
ALTER TABLE domains
DROP COLUMN created_date,
DROP COLUMN expired_date;