ALTER TABLE plans
    ADD COLUMN IF NOT EXISTS available_applications JSON NULL AFTER available_os_image_versions;
