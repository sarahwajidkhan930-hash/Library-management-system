-- Database Cleanup and Re-registration for Librarian ERP
-- Database: universal_db

-- 1. Identify the Librarian parent ID
SET @librarian_parent_id = 14;

-- 2. Clear existing sub-pages to avoid duplicates and confusion
DELETE FROM role_access WHERE page_id IN (12, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27);
DELETE FROM sys_pages WHERE id IN (12, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27);

-- 3. Re-register pages with clean properties under the correct parent
-- ID 12: Librarian Dashboard (Resetting it under 14)
INSERT INTO sys_pages (id, parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (12, 14, 'Dashboard', 'dashboards/librarian/librarian_dashboard.php', 'bi bi-speedometer2', 1);

-- ID 21: Inventory Management
INSERT INTO sys_pages (id, parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (21, 14, 'Inventory Management', 'dashboards/librarian/inventory_management.php', 'bi bi-stack', 10);

-- ID 20: Register Book
INSERT INTO sys_pages (id, parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (20, 14, 'Register Book', 'dashboards/librarian/register_book.php', 'bi bi-journal-plus', 15);

-- ID 22: Circulation Logs
INSERT INTO sys_pages (id, parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (22, 14, 'Circulation Logs', 'dashboards/librarian/circulation_logs.php', 'bi bi-arrow-left-right', 25);

-- ID 18: Student Directory
INSERT INTO sys_pages (id, parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (18, 14, 'Student Directory', 'dashboards/librarian/student_directory.php', 'bi bi-people', 30);

-- ID 27: Digital Audit Trail
INSERT INTO sys_pages (id, parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (27, 14, 'Digital Audit Trail', 'dashboards/librarian/digital_audit_trail.php', 'bi bi-shield-check', 40);

-- 4. Re-assign permissions for all the above IDs for 'librarian' and 'super_admin' roles
INSERT INTO role_access (role_key, page_id) 
SELECT 'librarian', id FROM sys_pages WHERE parent_id = 14;

INSERT INTO role_access (role_key, page_id) 
SELECT 'super_admin', id FROM sys_pages WHERE parent_id = 14;

-- Also Ensure the Parent itself has permissions
INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('librarian', 14), ('super_admin', 14);
