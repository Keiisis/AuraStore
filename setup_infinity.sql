-- ======================================
-- AuraStore Database Schema (InfinityFree / Shared Hosting Optimized)
-- IMPORTER CE FICHIER DANS PHPMYADMIN
-- ======================================

-- 1. Nettoyage (Optionnel, au cas où des tables partielles existent)
-- Retirez les commentaires ci-dessous si vous voulez tout effacer et recommencer
-- DROP TABLE IF EXISTS credits;
-- DROP TABLE IF EXISTS orders;
-- DROP TABLE IF EXISTS tryon_sessions;
-- DROP TABLE IF EXISTS products;
-- DROP TABLE IF EXISTS stores;
-- DROP TABLE IF EXISTS users;

-- 2. Création des Tables

-- Users: Admins + Sellers
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(30),
    role ENUM('admin','seller') DEFAULT 'seller',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores: Each seller owns 1 store
CREATE TABLE IF NOT EXISTS stores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    store_name VARCHAR(100) NOT NULL,
    store_slug VARCHAR(120) NOT NULL UNIQUE,
    category VARCHAR(30) DEFAULT 'streetwear',
    description TEXT,
    logo_url VARCHAR(500),
    banner_url VARCHAR(500),
    whatsapp_number VARCHAR(30),
    currency VARCHAR(5) DEFAULT 'XAF',
    total_views INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_slug (store_slug),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL,
    old_price DECIMAL(12,2),
    image_url VARCHAR(500),
    image_2_url VARCHAR(500),
    image_3_url VARCHAR(500),
    sizes VARCHAR(200),
    colors VARCHAR(200),
    stock INT DEFAULT 0,
    vto_enabled TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    vto_target_image INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    INDEX idx_store (store_id),
    INDEX idx_featured (is_featured),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Virtual Try-On Sessions
CREATE TABLE IF NOT EXISTS tryon_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    product_id INT NOT NULL,
    user_photo_hash VARCHAR(64),
    result_image_url VARCHAR(500),
    match_score DECIMAL(5,2),
    processing_time_ms INT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(300),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_store_tryon (store_id),
    INDEX idx_product_tryon (product_id),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders (WhatsApp-driven)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    product_id INT,
    customer_name VARCHAR(100),
    customer_phone VARCHAR(30),
    quantity INT DEFAULT 1,
    total_price DECIMAL(12,2),
    size VARCHAR(10),
    color VARCHAR(30),
    status ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    INDEX idx_store_order (store_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Credits System (VTO usage)
CREATE TABLE IF NOT EXISTS credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount INT NOT NULL,
    type ENUM('purchase','usage','bonus','refund') DEFAULT 'purchase',
    description VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_credits (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CMS Settings used by Landing Page
CREATE TABLE IF NOT EXISTS landing_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pricing Plans
CREATE TABLE IF NOT EXISTS pricing_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    price_xaf DECIMAL(10,2) DEFAULT 0,
    features JSON, 
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    cta_text VARCHAR(50) DEFAULT 'Commencer',
    cta_url VARCHAR(255) DEFAULT 'register.php'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Admin (Password: admin123)
-- Changez ce mot de passe immédiatement après connexion !
INSERT INTO users (full_name, email, password, role) VALUES 
('AuraStore Admin', 'admin@aurastore.com', '$2y$10$g6AgfnlqoC.R8s8q88MWhe/nMWHnlcdgOM5pwN7zt5Yla3lrNcJni', 'admin')
ON DUPLICATE KEY UPDATE id=id;
