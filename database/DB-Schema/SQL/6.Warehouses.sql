_______________________________________________________________________________________________________________________________
------------------------------------------------------------------------------------------------
-- the 'warehouses' table to store warehouse details
CREATE TABLE warehouses (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each warehouse, auto-incremented
    name VARCHAR(255) NOT NULL, -- Name of the warehouse, cannot be null
    phone VARCHAR(20) NULL, -- Contact phone number, optional field
    email VARCHAR(255) NULL UNIQUE, -- Unique email for the warehouse, optional field
    location_id BIGINT NOT NULL, -- Foreign key referencing the 'locations' table, required field
    address TEXT NULL, -- Detailed address, optional field
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the record is created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Timestamp updated on record modification
    
    -- Foreign Key Constraint: Ensures location_id references an existing entry in locations table
    CONSTRAINT fk_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
    
    -- Indexing for Optimization: Improves search performance on frequently queried columns
    INDEX idx_name (name), -- Index on warehouse name for faster lookups
    INDEX idx_email (email), -- Index on email for quick searches and uniqueness enforcement
    INDEX idx_location (location_id) -- Index on location_id to optimize join queries
);
------------------------------------------------------------------------------------------------
-- the 'locations' table to store geographical details
CREATE TABLE locations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each location, auto-incremented
    country VARCHAR(255) NOT NULL, -- Country name, required field
    city VARCHAR(255) NOT NULL, -- City name, required field
    
    -- Ensure unique combinations to avoid redundant entries
    UNIQUE INDEX idx_country_city (country, city) -- Prevents duplicate country-city pairs
);
------------------------------------------------------------------------------------------------
-- the 'warehouse_inventory_movements' table to track inventory changes
CREATE TABLE warehouse_inventory_movements (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each inventory movement, auto-incremented
    warehouse_id BIGINT NOT NULL, -- Foreign key referencing the 'warehouses' table, required field
    product_id BIGINT NOT NULL, -- Foreign key referencing the 'products' table, required field
    change_type ENUM('restock', 'sale', 'damage', 'return', 'transfer') NOT NULL, -- Type of inventory movement
    quantity_changed INT NOT NULL, -- The amount of product added or removed
    movement_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp of the movement event
    reference_order_id BIGINT NULL, -- References an order if applicable, optional field
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the record is created
    
    -- Foreign Key Constraints: Ensures data integrity between related tables
    CONSTRAINT fk_inventory_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE, -- Deletes movements when warehouse is deleted
    CONSTRAINT fk_inventory_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, -- Deletes movements when product is deleted
    CONSTRAINT fk_inventory_order FOREIGN KEY (reference_order_id) REFERENCES orders(id) ON DELETE SET NULL, -- Sets reference_order_id to NULL if order is deleted
    
    -- Indexing for Optimization: Enhances query performance on frequently accessed columns
    INDEX idx_inventory_warehouse (warehouse_id), -- Index on warehouse_id for efficient lookups
    INDEX idx_inventory_product (product_id), -- Index on product_id for quick retrieval
    INDEX idx_inventory_order (reference_order_id) -- Index on reference_order_id for fast order lookups
);
_______________________________________________________________________________________________________________________________