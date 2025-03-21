_______________________________________________________________________________________________________________________________
------------------------------------------------------------------------------------------------
-- Orders table: Stores order details for each transaction
------------------------------------------------------------------------------------------------
CREATE TABLE orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each order
    user_id CHAR(26) NOT NULL,  -- References the user who placed the order
    total_price DECIMAL(10, 2) NOT NULL,  -- Total price of the order
    status_id BIGINT UNSIGNED NOT NULL,  -- Foreign key to order_statuses table, tracks order status
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Timestamp when the order was created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Timestamp when the order was last updated
    deleted_at TIMESTAMP NULL,  -- Soft delete timestamp for logical deletion

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,  -- Cascade delete when user is deleted
    FOREIGN KEY (status_id) REFERENCES order_statuses(id),  -- Ensures valid order status assignment
    INDEX (user_id),  -- Index to optimize user order lookup
    INDEX (status_id)  -- Index to optimize order status lookup
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
-- Order statuses table: Defines different statuses an order can have
------------------------------------------------------------------------------------------------
CREATE TABLE order_statuses (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,  -- Unique identifier for each status
    status VARCHAR(50) UNIQUE  -- Possible values: 'pending', 'paid', 'shipped', etc.
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
);------------------------------------------------------------------------------------------------
_______________________________________________________________________________________________________________________________