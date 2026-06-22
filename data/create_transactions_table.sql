CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    payment_amount DECIMAL(18, 8) NOT NULL,
    payment_currency VARCHAR(10) NOT NULL,
    track_id VARCHAR(100) NOT NULL UNIQUE,
    tx_hash VARCHAR(255) DEFAULT NULL,
    status ENUM(
        'PENDING',
        'COMPLETED',
        'FAILED',
        'EXPIRED'
    ) DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id)
);