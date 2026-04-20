-- Register book_operations.php in the page access control system
-- Run this AFTER book_operations_schema.sql
-- NOTE: Parent is 'Librarian' (not 'Library')

SET @lib_parent_id = (SELECT id FROM sys_pages WHERE page_name = 'Librarian' LIMIT 1);

INSERT IGNORE INTO `sys_pages`
    (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`)
VALUES
    (@lib_parent_id,
     'Book Operations',
     'dashboards/librarian/book_operations.php',
     'bi bi-book-half',
     50);

SET @book_ops_id = LAST_INSERT_ID();

-- Grant access to Librarian, Assistant Manager, Super Admin
INSERT IGNORE INTO `role_access` (`role_key`, `page_id`) VALUES ('librarian',         @book_ops_id);
INSERT IGNORE INTO `role_access` (`role_key`, `page_id`) VALUES ('assistant_manager', @book_ops_id);
INSERT IGNORE INTO `role_access` (`role_key`, `page_id`) VALUES ('super_admin',       @book_ops_id);
