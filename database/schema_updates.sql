-- Database Updates for Library ERP Enhancements

-- 1. Add is_issueable to lib_books
ALTER TABLE `lib_books` 
ADD COLUMN `is_issueable` TINYINT(1) DEFAULT 1 AFTER `status`;

-- 2. Add fines to users table if not exists (for student directory integration)
ALTER TABLE `users` 
ADD COLUMN `fines` DECIMAL(10,2) DEFAULT 0.00 AFTER `identity_no`;

-- 3. Create audit_logs table (standardized for all activities)
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `activity` VARCHAR(255) NOT NULL,
  `notes` TEXT,
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
