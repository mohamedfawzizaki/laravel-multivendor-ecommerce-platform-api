_______________________________________________________________________________________________________________________________
------------------------------------------------------------------------------------------------
-- Payments table: Stores all payment records, linking users and orders
------------------------------------------------------------------------------------------------
CREATE TABLE payments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each payment
    user_id CHAR(26) NOT NULL,  -- References the user making the payment
    order_id BIGINT NOT NULL,  -- References the associated order
    amount DECIMAL(10, 2) NOT NULL,  -- Amount paid
    payment_status ENUM('unpaid', 'paid', 'pending', 'failed', 'refunded', 'cancelled') DEFAULT 'unpaid',  -- Status of the payment
    payment_method ENUM('credit_card', 'paypal', 'cash_on_delivery', 'bank_transfer') NOT NULL,  -- Payment method used
    transaction_id VARCHAR(255) DEFAULT NULL,  -- Transaction ID for tracking payments
    payment_gateway VARCHAR(100) DEFAULT NULL,  -- Payment gateway used
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Timestamp when payment was created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Timestamp when payment was last updated

    -- Foreign keys
    CONSTRAINT fk_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,  -- Cascade delete when user is deleted
    CONSTRAINT fk_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,  -- Cascade delete when order is deleted

    -- Indexing
    INDEX idx_user_id (user_id),  -- Optimizes lookups by user ID
    INDEX idx_payment_status (payment_status)  -- Optimizes queries based on payment status
);
------------------------------------------------------------------------------------------------
-- Payment transactions table: Stores transaction details for payments
------------------------------------------------------------------------------------------------
CREATE TABLE payment_transactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each payment transaction
    payment_id BIGINT NOT NULL,  -- References the associated payment
    amount DECIMAL(10, 2) NOT NULL,  -- Transaction amount
    transaction_details TEXT NOT NULL,  -- Stores transaction details
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Timestamp when transaction was created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Timestamp when transaction was last updated

    -- Foreign Key
    CONSTRAINT fk_payment_id FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,  -- Cascade delete when payment is deleted

    -- Index
    INDEX idx_payment_id (payment_id)  -- Optimizes lookups based on payment ID
);
------------------------------------------------------------------------------------------------
-- Failed payments table: Stores details of failed payment attempts
------------------------------------------------------------------------------------------------
CREATE TABLE failed_payments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each failed payment
    user_id CHAR(26) NOT NULL,  -- References the user who attempted the payment
    order_id BIGINT NOT NULL,  -- References the associated order
    amount DECIMAL(10, 2) NOT NULL,  -- Payment amount attempted
    error_message TEXT NOT NULL,  -- Error details for failure
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Timestamp when failure was recorded
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Timestamp when failure was last updated

    -- Foreign Keys
    CONSTRAINT fk_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,  -- Cascade delete when user is deleted
    CONSTRAINT fk_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,  -- Cascade delete when order is deleted

    -- Indexes
    INDEX idx_user_id (user_id),  -- Optimizes lookups by user ID
    INDEX idx_order_id (order_id)  -- Optimizes lookups by order ID
);
------------------------------------------------------------------------------------------------
-- Payment gateway responses table: Stores responses from payment gateways
------------------------------------------------------------------------------------------------
CREATE TABLE payment_gateway_responses (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each gateway response
    payment_gateway_id BIGINT NOT NULL,  -- References the associated payment
    gateway_response JSON NOT NULL,  -- Stores the response from the payment gateway
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Timestamp when response was recorded
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Timestamp when response was last updated

    -- Foreign Keys
    CONSTRAINT fk_payment_gateway_id FOREIGN KEY (payment_gateway_id) REFERENCES payments(id) ON DELETE CASCADE,  -- Cascade delete when payment is deleted

    -- Index
    INDEX idx_payment_gateway_id (payment_gateway_id)  -- Optimizes lookups by payment gateway ID
);
------------------------------------------------------------------------------------------------

_______________________________________________________________________________________________________________________________