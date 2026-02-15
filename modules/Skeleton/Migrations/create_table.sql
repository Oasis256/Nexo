CREATE TABLE IF NOT EXISTS skeleton_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(255) DEFAULT 'active',
    category VARCHAR(255) DEFAULT 'general',
    price DECIMAL(10,2) DEFAULT 0.00,
    quantity INT DEFAULT 0,
    metadata JSON,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    KEY idx_status (status),
    KEY idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
