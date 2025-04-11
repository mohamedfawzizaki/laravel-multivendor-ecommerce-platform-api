_______________________________________________________________________________________________________________________________
------------------------------------------------------------------------------------------------
-- Orders table: Stores order details for each transaction
------------------------------------------------------------------------------------------------
CREATE TABLE orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each order
    user_id CHAR(26) NOT NULL,  -- References the user who placed the order
    total_price DECIMAL(10, 2) NOT NULL,  -- Total price of the order
    status ENUM('pending', 'paid', 'shipped', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',  -- Replaces FK with enum
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Timestamp when the order was created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Timestamp when the order was last updated
    deleted_at TIMESTAMP NULL,  -- Soft delete timestamp for logical deletion

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,  -- Cascade delete when user is deleted

    INDEX (user_id),
    INDEX (status)
);

------------------------------------------------------------------------------------------------
-- Order items table: Stores individual items within an order
------------------------------------------------------------------------------------------------
CREATE TABLE order_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each order item
    order_id BIGINT NOT NULL,  -- References the related order
    product_id BIGINT NOT NULL,  -- References the purchased product
    quantity UNSIGNED INT NOT NULL,  -- Number of items purchased
    price DECIMAL(10, 2) NOT NULL,  -- Price per unit of the product
    subtotal DECIMAL(10, 2) NOT NULL,  -- Total price for this item (quantity * price)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Timestamp when item was added to order
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Timestamp when item was last updated
    
    -- Foreign keys
    CONSTRAINT fk_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,  -- Cascade delete when order is deleted
    CONSTRAINT fk_product_id FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,  -- Cascade delete when product is deleted

    -- Indexes
    INDEX idx_order_id (order_id),  -- Optimizes order-based lookups
    INDEX idx_product_id (product_id)  -- Optimizes product-based lookups
);
------------------------------------------------------------------------------------------------
-- Order payments table: Stores payment details for each order
------------------------------------------------------------------------------------------------
CREATE TABLE order_payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each payment record
    order_id BIGINT UNSIGNED NOT NULL,  -- References the associated order
    payment_status ENUM('unpaid', 'paid', 'pending', 'failed', 'refunded', 'cancelled') DEFAULT 'unpaid',  -- Status of the payment
    payment_method ENUM('credit_card', 'paypal', 'cash_on_delivery', 'bank_transfer') NOT NULL,  -- Payment method used
    transaction_id VARCHAR(255) NULL,  -- Nullable transaction ID for payment tracking

    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,  -- Cascade delete when order is deleted
    INDEX (order_id)  -- Optimizes lookups based on order ID
);
------------------------------------------------------------------------------------------------
CREATE TABLE invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    
    invoice_number VARCHAR(100) NOT NULL UNIQUE,
    invoice_date DATE NOT NULL DEFAULT CURRENT_DATE,
    due_date DATE NULL,

    subtotal DECIMAL(10, 2) NOT NULL,
    tax_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL,

    status ENUM('unpaid', 'paid', 'refunded', 'cancelled') NOT NULL DEFAULT 'unpaid',
    currency_code VARCHAR(10) NOT NULL DEFAULT 'USD',

    pdf_path VARCHAR(255) NULL,
    notes TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_invoice_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_invoice_currency FOREIGN KEY (currency_code) REFERENCES currencies(code),

    INDEX idx_invoice_order (order_id),
    INDEX idx_invoice_number (invoice_number)
);

------------------------------------------------------------------------------------------------

CREATE TABLE invoice_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NULL,

    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    tax_rate DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_invoice_item_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_invoice_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,

    INDEX idx_invoice_item_invoice_id (invoice_id),
    INDEX idx_invoice_item_product_id (product_id)
);


_______________________________________________________________________________________________________________________________