_______________________________________________________________________________________________________________________________
------------------------------------------------------------------------------------------------
-- Carts Table: Stores products added to a shopping cart by users (both registered and guests).
------------------------------------------------------------------------------------------------
CREATE TABLE carts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,  
    user_id CHAR(26) NULL,  -- Nullable for guest users (ULID format, linked to registered users)
    session_id CHAR(36) NULL, -- Nullable session identifier for guest users (UUID format)
    product_id BIGINT UNSIGNED NOT NULL,  -- Foreign key reference to the products table
    quantity TINYINT UNSIGNED DEFAULT 1 CHECK (quantity > 0),  -- Enforces positive quantity values
    `notes` TEXT NULL,  -- User notes or special instructions
    `expires_at` TIMESTAMP NULL,  -- Expiry date for cart items
    `deleted_at` TIMESTAMP NULL,  -- Soft deletion timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Auto-sets the creation timestamp
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Auto-updates on changes

    -- Foreign key constraints:
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,  -- If a user is deleted, keep cart items but remove user reference
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,  -- If a product is deleted, remove all associated cart items

    -- Unique constraints to ensure a user or session cannot add the same product multiple times:
    UNIQUE KEY unique_cart_user_product (user_id, product_id),  -- Ensures one cart entry per user per product
    UNIQUE KEY unique_cart_session_product (session_id, product_id)  -- Ensures one cart entry per guest session per product
);
------------------------------------------------------------------------------------------------
-- Wishlists Table: Stores products that users want to save for future purchases.
------------------------------------------------------------------------------------------------
CREATE TABLE wishlists (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,  
    user_id CHAR(26) NULL,  -- Nullable user ID (ULID format) to allow anonymous wishlists
    wishlist_name VARCHAR(255) NOT NULL DEFAULT 'Default',  -- Allows users to create multiple wishlists
    product_id BIGINT UNSIGNED NOT NULL,  -- Foreign key reference to the products table
    `notes` TEXT NULL,  -- User notes or reminders
    `notify_on_discount` BOOLEAN DEFAULT FALSE,  -- Notify user on discount
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Auto-sets the creation timestamp
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Auto-updates on changes
    `deleted_at` TIMESTAMP NULL,  -- Soft deletion timestamp

    -- Foreign key constraints:
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,  -- If a user is deleted, keep wishlist items but remove user reference
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,  -- If a product is deleted, remove all associated wishlist entries

    -- Unique constraint to allow users to add the same product to different wishlists but not duplicate it in the same list:
    UNIQUE KEY unique_wishlist_user_product (user_id, product_id, wishlist_name)
);
------------------------------------------------------------------------------------------------
_______________________________________________________________________________________________________________________________