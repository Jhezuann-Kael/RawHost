ALTER TABLE vps
    ADD COLUMN IF NOT EXISTS application_id INT NULL AFTER os_image_id;
