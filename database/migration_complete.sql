-- =====================================================
-- ChatApp - Complete Migration
-- Run this once in phpMyAdmin
-- =====================================================

-- 1. Add auto_delete column to messages table
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages' AND COLUMN_NAME = 'auto_delete');
SET @sql = IF(@exists = 0, 
    'ALTER TABLE messages ADD COLUMN auto_delete ENUM(\'none\',\'24hours\',\'1day\',\'7days\',\'30days\') DEFAULT \'none\' AFTER deleted_for_receiver', 
    'SELECT "auto_delete column already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Create message_reactions table
CREATE TABLE IF NOT EXISTS message_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    emoji VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (message_id, user_id, emoji),
    KEY idx_message (message_id),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create typing_status table
CREATE TABLE IF NOT EXISTS typing_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    chat_with_user_id INT NOT NULL,
    is_typing TINYINT(1) DEFAULT 0,
    last_typing_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_typing (user_id, chat_with_user_id),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Create group_messages_read table
CREATE TABLE IF NOT EXISTS group_messages_read (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_read (group_id, message_id, user_id),
    KEY idx_group (group_id),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Add cover_photo column to users if missing
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'cover_photo');
SET @sql = IF(@exists = 0, 
    'ALTER TABLE users ADD COLUMN cover_photo VARCHAR(500) DEFAULT NULL AFTER avatar', 
    'SELECT "cover_photo column already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Add about column to users if missing
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'about');
SET @sql = IF(@exists = 0, 
    'ALTER TABLE users ADD COLUMN about TEXT DEFAULT NULL AFTER bio', 
    'SELECT "about column already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7. Add last_seen column to users if missing
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_seen');
SET @sql = IF(@exists = 0, 
    'ALTER TABLE users ADD COLUMN last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_online', 
    'SELECT "last_seen column already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 8. Add theme column to users if missing
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'theme');
SET @sql = IF(@exists = 0, 
    'ALTER TABLE users ADD COLUMN theme VARCHAR(50) DEFAULT \'dark\' AFTER status', 
    'SELECT "theme column already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 9. Add language column to users if missing
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'language');
SET @sql = IF(@exists = 0, 
    'ALTER TABLE users ADD COLUMN language VARCHAR(10) DEFAULT \'en\' AFTER theme', 
    'SELECT "language column already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 10. Add chat_style column to users if missing
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'chat_style');
SET @sql = IF(@exists = 0, 
    'ALTER TABLE users ADD COLUMN chat_style VARCHAR(20) DEFAULT \'bubbles\' AFTER language', 
    'SELECT "chat_style column already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 11. Add settings column to users if missing (JSON for extensibility)
SET @exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'settings');
SET @sql = IF(@exists = 0, 
    'ALTER TABLE users ADD COLUMN settings JSON DEFAULT NULL AFTER chat_style', 
    'SELECT "settings column already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
