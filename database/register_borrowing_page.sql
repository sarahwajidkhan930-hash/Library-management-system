-- Register the Borrowing & Returns page
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) 
SELECT id, 'Borrowing & Returns', 'dashboards/librarian/borrowing.php', 'bi bi-arrow-left-right', 20 
FROM sys_pages WHERE page_name = 'Librarian' LIMIT 1;

-- Assign access to librarian and super_admin
SET @borrow_page_id = LAST_INSERT_ID();
INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('librarian', @borrow_page_id);
INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('super_admin', @borrow_page_id);
