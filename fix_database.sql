-- Fix Database Issues
-- This file adds missing tables and columns needed for the extension and notification system

-- 1. Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Add missing columns to extension_requests table
ALTER TABLE `extension_requests` 
ADD COLUMN IF NOT EXISTS `fine_amount` decimal(6,2) DEFAULT 0.00 AFTER `status`,
ADD COLUMN IF NOT EXISTS `extension_days` int(11) DEFAULT 0 AFTER `fine_amount`,
ADD COLUMN IF NOT EXISTS `fine_id` int(11) DEFAULT NULL AFTER `extension_days`;

-- 3. Update payments table to support extension_fine type
ALTER TABLE `payments` 
MODIFY COLUMN `payment_type` enum('fine','reservation','ebook','extension_fine') NOT NULL;

-- 4. Add missing columns to borrow_records table
ALTER TABLE `borrow_records` 
ADD COLUMN IF NOT EXISTS `fine_paid` tinyint(1) DEFAULT 0 AFTER `fine_paid_date`;

-- 5. Update reservations table status enum
ALTER TABLE `reservations` 
MODIFY COLUMN `status` enum('pending','active','cancelled','fulfilled') DEFAULT 'pending';

-- 6. Add indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_notifications_user_read` ON `notifications` (`user_id`, `is_read`);
CREATE INDEX IF NOT EXISTS `idx_extension_requests_user_status` ON `extension_requests` (`user_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_fines_borrow_paid` ON `fines` (`borrow_id`, `paid`);

-- 7. Insert sample notification for testing (optional)
-- INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`) VALUES 
-- (6, 'Welcome to Book Stop', 'Welcome to our library management system!', 'welcome'); 