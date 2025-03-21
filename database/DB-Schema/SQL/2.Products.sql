_______________________________________________________________________________________________________________________________
------------------------------------------------------------------------------------------------
-- Products Table: Stores the main product information, linked to sellers, categories, and brands.
------------------------------------------------------------------------------------------------
CREATE TABLE products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique product identifier (Auto-increment)
    seller_id BIGINT UNSIGNED NOT NULL, -- Reference to the seller (user who listed the product)
    category_id BIGINT UNSIGNED NOT NULL, -- Reference to the category the product belongs to
    brand_id BIGINT UNSIGNED NOT NULL, -- Reference to the brand of the product
    product_name VARCHAR(255) NOT NULL, -- Name of the product
    slug VARCHAR(255) NOT NULL UNIQUE, -- URL-friendly identifier (unique for SEO purposes)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the product was created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Auto-update timestamp
    deleted_at TIMESTAMP NULL, -- Soft delete timestamp for product removal without data loss

    -- Indexing to optimize queries involving seller, category, and brand
    INDEX idx_products (seller_id, category_id, brand_id),

    -- Foreign key constraints:
    CONSTRAINT fk_products_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE, -- Delete product when seller is removed
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE, -- Delete product when category is removed
    CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE -- Delete product when brand is removed
);
------------------------------------------------------------------------------------------------
-- Product Details Table: Stores extended product descriptions, ensuring a 1:1 relationship with products.
------------------------------------------------------------------------------------------------
CREATE TABLE product_details (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for product details
    product_id BIGINT UNSIGNED NOT NULL UNIQUE, -- 1:1 relation with products (each product has one details entry)
    description TEXT NOT NULL, -- Full description of the product
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the product details were created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Auto-update timestamp

    -- Foreign key constraint:
    CONSTRAINT fk_product_details FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE -- Remove details when product is deleted
);
------------------------------------------------------------------------------------------------
-- Product Variants Table: Stores different variations of a product (e.g., size, color, material).
------------------------------------------------------------------------------------------------
CREATE TABLE product_variants (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each product variant
    product_id BIGINT UNSIGNED NOT NULL, -- Reference to the parent product
    variant_name VARCHAR(255) NOT NULL, -- Variation details (e.g., "Red, Size M")
    price DECIMAL(10,2) NOT NULL, -- Price of the variant
    stock INT UNSIGNED NOT NULL DEFAULT 0, -- Stock quantity available
    sku VARCHAR(100) NOT NULL UNIQUE, -- Unique SKU identifier for inventory tracking
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the variant was created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Auto-update timestamp

    -- Foreign key constraint:
    CONSTRAINT fk_product_variants FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, -- Remove variants when product is deleted

    -- Indexing to speed up searches based on product and variant name
    INDEX idx_variant (product_id, variant_name)
);
------------------------------------------------------------------------------------------------
-- Product Images Table: Stores images associated with products.
------------------------------------------------------------------------------------------------
CREATE TABLE product_images (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each image
    product_id BIGINT UNSIGNED NOT NULL, -- Reference to the associated product
    image_filename VARCHAR(255) NOT NULL, -- Stores only the filename (assumes images are stored in a directory)
    is_primary BOOLEAN NOT NULL DEFAULT false, -- Identifies if the image is the main product image
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the image was uploaded
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Auto-update timestamp

    -- Foreign key constraint:
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, -- Remove images when product is deleted

    -- Indexing to optimize searches by product and primary image flag
    INDEX idx_product_images (product_id, is_primary)
);
------------------------------------------------------------------------------------------------
CREATE TABLE product_inventory (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each inventory record
    warehouse_id BIGINT UNSIGNED NOT NULL, -- Reference to the warehouse where the product is stored
    product_id BIGINT UNSIGNED NOT NULL, -- Reference to the product being stored
    quantity_in_stock INT UNSIGNED DEFAULT 0, -- Current stock available in the warehouse (default 0)
    restock_threshold INT UNSIGNED DEFAULT 10, -- Minimum stock before restocking is triggered (warehouse-specific)
    last_restocked_at TIMESTAMP NULL, -- Timestamp of the last restocking event
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Record creation timestamp
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Auto-updated timestamp on modifications

    -- Foreign Key Constraints to maintain referential integrity
    CONSTRAINT fk_product_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    CONSTRAINT fk_product_inventory FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    -- Indexing for Performance Optimization
    INDEX idx_warehouse (warehouse_id), -- Index to speed up queries filtering by warehouse
    INDEX idx_product (product_id), -- Index to speed up queries filtering by product
    UNIQUE INDEX unique_warehouse_product (warehouse_id, product_id) -- Ensures each warehouse can only have one entry per product
);
------------------------------------------------------------------------------------------------
CREATE TABLE inventory_change_types (
    id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for change type
    change_type VARCHAR(50) NOT NULL UNIQUE -- Describes the type of inventory change (e.g., "Restock", "Sale", "Return")
);
------------------------------------------------------------------------------------------------
CREATE TABLE inventory_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each transaction
    warehouse_id BIGINT UNSIGNED NOT NULL, -- Reference to the warehouse involved in the transaction
    product_id BIGINT UNSIGNED NOT NULL, -- Reference to the affected product
    change_type_id TINYINT UNSIGNED NOT NULL, -- Type of inventory change (linked to `inventory_change_types`)
    quantity INT UNSIGNED NOT NULL, -- Quantity of stock added/removed in this transaction
    reference_order_id BIGINT UNSIGNED NULL, -- If related to an order, references the corresponding order ID
    transaction_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the transaction occurred
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Record creation timestamp

    -- Foreign Key Constraints
    CONSTRAINT fk_inventory_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    CONSTRAINT fk_inventory_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_inventory_order FOREIGN KEY (reference_order_id) REFERENCES orders(id) ON DELETE SET NULL,
    CONSTRAINT fk_inventory_type FOREIGN KEY (change_type_id) REFERENCES inventory_change_types(id) ON DELETE RESTRICT,

    -- Indexing for Performance
    INDEX idx_inventory_warehouse (warehouse_id), -- Optimizes warehouse-based queries
    INDEX idx_inventory_product (product_id), -- Optimizes product-based queries
    INDEX idx_inventory_order (reference_order_id), -- Speeds up lookups for transactions linked to orders
    INDEX idx_inventory_type (change_type_id) -- Optimizes filtering by inventory change type
);
------------------------------------------------------------------------------------------------
CREATE TABLE product_inventory_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each log entry
    product_id BIGINT UNSIGNED NOT NULL, -- Reference to the affected product
    warehouse_id BIGINT UNSIGNED NOT NULL, -- Reference to the warehouse involved in the transaction
    change_type_id TINYINT UNSIGNED NOT NULL, -- Type of inventory change (linked to `inventory_change_types`)
    quantity_change INT NOT NULL, -- Signed INT to track stock increase (+) or decrease (-)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Record creation timestamp

    -- Foreign Keys for Referential Integrity
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (change_type_id) REFERENCES inventory_change_types(id) ON DELETE RESTRICT,

    -- Indexing for Fast Query Performance
    INDEX idx_product_inventory_product (product_id), -- Optimizes product-based queries
    INDEX idx_product_inventory_warehouse (warehouse_id), -- Optimizes warehouse-based queries
    INDEX idx_product_inventory_type (change_type_id), -- Optimizes filtering by change type
    INDEX idx_product_inventory_created (created_at) -- Optimizes time-based queries
);
------------------------------------------------------------------------------------------------
-- This table defines different statuses for products (e.g., "Active", "Inactive", "Out of Stock").
CREATE TABLE product_statuses (
    id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each product status
    name VARCHAR(20) NOT NULL UNIQUE -- Status name (e.g., "Active", "Inactive"), must be unique
);
------------------------------------------------------------------------------------------------
-- Stores discount details for products, allowing both fixed-price and percentage-based discounts.
CREATE TABLE product_discounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each discount
    product_id BIGINT UNSIGNED NOT NULL, -- References the product receiving the discount
    discount_price DECIMAL(10,2) NOT NULL CHECK (discount_price >= 0.00), -- Fixed discount price, cannot be negative
    discount_percentage DECIMAL(5,2) NULL CHECK (discount_percentage BETWEEN 0.00 AND 100.00), -- Percentage discount (optional), must be between 0 and 100
    start_date TIMESTAMP NULL, -- Optional start date for the discount period
    end_date TIMESTAMP NULL, -- Optional end date for the discount period
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Automatically records when the discount was created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Updates timestamp when discount data is modified

    -- Foreign Key Constraint: Ensures discount belongs to a valid product
    CONSTRAINT fk_product_discounts_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    -- Indexing for Performance Optimization
    INDEX idx_product_discounts_product (product_id), -- Speeds up discount lookups by product
    INDEX idx_product_discounts_dates (start_date, end_date) -- Optimizes filtering by active discount periods
);
------------------------------------------------------------------------------------------------
-- Stores customer reviews for products, including ratings and verification status.
CREATE TABLE product_reviews (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each review
    product_id BIGINT UNSIGNED NOT NULL, -- References the reviewed product
    user_id CHAR(36) NULL, -- References the user who wrote the review (supports UUID format)
    review TEXT NOT NULL CHECK (CHAR_LENGTH(review) > 5), -- Ensures meaningful reviews by requiring a minimum length of 5 characters
    rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5), -- Rating scale from 1 to 5
    verified_purchase BOOLEAN DEFAULT FALSE, -- Indicates if the reviewer purchased the product
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the review was created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Updates timestamp when the review is edited

    -- Foreign Key Constraints: Ensure referential integrity
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, -- Deletes reviews when the product is deleted
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL, -- Sets user_id to NULL if the user is deleted

    -- Indexing for Performance Optimization
    INDEX idx_product_reviews_product (product_id), -- Optimizes queries filtering by product
    INDEX idx_product_reviews_user (user_id), -- Speeds up user-based review lookups
    INDEX idx_product_reviews_rating (rating) -- Optimizes filtering by rating
);
------------------------------------------------------------------------------------------------
-- Stores brand information, including name, description, and logo.
CREATE TABLE brands (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each brand
    brand_name VARCHAR(255) NOT NULL UNIQUE, -- Unique brand name
    brand_description TEXT NULL, -- Optional description of the brand
    logo_url TEXT NULL, -- URL to the brand's logo
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the brand record was created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Updates timestamp when brand data is modified

    -- Indexing for Performance
    INDEX idx_brand_name (brand_name) -- Optimizes queries filtering by brand name
);
------------------------------------------------------------------------------------------------
-- Stores product categories, ensuring unique names and slugs.
CREATE TABLE categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each category
    category_name VARCHAR(255) NOT NULL UNIQUE, -- Unique name for the category
    slug VARCHAR(255) NOT NULL UNIQUE, -- SEO-friendly category identifier (e.g., "electronics", "home-appliances")
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the category was created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- Updates timestamp when category data is modified
);
------------------------------------------------------------------------------------------------
-- Manages parent-child relationships between categories (e.g., "Electronics" → "Laptops").
CREATE TABLE category_hierarchy (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each hierarchy entry
    parent_id BIGINT UNSIGNED NOT NULL, -- References the parent category
    child_id BIGINT UNSIGNED NOT NULL, -- References the child category

    -- Foreign Key Constraints: Ensure referential integrity
    CONSTRAINT fk_category_hierarchy_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE, -- Deletes child categories if parent is deleted
    CONSTRAINT fk_category_hierarchy_child FOREIGN KEY (child_id) REFERENCES categories(id) ON DELETE CASCADE, -- Deletes relationships when a child category is removed

    -- Ensures that the same parent-child relationship is not duplicated
    UNIQUE KEY unique_parent_child (parent_id, child_id)
);
------------------------------------------------------------------------------------------------







------------------------------------------------------------------------------------------------
-- CREATE TABLE categories (
--     id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
--     category_name VARCHAR(255) NOT NULL UNIQUE,
--     slug VARCHAR(255) NOT NULL UNIQUE CHECK (slug REGEXP '^[a-z0-9-]+$'),
--     parent_id BIGINT UNSIGNED NULL, -- Self-referencing for hierarchical categories
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

--     -- Foreign Key for Hierarchical Categories
--     CONSTRAINT fk_category_parent FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE CASCADE,

--     -- Indexing for Fast Lookups
--     INDEX idx_category_name (category_name),
--     INDEX idx_category_slug (slug),
--     INDEX idx_category_parent (parent_id)
-- );
------------------------------------------------------------------------------------------------

_______________________________________________________________________________________________________________________________