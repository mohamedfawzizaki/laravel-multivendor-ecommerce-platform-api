_______________________________________________________________________________________________________________________________
------------------------------------------------------------------------------------------------
-- Carts Table: Stores products added to a shopping cart by users (both registered and guests).
------------------------------------------------------------------------------------------------
CREATE TABLE carts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(26) NULL COMMENT 'ULID format for registered users',
    session_id CHAR(36) NULL COMMENT 'UUID format for guest sessions',
    product_id BIGINT UNSIGNED NOT NULL,
    variant_id BIGINT UNSIGNED NULL COMMENT 'For product variants/sizes/colors',
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1 CHECK (quantity > 0 AND quantity <= 100),
    price DECIMAL(10, 2) NOT NULL COMMENT 'Snapshot of price at time of adding',
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    notes VARCHAR(500) NULL COMMENT 'Special instructions (500 char limit)',
    expires_at TIMESTAMP NULL DEFAULT (CURRENT_TIMESTAMP + INTERVAL 30 DAY),
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,

    -- Composite constraints
    CONSTRAINT chk_user_or_session CHECK (
        (user_id IS NOT NULL AND session_id IS NULL) OR 
        (user_id IS NULL AND session_id IS NOT NULL)
    ),
    
    -- Indexes
    INDEX idx_cart_user (user_id),
    INDEX idx_cart_session (session_id),
    INDEX idx_cart_expiry (expires_at),
    INDEX idx_cart_product (product_id),
    
    -- Unique constraints
    UNIQUE KEY uk_user_product_variant (user_id, product_id, variant_id),
    UNIQUE KEY uk_session_product_variant (session_id, product_id, variant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
------------------------------------------------------------------------------------------------
-- Wishlists Table: Stores products that users want to save for future purchases.
------------------------------------------------------------------------------------------------
CREATE TABLE wishlists (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    
    -- User/Session Identification
    user_id CHAR(26) NULL COMMENT 'ULID for authenticated users',
    session_id CHAR(36) NULL COMMENT 'UUID for guest sessions',
    
    -- Wishlist Metadata
    wishlist_name VARCHAR(100) NOT NULL DEFAULT 'Default',
    wishlist_slug VARCHAR(110) GENERATED ALWAYS AS (slugify(wishlist_name)) STORED,
    product_id BIGINT UNSIGNED NOT NULL,
    
    -- User Preferences
    notes VARCHAR(500) NULL COMMENT 'Short user notes',
    notify_preferences ENUM('none', 'discount', 'restock', 'both') DEFAULT 'none',
    
    -- Timestamps
    expires_at TIMESTAMP NULL COMMENT 'Auto-expire guest lists',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    
    -- Unique Constraints
    UNIQUE KEY uniq_user_list_product (user_id, wishlist_slug, product_id),
    UNIQUE KEY uniq_session_list_product (session_id, wishlist_slug, product_id),
    
    -- Validation Rules
    CONSTRAINT chk_ownership CHECK (
        (user_id IS NOT NULL AND session_id IS NULL) OR 
        (user_id IS NULL AND session_id IS NOT NULL)
    ),
    CONSTRAINT chk_list_name CHECK (
        wishlist_name REGEXP '^[A-Za-z0-9 ]{1,100}$'
    ),

    -- Indexes
    INDEX idx_user_wishlists (user_id),
    INDEX idx_session_wishlists (session_id),
    INDEX idx_wishlist_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
------------------------------------------------------------------------------------------------
_______________________________________________________________________________________________________________________________