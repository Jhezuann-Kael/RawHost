-- Agregar campo external_id para el ID del dominio en la API externa
ALTER TABLE domains
ADD COLUMN external_id VARCHAR(36) NULL AFTER id,
ADD UNIQUE KEY unique_external_id (external_id);

-- Comentario:
-- external_id: ID del dominio en la API externa (database_info.id), de 36 caracteres (UUID format)
-- Ejemplo: "d92141bb-9ba1-11f0-8cae-525400792197"