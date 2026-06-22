-- Drop trigger if exists
DROP TRIGGER IF EXISTS after_balance_movement_completed;

-- Create trigger to automatically update user balance when a movement is inserted
DELIMITER $$

CREATE TRIGGER after_balance_movement_completed
AFTER INSERT ON movements
FOR EACH ROW
BEGIN
    UPDATE users 
    SET balance = balance + 
        CASE 
            WHEN NEW.type = 'IN' THEN NEW.amount
            WHEN NEW.type = 'OUT' THEN -NEW.amount
            ELSE 0
        END,
        updated_at = NOW()
    WHERE id = NEW.user_id;
END$$

DELIMITER;