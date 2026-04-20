-- Database Migration for Library Management System
-- Database: universal_db

-- 1. Authors Table
CREATE TABLE IF NOT EXISTS `lib_authors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `author_name` VARCHAR(100) NOT NULL,
  `biography` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS `lib_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Books Table
CREATE TABLE IF NOT EXISTS `lib_books` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `author_id` INT,
  `category_id` INT,
  `isbn` VARCHAR(20),
  `total_copies` INT DEFAULT 1,
  `available_copies` INT DEFAULT 1,
  `cover_image` VARCHAR(255),
  `status` ENUM('available', 'low_stock', 'out_of_stock') DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`author_id`) REFERENCES `lib_authors`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`category_id`) REFERENCES `lib_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Borrowings Table
CREATE TABLE IF NOT EXISTS `lib_borrowings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `book_id` INT NOT NULL,
  `borrow_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `return_date` DATE DEFAULT NULL,
  `fine_amount` DECIMAL(10,2) DEFAULT 0.00,
  `status` ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `lib_books`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. System Integration (Pages & Roles)
-- Ensure 'student' role exists
INSERT IGNORE INTO `sys_roles` (`role_key`, `role_name`, `is_system_role`) VALUES ('student', 'Student', 0);

-- Library Parent Menu
INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) 
VALUES (0, 'Library', '#', 'bi bi-book-fill', 50);
SET @lib_parent_id = LAST_INSERT_ID();

-- Student Dashboard Page
INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) 
VALUES (@lib_parent_id, 'Library Dashboard', 'dashboards/student/student_dashboard.php', 'bi bi-speedometer2', 1);
SET @student_dash_id = LAST_INSERT_ID();

-- Manage Books (Admin Only)
INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) 
VALUES (@lib_parent_id, 'Manage Books', 'dashboards/admin/manage_books.php', 'bi bi-pencil-square', 2);
SET @manage_books_id = LAST_INSERT_ID();

-- Issue/Return Books (Admin Only)
INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) 
VALUES (@lib_parent_id, 'Issue/Return', 'dashboards/admin/borrowing.php', 'bi bi-arrow-left-right', 3);
SET @borrowing_id = LAST_INSERT_ID();

-- Assign Access
-- Student Access
INSERT INTO `role_access` (`role_key`, `page_id`) VALUES ('student', @lib_parent_id);
INSERT INTO `role_access` (`role_key`, `page_id`) VALUES ('student', @student_dash_id);

-- Super Admin Access (Inherits all)
INSERT INTO `role_access` (`role_key`, `page_id`) VALUES ('super_admin', @lib_parent_id);
INSERT INTO `role_access` (`role_key`, `page_id`) VALUES ('super_admin', @student_dash_id);
INSERT INTO `role_access` (`role_key`, `page_id`) VALUES ('super_admin', @manage_books_id);
INSERT INTO `role_access` (`role_key`, `page_id`) VALUES ('super_admin', @borrowing_id);
