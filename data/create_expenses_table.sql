CREATE TABLE IF NOT EXISTS expenses (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    currency        VARCHAR(20)    NOT NULL,
    amount_currency DECIMAL(18,8)  NULL,
    amount_fiat     DECIMAL(10,2)  NOT NULL,
    fiat_currency   VARCHAR(3)     NOT NULL DEFAULT 'USD',
    description     TEXT           NULL,
    created_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
);
