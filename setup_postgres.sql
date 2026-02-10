-- ======================================
-- AuraStore Database Schema (PostgreSQL/Supabase)
-- ======================================

-- Cloud Database: Created user account manually or via Dashboard
-- This script creates Tables & Indexes

-- Users: Admins + Sellers
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(30),
    role VARCHAR(20) DEFAULT 'seller' CHECK (role IN ('admin', 'seller')),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_role ON users(role);

-- Stores: Each seller owns 1 store
CREATE TABLE IF NOT EXISTS stores (
    id SERIAL PRIMARY KEY,
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
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_slug ON stores(store_slug);
CREATE INDEX IF NOT EXISTS idx_category ON stores(category);

-- Products
CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
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
    vto_enabled BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_store ON products(store_id);
CREATE INDEX IF NOT EXISTS idx_featured ON products(is_featured);

-- Virtual Try-On Sessions
CREATE TABLE IF NOT EXISTS tryon_sessions (
    id SERIAL PRIMARY KEY,
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
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_store_tryon ON tryon_sessions(store_id);
CREATE INDEX IF NOT EXISTS idx_date ON tryon_sessions(created_at);

-- Orders (WhatsApp-driven)
CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    store_id INT NOT NULL,
    product_id INT,
    customer_name VARCHAR(100),
    customer_phone VARCHAR(30),
    quantity INT DEFAULT 1,
    total_price DECIMAL(12,2),
    size VARCHAR(10),
    color VARCHAR(30),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending','confirmed','shipped','delivered','cancelled')),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_store_order ON orders(store_id);
CREATE INDEX IF NOT EXISTS idx_status ON orders(status);

-- Credits System (VTO usage)
CREATE TABLE IF NOT EXISTS credits (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    amount INT NOT NULL,
    type VARCHAR(20) DEFAULT 'purchase' CHECK (type IN ('purchase','usage','bonus','refund')),
    description VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_user_credits ON credits(user_id);

-- Insert default admin (Password: admin123)
-- In Postgres, we use ON CONFLICT DO NOTHING or UPDATE
INSERT INTO users (full_name, email, password, role) 
VALUES ('AuraStore Admin', 'admin@aurastore.com', '$2y$10$g6AgfnlqoC.R8s8q88MWhe/nMWHnlcdgOM5pwN7zt5Yla3lrNcJni', 'admin')
ON CONFLICT (email) DO NOTHING;
