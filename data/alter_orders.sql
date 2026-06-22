ALTER TABLE orders ADD COLUMN image_os_id VARCHAR(50) AFTER plan_id;

ALTER TABLE orders MODIFY COLUMN duration INT DEFAULT 1;

UPDATE users SET is_superuser = 1 WHERE id = 2;