CREATE DATABASE IF NOT EXISTS dummiesvps;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    is_superuser BOOLEAN DEFAULT FALSE,
    balance DECIMAL(10, 2) DEFAULT 0.00,
    telegram_id VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('IN', 'OUT') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_movements_user_created (user_id, created_at),
    INDEX idx_movements_type (type)
);

CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    metadata JSON,
    available_os_image_versions JSON,
    external_id VARCHAR(50),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_plans_external_id (external_id)
);

CREATE TABLE IF NOT EXISTS vps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    external_id VARCHAR(50),
    os_image_id INT,
    status ENUM(
        'PROVISIONING',
        'ACTIVE',
        'INACTIVE',
        'SUSPENDED',
        'TERMINATED'
    ) DEFAULT 'PROVISIONING',
    expires_at DATETIME NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_vps_user (user_id),
    INDEX idx_vps_external (external_id)
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT,
    vps_id INT,
    addon_id INT,
    image_os_id INT,
    duration INT COMMENT 'Duration in hours/units',
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM(
        'PENDING',
        'PAID',
        'COMPLETED',
        'CANCELLED'
    ) DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans (id) ON DELETE SET NULL,
    FOREIGN KEY (vps_id) REFERENCES vps (id) ON DELETE SET NULL,
    FOREIGN KEY (addon_id) REFERENCES addons (id) ON DELETE SET NULL,
    INDEX idx_orders_user (user_id),
    INDEX idx_orders_status (status)
);

CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    category ENUM(
        'TECHNICAL',
        'BILLING',
        'RECOMMENDATIONS',
        'OTHER'
    ) NOT NULL DEFAULT 'OTHER',
    status ENUM('OPEN', 'ANSWERED', 'CLOSED') NOT NULL DEFAULT 'OPEN',
    priority ENUM('LOW', 'MEDIUM', 'HIGH') NOT NULL DEFAULT 'MEDIUM',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_tickets_user (user_id),
    INDEX idx_tickets_status (status)
);

CREATE TABLE IF NOT EXISTS ticket_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_messages_ticket (ticket_id)
);

CREATE TABLE IF NOT EXISTS domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    external_id VARCHAR(36) NULL,
    user_id INT NOT NULL,
    domain_name VARCHAR(255) NOT NULL,
    nameservers JSON NULL COMMENT 'Array de nameservers en formato JSON',
    contacts JSON NULL COMMENT 'Información de contacto (registrant, admin, tech, billing) en formato JSON',
    expiration_date DATETIME NULL,
    status VARCHAR(50) DEFAULT 'PENDING',
    domain_password VARCHAR(255) NULL,
    registration_term INT NULL,
    product_id VARCHAR(50) NULL,
    price_domain DECIMAL(10, 2) NULL,
    last_checked DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_external_id (external_id),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_domains_user (user_id),
    INDEX idx_domains_status (status)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS addons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vps_id INT NOT NULL,
    type ENUM('IPV4', 'IPV6', 'STORAGE') NOT NULL,
    value VARCHAR(255) NULL COMMENT 'IP address (e.g., 192.168.1.1) or storage details',
    price DECIMAL(10, 2) NOT NULL,
    status ENUM(
        'PENDING',
        'ACTIVE',
        'SUSPENDED',
        'TERMINATED',
        'FAILED'
    ) DEFAULT 'PENDING',
    error_message TEXT NULL,
    external_id VARCHAR(50) NULL,
    expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (vps_id) REFERENCES vps (id) ON DELETE CASCADE,
    INDEX idx_addons_user (user_id),
    INDEX idx_addons_vps (vps_id),
    INDEX idx_addons_status (status),
    INDEX idx_addons_type (type)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;