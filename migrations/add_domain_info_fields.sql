-- Agregar campos para información completa del dominio
ALTER TABLE domains
ADD COLUMN created_date DATE NULL AFTER domain_name,
ADD COLUMN expired_date DATE NULL AFTER created_date,
ADD COLUMN nameservers JSON NULL AFTER expired_date,
ADD COLUMN contacts JSON NULL AFTER nameservers;

-- Comentario explicativo de los campos
-- created_date: Fecha de creación del dominio
-- expired_date: Fecha de expiración del dominio
-- nameservers: Array JSON de nameservers, ejemplo: ["ns1.example.com", "ns2.example.com"]
-- contacts: Objeto JSON con información de contacto completa (registrant, admin, tech, billing)