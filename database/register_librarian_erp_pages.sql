-- Ensure 'Librarian' parent menu exists and get its ID
SET @librarian_parent_id = (SELECT id FROM sys_pages WHERE page_name = 'Librarian' LIMIT 1);

-- 1. Register Book (Dedicated Form)
INSERT IGNORE INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (@librarian_parent_id, 'Register Book', 'dashboards/librarian/register_book.php', 'bi bi-journal-plus', 15);

-- 2. Inventory Management (Dedicated Data Table)
INSERT IGNORE INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (@librarian_parent_id, 'Inventory Management', 'dashboards/librarian/inventory_management.php', 'bi bi-stack', 10);

-- 3. Student Directory (Dedicated Data Table + Fine Clearing)
INSERT IGNORE INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (@librarian_parent_id, 'Student Directory', 'dashboards/librarian/student_directory.php', 'bi bi-people', 30);

-- 4. Circulation Logs (Dedicated History)
INSERT IGNORE INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (@librarian_parent_id, 'Circulation Logs', 'dashboards/librarian/circulation_logs.php', 'bi bi-arrow-left-right', 25);

-- 5. Digital Audit Trail (Security Logs)
INSERT IGNORE INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (@librarian_parent_id, 'Digital Audit Trail', 'dashboards/librarian/digital_audit_trail.php', 'bi bi-shield-check', 40);

-- Assign Access to Librarian and Super Admin for all pages under Librarian parent
INSERT IGNORE INTO role_access (role_key, page_id) 
SELECT 'librarian', id FROM sys_pages WHERE parent_id = @librarian_parent_id;

INSERT IGNORE INTO role_access (role_key, page_id) 
SELECT 'super_admin', id FROM sys_pages WHERE parent_id = @librarian_parent_id;
