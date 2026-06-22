-- Add os_image_id column to vps table
ALTER TABLE vps
ADD COLUMN os_image_id INT DEFAULT NULL AFTER external_id;