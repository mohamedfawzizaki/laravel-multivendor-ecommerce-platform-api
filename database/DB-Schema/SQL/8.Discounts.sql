_______________________________________________________________________________________________________________________________
-- Table to store coupon details
CREATE TABLE coupons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each coupon
    code VARCHAR(255) UNIQUE NOT NULL, -- Unique coupon code
    description TEXT NULL, -- Optional description of the coupon
    discount_type ENUM('percentage', 'fixed') NOT NULL, -- Type of discount (percentage or fixed amount)
    discount_value DECIMAL(10,2) NOT NULL, -- Discount value applied when the coupon is used
    min_order_amount DECIMAL(10,2) DEFAULT 0 NOT NULL, -- Minimum order amount required to use the coupon
    max_discount_amount DECIMAL(10,2) NULL, -- Maximum discount amount allowed for percentage-based discounts
    usage_limit INT UNSIGNED NULL, -- Maximum number of times the coupon can be used overall
    user_usage_limit INT UNSIGNED NULL, -- Maximum number of times a single user can use the coupon
    status ENUM('active', 'inactive') DEFAULT 'active' NOT NULL, -- Coupon status
    start_date TIMESTAMP NULL DEFAULT NULL, -- Start date when the coupon becomes valid
    end_date TIMESTAMP NULL DEFAULT NULL, -- End date when the coupon expires
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the coupon is created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- Timestamp when the coupon is last updated
);
------------------------------------------------------------------------------------------------
-- Table to define conditions for coupon usage
CREATE TABLE coupon_conditions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each condition
    coupon_id BIGINT UNSIGNED NOT NULL, -- Foreign key referencing the coupons table
    condition_type ENUM('first_time_user', 'shipping_discount', 'min_order_value') NOT NULL, -- Type of condition
    condition_value VARCHAR(255) NULL, -- Condition value (e.g., min order amount, user type)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the condition is created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Timestamp when the condition is last updated
    CONSTRAINT fk_coupon_conditions FOREIGN KEY (coupon_id) REFERENCES coupons (id) ON DELETE CASCADE -- Ensure referential integrity
);
------------------------------------------------------------------------------------------------
-- Table to specify applicable products for coupons
CREATE TABLE coupon_applicable_products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, -- Unique identifier
    coupon_id BIGINT UNSIGNED NOT NULL, -- Foreign key referencing the coupons table
    product_id BIGINT UNSIGNED NOT NULL, -- Foreign key referencing the products table
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the record is created
    CONSTRAINT fk_coupon_product FOREIGN KEY (coupon_id) REFERENCES coupons (id) ON DELETE CASCADE, -- Maintain referential integrity
    CONSTRAINT fk_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE -- Ensure product reference is valid
);
------------------------------------------------------------------------------------------------
-- Table to specify applicable categories for coupons
CREATE TABLE coupon_applicable_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, -- Unique identifier
    coupon_id BIGINT UNSIGNED NOT NULL, -- Foreign key referencing the coupons table
    category_id BIGINT UNSIGNED NOT NULL, -- Foreign key referencing the categories table
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the record is created
    CONSTRAINT fk_coupon_category FOREIGN KEY (coupon_id) REFERENCES coupons (id) ON DELETE CASCADE, -- Ensure valid coupon reference
    CONSTRAINT fk_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE -- Ensure valid category reference
);
------------------------------------------------------------------------------------------------
-- Table to track coupon redemptions
CREATE TABLE coupon_redemptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, -- Unique identifier
    coupon_id BIGINT UNSIGNED NOT NULL, -- Foreign key referencing the coupons table
    user_id CHAR(36) NOT NULL, -- ULID for user identification
    order_id BIGINT UNSIGNED NOT NULL, -- Foreign key referencing the orders table
    amount_saved DECIMAL(10,2) NOT NULL, -- Amount saved using the coupon
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL, -- Timestamp when the coupon is used
    redemption_status ENUM('redeemed', 'failed') DEFAULT 'redeemed' NOT NULL, -- Status of the redemption
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the record is created
    INDEX idx_coupon_id (coupon_id), -- Index for faster lookup by coupon ID
    INDEX idx_user_id (user_id), -- Index for faster lookup by user ID
    INDEX idx_order_id (order_id), -- Index for faster lookup by order ID
    UNIQUE KEY unique_coupon_user (coupon_id, user_id), -- Ensure a user can use a coupon only once
    UNIQUE KEY unique_coupon_order (order_id, coupon_id), -- Ensure a coupon is only used once per order
    CONSTRAINT fk_redemptions_coupon FOREIGN KEY (coupon_id) REFERENCES coupons (id) ON DELETE CASCADE, -- Maintain integrity
    CONSTRAINT fk_redemptions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE, -- Maintain user reference
    CONSTRAINT fk_redemptions_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE -- Maintain order reference
);
------------------------------------------------------------------------------------------------
-- Table to store referral programs
CREATE TABLE referral_programs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for referral program
    name VARCHAR(255) UNIQUE NOT NULL, -- Name of the referral program
    description TEXT, -- Optional description of the program
    reward_type ENUM('discount', 'credit') NOT NULL, -- Type of reward
    reward_value DECIMAL(10, 2) NOT NULL, -- Reward value for the program
    min_order_value DECIMAL(10, 2), -- Minimum order value to qualify for the reward
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the program is created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- Timestamp when the program is updated
);
------------------------------------------------------------------------------------------------
-- Table to track user referrals
CREATE TABLE user_referrals (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for user referral
    referral_program_id BIGINT NOT NULL, -- Foreign key referencing referral programs
    referrer_user_id CHAR(26) NOT NULL, -- ULID of the referring user
    referred_user_id CHAR(26) NOT NULL, -- ULID of the referred user
    status ENUM('pending', 'claimed', 'expired') DEFAULT 'pending', -- Status of the referral
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the referral is created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Timestamp when referral status is updated
    FOREIGN KEY (referral_program_id) REFERENCES referral_programs(id) ON DELETE CASCADE, -- Ensure valid referral program
    FOREIGN KEY (referrer_user_id) REFERENCES users(id) ON DELETE CASCADE, -- Ensure valid referrer
    FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE, -- Ensure valid referred user
    UNIQUE (referrer_user_id, referred_user_id) -- Ensure a user cannot refer the same person twice
);
------------------------------------------------------------------------------------------------
-- Table to store referral rewards
CREATE TABLE referral_rewards (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for referral reward
    user_referral_id BIGINT NOT NULL, -- Foreign key referencing user referrals
    user_id CHAR(26) NOT NULL, -- ULID of the user receiving the reward
    reward_type ENUM('discount', 'credit') NOT NULL, -- Type of reward (discount or credit)
    reward_amount DECIMAL(10, 2) NOT NULL, -- Reward amount
    status ENUM('pending', 'approved', 'used') DEFAULT 'pending', -- Status of the reward
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the reward is created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Timestamp when reward status is updated
    FOREIGN KEY (user_referral_id) REFERENCES user_referrals(id) ON DELETE CASCADE, -- Ensure valid referral reference
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE -- Ensure valid user reference
);

_______________________________________________________________________________________________________________________________