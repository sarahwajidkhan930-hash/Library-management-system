-- 1. Create Reservations Table
CREATE TABLE IF NOT EXISTS `lib_reservations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `book_id` INT NOT NULL,
  `reserved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending', 'fulfilled', 'cancelled') DEFAULT 'pending',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `lib_books`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Register Sidebar Points (Under Library Parent)
-- Find the parent ID for 'Library'
SET @lib_parent_id = (SELECT id FROM sys_pages WHERE page_name = 'Library' AND parent_id = 0 LIMIT 1);

-- Insert Points if not exists
INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) 
VALUES (@lib_parent_id, 'My Reservations', 'dashboards/student/student_dashboard.php#reservations', 'bi bi-calendar-check', 10);
SET @res_id = LAST_INSERT_ID();

INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) 
VALUES (@lib_parent_id, 'Fine Payments', 'dashboards/student/student_dashboard.php#fines', 'bi bi-credit-card', 11);
SET @fine_id = LAST_INSERT_ID();

INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) 
VALUES (@lib_parent_id, 'Reading History', 'dashboards/student/student_dashboard.php#history', 'bi bi-journal-text', 12);
SET @hist_id = LAST_INSERT_ID();

-- Assign Access to Student Role
INSERT IGNORE INTO `role_access` (`role_key`, `page_id`) VALUES ('student', @res_id);
INSERT IGNORE INTO `role_access` (`role_key`, `page_id`) VALUES ('student', @fine_id);
INSERT IGNORE INTO `role_access` (`role_key`, `page_id`) VALUES ('student', @hist_id);
