-- Migration: Create custom lists tables
-- Purpose: Allow users to create custom lists of books and movies
-- Created: 2026-02-10

-- Lists table
CREATE TABLE IF NOT EXISTS user_lists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL DEFAULT 1,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    list_type ENUM('books', 'movies', 'mixed') NOT NULL DEFAULT 'mixed',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_list_type (list_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- List items table (many-to-many relationship)
CREATE TABLE IF NOT EXISTS user_list_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    list_id INT NOT NULL,
    post_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (list_id) REFERENCES user_lists(id) ON DELETE CASCADE,
    UNIQUE KEY unique_list_item (list_id, post_id),
    INDEX idx_list_id (list_id),
    INDEX idx_post_id (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some default lists
INSERT INTO user_lists (user_id, name, description, list_type) VALUES
(1, 'Favorites', 'My all-time favorite books and movies', 'mixed'),
(1, 'To Read', 'Books I want to read', 'books'),
(1, 'Watchlist', 'Movies I want to watch', 'movies'),
(1, 'Re-watch/Re-read', 'Worth experiencing again', 'mixed')
ON DUPLICATE KEY UPDATE name=name;
