_______________________________________________________________________________________________________________________________
------------------------------------------------------------------------------------------------
-- Table for storing shipping addresses
CREATE TABLE shipping_addresses (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for the shipping address
    user_id BINARY(16) NOT NULL, -- ULID (UUID alternative for scalability and uniqueness)
    order_id BIGINT NOT NULL, -- Associated order ID
    recipient_name VARCHAR(255) NOT NULL, -- Name of the recipient
    phone VARCHAR(20) NOT NULL, -- Contact phone number
    email VARCHAR(255) NULL, -- Contact email (optional)
    address_line1 VARCHAR(255) NOT NULL, -- First line of the address
    address_line2 VARCHAR(255) NULL, -- Second line of the address (optional)
    city VARCHAR(100) NOT NULL, -- City of the shipping address
    state VARCHAR(100) NOT NULL, -- State/region of the shipping address
    postal_code VARCHAR(20) NOT NULL, -- ZIP or postal code
    country VARCHAR(100) NOT NULL, -- Country of the shipping address
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the record was created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Timestamp when the record was last updated
    -- Foreign Key Constraints
    CONSTRAINT fk_shipping_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, -- Links to users table, deletes if user is removed
    CONSTRAINT fk_shipping_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, -- Links to orders table, deletes if order is removed
    -- Indexes for optimization
    INDEX idx_shipping_user (user_id),
    INDEX idx_shipping_order (order_id)
);
------------------------------------------------------------------------------------------------
-- Table for storing shipping carrier details
CREATE TABLE shipping_carriers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for the shipping carrier
    name VARCHAR(255) NOT NULL UNIQUE, -- Name of the carrier (e.g., DHL, FedEx, UPS)
    tracking_url_format VARCHAR(255) NOT NULL, -- URL template for tracking shipments
    phone VARCHAR(20) NULL, -- Contact phone number (optional)
    email VARCHAR(255) NULL, -- Contact email (optional)
    website VARCHAR(255) NULL, -- Carrier's website (optional)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Timestamp when the record was created
);
------------------------------------------------------------------------------------------------
-- Table for storing shipment details
CREATE TABLE shipments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for the shipment
    order_id BIGINT NOT NULL, -- Associated order ID
    shipping_address_id BIGINT NOT NULL, -- Associated shipping address ID
    carrier_id BIGINT NOT NULL, -- Carrier handling the shipment
    tracking_number VARCHAR(100) NOT NULL UNIQUE, -- Unique tracking number for the shipment
    shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00, -- Cost of shipping
    status ENUM('pending', 'shipped', 'in_transit', 'delivered', 'failed', 'returned', 'cancelled') NOT NULL DEFAULT 'pending', -- Shipment status
    estimated_delivery_date DATE NULL, -- Expected delivery date
    shipped_at TIMESTAMP NULL, -- Timestamp when the shipment was dispatched
    delivered_at TIMESTAMP NULL, -- Timestamp when the shipment was delivered
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the record was created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Timestamp when the record was last updated
    -- Foreign Key Constraints
    CONSTRAINT fk_shipment_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, -- Links to orders table, deletes if order is removed
    CONSTRAINT fk_shipment_address FOREIGN KEY (shipping_address_id) REFERENCES shipping_addresses(id) ON DELETE CASCADE, -- Links to shipping_addresses table, deletes if address is removed
    CONSTRAINT fk_shipment_carrier FOREIGN KEY (carrier_id) REFERENCES shipping_carriers(id) ON DELETE CASCADE, -- Links to shipping_carriers table, deletes if carrier is removed
    -- Indexes for optimization
    INDEX idx_shipment_order (order_id),
    INDEX idx_shipment_status (status),
    INDEX idx_shipment_tracking (tracking_number)
);
------------------------------------------------------------------------------------------------
-- Table for tracking shipment items
CREATE TABLE shipment_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for the shipment item
    shipment_id BIGINT NOT NULL, -- Associated shipment ID
    product_id BIGINT NOT NULL, -- Product being shipped
    quantity INT UNSIGNED NOT NULL, -- Quantity of the product in the shipment
    weight DECIMAL(8,2) NULL, -- Optional field for weight (used for shipping cost calculations)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the record was created
    -- Foreign Key Constraints
    CONSTRAINT fk_shipment_item FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE, -- Links to shipments table, deletes if shipment is removed
    CONSTRAINT fk_shipment_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE, -- Links to products table, deletes if product is removed
    -- Indexes for optimization
    INDEX idx_shipment_product (product_id)
);
------------------------------------------------------------------------------------------------
-- Table for tracking shipment progress
CREATE TABLE shipment_tracking (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for the tracking record
    shipment_id BIGINT NOT NULL, -- Associated shipment ID
    status ENUM('processing', 'in_transit', 'out_for_delivery', 'delivered', 'failed', 'returned', 'cancelled') NOT NULL, -- Current status of the shipment
    return_policy ENUM('replaceable', 'non_replaceable', 'returnable', 'non_returnable') NOT NULL, -- Return policy for the shipment
    location VARCHAR(255) NULL, -- Current location of the package (optional)
    tracking_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the tracking event occurred
    remarks TEXT NULL, -- Additional remarks (optional)
    -- Foreign Key Constraints
    CONSTRAINT fk_tracking_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE, -- Links to shipments table, deletes if shipment is removed
    -- Indexes for optimization
    INDEX idx_tracking_shipment (shipment_id),
    INDEX idx_tracking_status (status)
);
------------------------------------------------------------------------------------------------
_______________________________________________________________________________________________________________________________