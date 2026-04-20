-- Register book_categories.php in the page access control system
-- Run this in phpMyAdmin against your universal_db database
-- NOTE: Parent is 'Librarian' (not 'Library')

SET @lib_parent_id = (SELECT id FROM sys_pages WHERE page_name = 'Librarian' LIMIT 1);

INSERT IGNORE INTO `sys_pages`
    (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`)
VALUES
    (@lib_parent_id,
     'Book Categories',
     'dashboards/librarian/book_categories.php',
     'bi bi-tags-fill',
     55);

SET @book_cat_id = LAST_INSERT_ID();

-- Grant access to Librarian, Assistant Manager, Super Admin
INSERT IGNORE INTO `role_access` (`role_key`, `page_id`) VALUES ('librarian',         @book_cat_id);
INSERT IGNORE INTO `role_access` (`role_key`, `page_id`) VALUES ('assistant_manager', @book_cat_id);
INSERT IGNORE INTO `role_access` (`role_key`, `page_id`) VALUES ('super_admin',       @book_cat_id);
