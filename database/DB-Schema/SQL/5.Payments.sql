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

------------------------------------------------------------------------------------------------
-- Payments table (Enhanced)
------------------------------------------------------------------------------------------------
CREATE TABLE payments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(26) NOT NULL,
    order_id BIGINT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL COMMENT 'Amount in original currency',
    currency CHAR(3) NOT NULL DEFAULT 'USD' COMMENT 'ISO 4217 currency code',
    base_amount DECIMAL(12, 2) NOT NULL COMMENT 'Amount converted to base currency',
    exchange_rate DECIMAL(12, 6) DEFAULT 1.0,
    
    payment_status ENUM(
        'pending', 
        'authorized', 
        'captured', 
        'partially_refunded', 
        'fully_refunded', 
        'failed', 
        'voided',
        'disputed',
        'chargeback'
    ) DEFAULT 'pending',
    
    payment_method ENUM(
        'credit_card', 
        'debit_card', 
        'paypal', 
        'bank_transfer',
        'digital_wallet',
        'crypto',
        'cash_on_delivery',
        'installment'
    ) NOT NULL,
    
    payment_method_details JSON COMMENT 'Stores method-specific details',
    payment_gateway VARCHAR(50) NOT NULL,
    gateway_reference VARCHAR(255) UNIQUE,
    
    fraud_score TINYINT UNSIGNED DEFAULT NULL,
    
    captured_at TIMESTAMP NULL DEFAULT NULL,
    refunded_at TIMESTAMP NULL DEFAULT NULL,
    failed_at TIMESTAMP NULL DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_payment_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,

    INDEX idx_payment_user_id (user_id),
    INDEX idx_payment_order_id (order_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_payment_gateway_reference (gateway_reference),
    INDEX idx_payment_created_at (created_at),
    INDEX idx_payment_gateway (payment_gateway)
) ENGINE=InnoDB ROW_FORMAT=COMPRESSED;

------------------------------------------------------------------------------------------------
-- Payment transactions table (Enhanced)
------------------------------------------------------------------------------------------------
CREATE TABLE payment_transactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    payment_id BIGINT NOT NULL,
    parent_transaction_id BIGINT NULL COMMENT 'For refunds/voids referencing original',
    
    transaction_type ENUM(
        'authorization', 
        'capture', 
        'refund', 
        'void', 
        'chargeback',
        'dispute',
        'adjustment'
    ) NOT NULL,
    
    amount DECIMAL(12, 2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    gateway_transaction_id VARCHAR(255) NOT NULL,
    gateway_status VARCHAR(50) NOT NULL,
    gateway_response_code VARCHAR(50) DEFAULT NULL,
    gateway_response TEXT DEFAULT NULL,
    
    is_success BOOLEAN NOT NULL DEFAULT FALSE,
    requires_action BOOLEAN NOT NULL DEFAULT FALSE,
    action_url VARCHAR(512) DEFAULT NULL,
    
    metadata JSON DEFAULT NULL,
    processed_by VARCHAR(50) DEFAULT NULL COMMENT 'System/Admin who processed',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_payment_transaction_payment_id FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_transaction_parent_id FOREIGN KEY (parent_transaction_id) REFERENCES payment_transactions(id) ON DELETE SET NULL,

    INDEX idx_payment_transaction_payment_id (payment_id),
    INDEX idx_payment_transaction_parent_id (parent_transaction_id),
    INDEX idx_payment_transaction_gateway_id (gateway_transaction_id),
    INDEX idx_payment_transaction_created_at (created_at),
    INDEX idx_payment_transaction_type (transaction_type),
    INDEX idx_payment_transaction_success (is_success),
    INDEX idx_payment_transaction_gateway_status (gateway_status)
) ENGINE=InnoDB ROW_FORMAT=COMPRESSED;

------------------------------------------------------------------------------------------------
-- Payment errors table (Replaces failed_payments with more comprehensive tracking)
------------------------------------------------------------------------------------------------
CREATE TABLE payment_errors (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    payment_id BIGINT NULL COMMENT 'Null if payment record not created',
    user_id CHAR(26) NOT NULL,
    order_id BIGINT NULL COMMENT 'Null if order not created yet',
    
    error_type ENUM(
        'validation',
        'gateway',
        'fraud',
        'authentication',
        'insufficient_funds',
        'system',
        'timeout'
    ) NOT NULL,
    
    error_code VARCHAR(50) NOT NULL,
    error_message TEXT NOT NULL,
    gateway_response JSON DEFAULT NULL,
    is_recoverable BOOLEAN DEFAULT FALSE,
    retry_count TINYINT UNSIGNED DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_payment_error_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_error_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    CONSTRAINT fk_payment_error_payment_id FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,

    INDEX idx_payment_error_user_id (user_id),
    INDEX idx_payment_error_order_id (order_id),
    INDEX idx_payment_error_payment_id (payment_id),
    INDEX idx_payment_error_type (error_type),
    INDEX idx_payment_error_code (error_code),
    INDEX idx_payment_error_created_at (created_at)
) ENGINE=InnoDB ROW_FORMAT=COMPRESSED;

------------------------------------------------------------------------------------------------
-- Payment gateway logs table (Enhanced)
------------------------------------------------------------------------------------------------
CREATE TABLE payment_gateway_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    payment_id BIGINT NULL,
    transaction_id BIGINT NULL,
    
    direction ENUM('request', 'response') NOT NULL,
    url VARCHAR(512) NOT NULL,
    method VARCHAR(10) NOT NULL,
    status_code SMALLINT UNSIGNED DEFAULT NULL,
    headers JSON DEFAULT NULL,
    body LONGTEXT DEFAULT NULL,
    response_time_ms INT UNSIGNED DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_gateway_log_payment_id FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    CONSTRAINT fk_gateway_log_transaction_id FOREIGN KEY (transaction_id) REFERENCES payment_transactions(id) ON DELETE SET NULL,

    INDEX idx_gateway_log_payment_id (payment_id),
    INDEX idx_gateway_log_transaction_id (transaction_id),
    INDEX idx_gateway_log_created_at (created_at),
    INDEX idx_gateway_log_direction (direction)
) ENGINE=InnoDB ROW_FORMAT=COMPRESSED;