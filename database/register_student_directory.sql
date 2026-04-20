-- Register the Student Directory page
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) 
SELECT id, 'Student Directory', 'dashboards/librarian/student_directory.php', 'bi bi-people', 30 
FROM sys_pages WHERE page_name = 'Librarian' LIMIT 1;

-- Assign access to librarian and super_admin
SET @student_dir_id = LAST_INSERT_ID();
INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('librarian', @student_dir_id);
INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('super_admin', @student_dir_id);
