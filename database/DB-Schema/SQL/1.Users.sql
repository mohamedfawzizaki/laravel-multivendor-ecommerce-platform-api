_______________________________________________________________________________________________________________________________
------------------------------------------------------------------------------------------------
-- This table stores user account details, including login credentials and account status.
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each user
    `username` VARCHAR(255) NOT NULL UNIQUE, -- Unique username for the user
    `email` VARCHAR(255) NOT NULL UNIQUE, -- Unique email address for authentication
    `password` VARCHAR(255) NOT NULL, -- Hashed password for security
    
    `role_id` BIGINT UNSIGNED NOT NULL, -- Foreign key reference to the user's role
    `status_id` BIGINT UNSIGNED NOT NULL, -- Foreign key reference to the user's account status

    `email_verified_at` DATETIME NULL, -- Timestamp of email verification (NULL = not verified)
    `email_verification_code` VARCHAR(6) NULL, -- OTP for email verification
    `email_verification_expires_at` DATETIME NULL; -- Expiration timestamp for OTP
    
    `remember_token` VARCHAR(255) NULL, -- Token used for "remember me" functionality in authentication


    `last_login_at` DATETIME NULL, -- Timestamp of the last login activity
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Timestamp when the user account was created
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Updates when account details change
    
    -- Foreign Key Constraints
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT, 
    FOREIGN KEY (`status_id`) REFERENCES `statuses`(`id`) ON DELETE RESTRICT, 

    -- Indexing for Performance Optimization
    INDEX idx_role_id (role_id), -- Speeds up queries filtering by role
    INDEX idx_status_id (status_id), -- Optimizes queries filtering by user status
    INDEX idx_last_login_at (last_login_at) -- Optimizes queries sorting or filtering by last login date
);
------------------------------------------------------------------------------------------------
-- Stores multiple phone numbers per user with verification
CREATE TABLE `user_phone_numbers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique ID for each phone record
    `user_id` BIGINT UNSIGNED NOT NULL, -- References the user
    `phone` VARCHAR(20) NOT NULL UNIQUE, -- User's phone number (must be unique across all users)
    `is_primary` BOOLEAN DEFAULT false, -- Indicates if this is the primary phone number
    `verified_at` DATETIME NULL, -- Timestamp when the phone was verified (NULL = not verified)
    `verification_code` VARCHAR(6) NULL, -- OTP for verification
    `verification_expires_at` DATETIME NULL, -- Expiration timestamp for OTP
    `verification_method` ENUM('sms', 'email') NOT NULL DEFAULT 'sms', -- How verification was done
    `deleted_at` DATETIME NULL, -- Soft delete timestamp

    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Record creation timestamp
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Last update timestamp

    -- Foreign Key Constraint: Ensures each phone number belongs to a valid user
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,

    -- Indexing for Performance Optimization
    INDEX idx_user_phone (user_id, phone), -- Optimizes searches for a user’s phone numbers
    INDEX idx_user_primary (user_id, is_primary), -- Optimizes searches for the primary phone number

    -- Ensure only one primary phone per user
    CONSTRAINT unique_primary_phone_per_user UNIQUE (user_id, is_primary) WHERE (is_primary = TRUE),

    -- Ensure only one verified phone per user
    CONSTRAINT unique_verified_phone_per_user UNIQUE (user_id, verified_at) WHERE (verified_at IS NOT NULL),

    -- A phone cannot be primary unless verified
    CHECK (is_primary = FALSE OR verified_at IS NOT NULL)
);
------------------------------------------------------------------------------------------------
-- This table stores additional user information such as names, gender, and bio.
CREATE TABLE `user_profiles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each user profile
    `user_id` BIGINT UNSIGNED NOT NULL UNIQUE, -- References the user this profile belongs to
    `profile_image` VARCHAR(255) NULL, -- URL to the user's profile image
    `first_name` VARCHAR(255) NULL, -- User's first name (optional)
    `last_name` VARCHAR(255) NULL, -- User's last name (optional)
    `gender` ENUM('male', 'female', 'other') NULL, -- User's gender selection
    `date_of_birth` DATE NULL, -- User's date of birth
    `bio` TEXT NULL, -- User's biography or description

    -- Foreign Key Constraint: Ensures each profile belongs to a valid user
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,

    -- Indexing for Performance Optimization
    INDEX idx_user_id (user_id) -- Optimizes user profile lookups
);
------------------------------------------------------------------------------------------------
-- Defines different user roles and permissions, supporting JSON-based permissions storage.
CREATE TABLE `roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each role
    -- `name` ENUM('customer', 'admin', 'seller') NOT NULL UNIQUE, -- Role name (must be unique)
    `name` VARCHAR(50) NOT NULL UNIQUE
    `description` VARCHAR(255) NULL
    `permissions` JSON NULL, -- Stores permissions in JSON format (e.g., `{"can_edit": true, "can_delete": false}`)
    
    -- Indexing for Performance Optimization
    INDEX idx_role_name (name) -- Speeds up role-based queries
);
------------------------------------------------------------------------------------------------
-- Tracks different statuses a user can have, such as "active" or "banned".
CREATE TABLE `statuses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each status
    `name` ENUM('active', 'inactive', 'banned') NOT NULL UNIQUE, -- Defines different account states
    `description` TEXT NULL, -- Optional description of what this status means
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP -- Timestamp when the status was last updated
);
------------------------------------------------------------------------------------------------
-- This table stores geographical location details (city, state, country) and coordinates (latitude/longitude).
CREATE TABLE `addresses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique address ID
    `city` VARCHAR(100) NOT NULL, -- City name
    `state` VARCHAR(100) NOT NULL, -- State or province
    `postal_code` VARCHAR(20) NOT NULL, -- ZIP or postal code
    `country` VARCHAR(100) NOT NULL, -- Country name
    `latitude` DECIMAL(9,6) NULL, -- Latitude coordinate
    `longitude` DECIMAL(9,6) NULL, -- Longitude coordinate

    -- Indexing for faster searches
    INDEX idx_city_state (city, state),
    INDEX idx_country (country)
);
------------------------------------------------------------------------------------------------
-- This table allows users to save multiple addresses, with labels such as "Home" or "Work," and soft deletes.
CREATE TABLE `user_addresses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, -- Unique user address ID
    `user_id` BIGINT UNSIGNED NOT NULL, -- References the user
    `address_id` BIGINT UNSIGNED NOT NULL, -- Links to an address record
    `label` ENUM('Home', 'Work', 'Other') DEFAULT 'Home', -- Address label
    `address_line1` VARCHAR(255) NOT NULL, -- Detailed street address
    `address_line2` VARCHAR(255) NULL, -- Additional details
    `is_default` BOOLEAN DEFAULT false, -- Marks primary address

    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL, -- Soft delete support

    -- Foreign Keys
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`address_id`) REFERENCES `addresses`(`id`) ON DELETE RESTRICT,

    -- Indexing for faster lookups
    INDEX idx_user_default (user_id, is_default),
    INDEX idx_user_label (user_id, label)
);
CREATE UNIQUE INDEX unique_default_address ON `user_addresses` (user_id) 
WHERE is_default = TRUE;
----------------------------------------------------------------------------------------------
_______________________________________________________________________________________________________________________________
------------------------------------------------------------------------------------------------
-- Stores password reset requests securely.
CREATE TABLE `password_reset_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `token` VARCHAR(255) NOT NULL,                                   -- Hashed token for security
    `email` VARCHAR(255) NOT NULL,                                   -- Email for reset request
    `user_id` BIGINT UNSIGNED NOT NULL,                             -- References the user requesting the reset
    `status` ENUM('active', 'used', 'expired') DEFAULT 'active',      -- Status of the token
    `reset_attempts` INT DEFAULT 0,                                  -- Number of reset attempts within a window
    `last_attempt_at` TIMESTAMP NULL,                                -- Timestamp for last reset attempt
    `expires_at` TIMESTAMP NULL,                                     -- Expiry date for reset token
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,                -- Created timestamp
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Update timestamp
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE, -- Foreign key reference to users
    -- Indices for efficient queries
    INDEX idx_password_reset_email (email),  -- Index for searching reset tokens by email
    INDEX idx_password_reset_status (status), -- Index for searching tokens by status
    UNIQUE KEY idx_password_reset_email_token (email, token) -- Unique combination of email and token
);
------------------------------------------------------------------------------------------------
_______________________________________________________________________________________________________________________________
------------------------------------------------------------------------------------------------
-- devices table (to normalize device information)
CREATE TABLE `devices` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `device_id` VARCHAR(255) NOT NULL, -- Unique device identifier
    `device_type` ENUM('mobile', 'desktop', 'tablet') NULL, -- Device type
    `user_agent` VARCHAR(255) NULL, -- User agent for this device
    `ip_address` VARCHAR(45) NULL,  -- Supports IPv6
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Index for quick lookups
    INDEX idx_device_id (`device_id`),
    INDEX idx_device_type (`device_type`),
    INDEX idx_ip_address (`ip_address`)
);
------------------------------------------------------------------------------------------------
-- auth_tokens table (reference to devices table)
CREATE TABLE `auth_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `token` VARCHAR(255) NOT NULL UNIQUE, -- Ensure token is securely hashed
    `user_id` CHAR(26) NOT NULL,  -- User reference (ULID assumed for user_id)
    `device_id` BIGINT UNSIGNED NOT NULL, -- Foreign key to devices table
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign keys
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE,

    -- Indexes for performance
    INDEX idx_expires_at (`expires_at`)
);
------------------------------------------------------------------------------------------------
-- sessions table (normalized device info via devices table)
CREATE TABLE `sessions` (
    `id` CHAR(36) NOT NULL PRIMARY KEY,  -- UUID session ID
    `user_id` CHAR(26) NOT NULL,         -- User reference (ULID assumed)
    `device_id` BIGINT UNSIGNED NOT NULL, -- Foreign key to devices table
    `payload` LONGTEXT NOT NULL,         -- Stores session data
    `last_activity` TIMESTAMP NOT NULL,  -- For session expiration checks
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign keys
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE,

    -- Indexes for query performance
    INDEX idx_last_activity (`last_activity`)
);
------------------------------------------------------------------------------------------------
-- two_factor_auth table (normalized backup codes into a separate table)
CREATE TABLE `two_factor_auth` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` CHAR(26) NOT NULL,   -- User reference (ULID assumed)
    `secret_key` CHAR(64) NOT NULL, -- 2FA secret key
    `is_enabled` BOOLEAN DEFAULT FALSE, -- Whether 2FA is enabled
    `expires_at` DATETIME NULL,     -- Expiry time for OTP or 2FA session
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign key
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,

    -- Indexes for optimized querying
    INDEX idx_user_id (`user_id`),
    INDEX idx_expires_at (`expires_at`)
);
------------------------------------------------------------------------------------------------
-- -- two_factor_auth_backup_codes table (normalized backup codes)
-- CREATE TABLE `two_factor_auth_backup_codes` (
--     `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
--     `two_factor_auth_id` BIGINT UNSIGNED NOT NULL,  -- Foreign key to two_factor_auth
--     `backup_code` VARCHAR(255) NOT NULL, -- Backup code
--     `is_used` BOOLEAN DEFAULT FALSE, -- Whether the backup code has been used
--     `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
--     -- Foreign key
--     FOREIGN KEY (`two_factor_auth_id`) REFERENCES `two_factor_auth`(`id`) ON DELETE CASCADE,

--     -- Indexes for querying backup codes
--     INDEX idx_backup_code (`backup_code`)
-- );
------------------------------------------------------------------------------------------------
_______________________________________________________________________________________________________________________________
------------------------------------------------------------------------------------------------
-- User Activity Logs table (references devices and users)
CREATE TABLE `user_activity_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,  -- Auto-increment primary key
    `user_id` CHAR(26) NOT NULL,  -- User reference (assuming ULID for user_id)
    `device_id` BIGINT UNSIGNED NULL,  -- Foreign key to devices table
    `action` VARCHAR(100) NOT NULL,  -- Action performed (e.g., login, logout)
    `description` TEXT NULL,  -- Optional description of the activity
    `resource_type` VARCHAR(100) NULL,  -- Type of resource being acted upon
    `resource_id` BIGINT UNSIGNED NULL,  -- ID of the resource
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Timestamp for when activity occurred

    -- Foreign key constraints
    FOREIGN KEY (user_id) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES `devices`(`id`) ON DELETE SET NULL,

    -- Indexes for optimized queries
    INDEX idx_user_id (`user_id`),
    INDEX idx_action (`action`),
    INDEX idx_created_at (`created_at`),
    INDEX idx_resource (`resource_type`, `resource_id`),
    INDEX idx_user_activity_user_created (`user_id`, `created_at`)  -- Compound index for recent activities
);
------------------------------------------------------------------------------------------------
-- Login Attempts table (references devices and users)
CREATE TABLE `login_attempts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,  -- Auto-increment primary key
    `email` VARCHAR(255) NOT NULL,  -- Email for unauthenticated attempts
    `user_id` CHAR(26) NULL,  -- Foreign key reference to users table (nullable for failed logins)
    `device_id` BIGINT UNSIGNED NULL,  -- Foreign key reference to devices table
    `success` BOOLEAN DEFAULT FALSE,  -- True for successful, false for failed login attempt
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Timestamp of the attempt

    -- Foreign key constraints
    FOREIGN KEY (user_id) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES `devices`(`id`) ON DELETE SET NULL,

    -- Indexes for optimized searches
    INDEX idx_email (`email`),  -- Index for email field to speed up lookups
    INDEX idx_success (`success`),  -- Index for success field
    INDEX idx_attempted_at (`attempted_at`)  -- Index for time-based searches
);
------------------------------------------------------------------------------------------------
_______________________________________________________________________________________________________________________________