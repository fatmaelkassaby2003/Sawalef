-- Add data column to site_notifications table
-- This allows storing complete notification payload including call data

ALTER TABLE `site_notifications` 
ADD COLUMN `data` JSON NULL AFTER `type`;

-- Optional: Add index for better query performance on type
-- ALTER TABLE `site_notifications` ADD INDEX `idx_type` (`type`);
