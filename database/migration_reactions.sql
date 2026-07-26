-- Message Reactions table
CREATE TABLE IF NOT EXISTS `message_reactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `message_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `emoji` VARCHAR(10) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_reaction` (`message_id`, `user_id`),
    INDEX `idx_message` (`message_id`),
    CONSTRAINT `fk_reaction_message` FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reaction_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
