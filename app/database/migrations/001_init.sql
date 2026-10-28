CREATE TABLE shops (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(63) NOT NULL,
    custom_domain VARCHAR(190) NULL,
    name VARCHAR(190) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'demo',
    noindex TINYINT(1) NOT NULL DEFAULT 1,
    settings MEDIUMTEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_shops_slug (slug),
    UNIQUE KEY uq_shops_domain (custom_domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NULL,
    role VARCHAR(20) NOT NULL,
    name VARCHAR(190) NOT NULL DEFAULT '',
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    KEY idx_users_email (email),
    KEY idx_users_shop (shop_id),
    CONSTRAINT fk_users_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_login_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cake_sizes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    persons INT NOT NULL DEFAULT 0,
    weight_kg DECIMAL(6,2) NOT NULL DEFAULT 0,
    tiers INT NOT NULL DEFAULT 1,
    shape VARCHAR(30) NOT NULL DEFAULT 'okrugla',
    sort INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_sizes_shop (shop_id),
    CONSTRAINT fk_sizes_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cake_flavors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    description TEXT NULL,
    allergens VARCHAR(255) NOT NULL DEFAULT '',
    price_per_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
    sort INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_flavors_shop (shop_id),
    CONSTRAINT fk_flavors_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    description TEXT NULL,
    allergens VARCHAR(255) NOT NULL DEFAULT '',
    unit VARCHAR(10) NOT NULL DEFAULT 'kg',
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    min_qty DECIMAL(8,2) NOT NULL DEFAULT 1,
    step DECIMAL(8,2) NOT NULL DEFAULT 1,
    image_path VARCHAR(255) NULL,
    sort INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_products_shop (shop_id),
    CONSTRAINT fk_products_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE gallery_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL DEFAULT '',
    title VARCHAR(190) NOT NULL DEFAULT '',
    image_path VARCHAR(255) NOT NULL,
    sort INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    KEY idx_gallery_shop (shop_id),
    CONSTRAINT fk_gallery_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blocked_dates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    day DATE NOT NULL,
    note VARCHAR(190) NOT NULL DEFAULT '',
    UNIQUE KEY uq_blocked (shop_id, day),
    CONSTRAINT fk_blocked_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE design_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    token CHAR(32) NOT NULL,
    client_key CHAR(32) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    input MEDIUMTEXT NOT NULL,
    briefs MEDIUMTEXT NULL,
    generator VARCHAR(30) NOT NULL DEFAULT '',
    cost_usd DECIMAL(10,4) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_design_token (token),
    KEY idx_design_client (shop_id, client_key),
    KEY idx_design_ip (ip, created_at),
    CONSTRAINT fk_design_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE design_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    idx TINYINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    image_path VARCHAR(255) NULL,
    error VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_design_image (session_id, idx),
    CONSTRAINT fk_design_images_session FOREIGN KEY (session_id) REFERENCES design_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    number INT UNSIGNED NOT NULL,
    code CHAR(16) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'nova',
    customer_name VARCHAR(190) NOT NULL,
    customer_phone VARCHAR(60) NOT NULL,
    customer_email VARCHAR(190) NOT NULL DEFAULT '',
    customer_note TEXT NULL,
    fulfillment VARCHAR(20) NOT NULL DEFAULT 'preuzimanje',
    delivery_address VARCHAR(255) NOT NULL DEFAULT '',
    due_date DATE NOT NULL,
    due_time VARCHAR(20) NOT NULL DEFAULT '',
    estimated_total DECIMAL(10,2) NOT NULL DEFAULT 0,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    final_total DECIMAL(10,2) NULL,
    deposit_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    deposit_paid TINYINT(1) NOT NULL DEFAULT 0,
    internal_note TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_orders_code (code),
    UNIQUE KEY uq_orders_number (shop_id, number),
    KEY idx_orders_due (shop_id, due_date),
    CONSTRAINT fk_orders_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    type VARCHAR(20) NOT NULL,
    name VARCHAR(255) NOT NULL,
    details MEDIUMTEXT NULL,
    quantity DECIMAL(8,2) NOT NULL DEFAULT 1,
    unit VARCHAR(10) NOT NULL DEFAULT 'kom',
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    image_path VARCHAR(255) NULL,
    KEY idx_items_order (order_id),
    CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_usage (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    kind VARCHAR(20) NOT NULL,
    provider VARCHAR(30) NOT NULL,
    model VARCHAR(60) NOT NULL DEFAULT '',
    input_tokens INT NOT NULL DEFAULT 0,
    output_tokens INT NOT NULL DEFAULT 0,
    cost_usd DECIMAL(10,4) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    KEY idx_usage_shop (shop_id, created_at),
    CONSTRAINT fk_usage_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
