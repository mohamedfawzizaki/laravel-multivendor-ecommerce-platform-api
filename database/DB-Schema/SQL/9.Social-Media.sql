_______________________________________________________________________________________________________________________________
------------------------------------------------------------------------------------------------
CREATE TABLE social_media_platforms (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,  -- e.g., 'Facebook', 'Twitter', etc.
    api_url VARCHAR(255),               -- The URL for API requests (optional)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
------------------------------------------------------------------------------------------------
CREATE TABLE social_media_credentials (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    platform_id BIGINT NOT NULL,                           -- Foreign key to `social_media_platforms`
    user_id BIGINT NOT NULL,                               -- Foreign key to the `users` table
    username VARCHAR(255) NOT NULL,                        -- Username or account name for the social media
    encrypted_password TEXT NOT NULL,                      -- Encrypted password (do not store plaintext)
    access_token TEXT,                                     -- OAuth access token (encrypted)
    refresh_token TEXT,                                    -- Refresh token (encrypted)
    app_id VARCHAR(255),                                   -- App ID for the social media API (if applicable)
    app_secret VARCHAR(255),                               -- App secret for the social media API (if applicable)
    status ENUM('active', 'inactive') DEFAULT 'active',    -- Status of the credentials (active or inactive)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (platform_id) REFERENCES social_media_platforms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
------------------------------------------------------------------------------------------------
CREATE TABLE user_social_media_links (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,                            -- Foreign key to `users`
    platform_id BIGINT NOT NULL,                        -- Foreign key to `social_media_platforms`
    platform_username VARCHAR(255) NOT NULL,            -- Username on the platform
    linked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,      -- Date when the platform was linked
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (platform_id) REFERENCES social_media_platforms(id) ON DELETE CASCADE,
    UNIQUE (user_id, platform_id)                       -- Ensure a user can only link to a platform once
);
------------------------------------------------------------------------------------------------
_______________________________________________________________________________________________________________________________