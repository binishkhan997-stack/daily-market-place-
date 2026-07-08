-- database_schema.sql

-- Table: Users (Customers, Vendors, Admins)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    user_type ENUM('customer', 'vendor', 'admin') NOT NULL DEFAULT 'customer',
    vendor_status ENUM('pending', 'approved', 'suspended') DEFAULT 'pending',
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_user_type (user_type)
);

-- Table: Vendor Profiles (Additional details for vendors)
CREATE TABLE vendor_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    store_name VARCHAR(100) NOT NULL,
    store_description TEXT,
    store_logo VARCHAR(255),
    store_banner VARCHAR(255),
    store_address TEXT,
    store_phone VARCHAR(20),
    is_verified BOOLEAN DEFAULT FALSE,
    rating DECIMAL(3,2) DEFAULT 0,
    total_sales INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: Categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    parent_id INT DEFAULT NULL,
    description TEXT,
    icon_class VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Table: Products
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    short_description TEXT,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 0,
    sku VARCHAR(50),
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    views INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    total_orders INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_category_id (category_id),
    INDEX idx_slug (slug),
    FULLTEXT INDEX idx_search (name, description, short_description)
);

-- Table: Product Images
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
);

-- Table: Product Attributes (Dynamic attributes like color, size, material)
CREATE TABLE product_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    attribute_name VARCHAR(50) NOT NULL,
    attribute_value VARCHAR(255) NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
);

-- Table: Product Variations (E.g., size/color combos with separate stock/prices)
CREATE TABLE product_variations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sku VARCHAR(50),
    price DECIMAL(10,2),
    quantity INT NOT NULL DEFAULT 0,
    attributes JSON, -- {"size": "M", "color": "Red"}
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
);

-- Table: Orders
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    vendor_id INT NOT NULL, -- Track which vendor this order is for
    total_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    shipping_charge DECIMAL(10,2) DEFAULT 0,
    net_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned') DEFAULT 'pending',
    payment_method ENUM('cod', 'bank_transfer', 'card', 'mobile_wallet') NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    billing_address TEXT NOT NULL,
    order_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_order_number (order_number)
);

-- Table: Order Items
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variation_id INT DEFAULT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL, -- Price at time of purchase
    total_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variation_id) REFERENCES product_variations(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id)
);

-- Table: Shopping Cart
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    variation_id INT DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variation_id) REFERENCES product_variations(id) ON DELETE SET NULL,
    UNIQUE KEY unique_cart_item (user_id, product_id, variation_id)
);

-- Table: Reviews and Ratings
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_user_id (user_id)
);

-- Table: Wishlist
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

-- Table: Vendor Payouts / Commissions
CREATE TABLE vendor_payouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    order_id INT NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL, -- Platform's cut (e.g., 10%)
    payout_amount DECIMAL(10,2) NOT NULL, -- Amount to be paid to vendor
    status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    paid_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_vendor_id (vendor_id)
);

-- Table: Product Attributes (Moved to proper location)
-- Product attributes table already defined above
