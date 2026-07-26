-- Auto-delete messages column
ALTER TABLE `messages` 
ADD COLUMN `auto_delete` ENUM('none', '24hours', '1day', '7days', '30days') DEFAULT 'none' AFTER `seen_at`;

-- Cleanup API: Delete messages older than their auto_delete period
-- Run via cron: php api/cleanup-auto-delete.php
