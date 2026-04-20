-- ============================================================
-- Book Operations Schema Updates
-- Run these against your `universal_db` database.
-- NOTE: category_id (FK to lib_categories) and is_issueable
--       already exist from schema_updates.sql. Only the
--       fine_per_day column is truly new.
-- ============================================================

-- 1. Add fine_per_day to lib_books (per-book fine rate)
--    Allows different book types (e.g. Reference) to carry
--    a higher fine rate than Textbooks.
ALTER TABLE `lib_books`
    ADD COLUMN `fine_per_day` DECIMAL(10,2) NOT NULL DEFAULT 10.00
    AFTER `is_issueable`;

-- 2. Add a book_type TEXT label column for quick categorisation
--    without requiring a lib_categories FK entry.
--    (e.g. "Reference", "Textbook", "Periodical", "Fiction")
ALTER TABLE `lib_books`
    ADD COLUMN `book_type` VARCHAR(50) DEFAULT 'Textbook'
    AFTER `fine_per_day`;

-- 3. (Re)create the overdue_alerts view used by the
--    notification widget in book_operations.php.
CREATE OR REPLACE VIEW `v_overdue_alerts` AS
    SELECT
        br.id            AS borrowing_id,
        u.id             AS student_id,
        u.name           AS student_name,
        u.email          AS student_email,
        b.id             AS book_id,
        b.title          AS book_title,
        b.fine_per_day,
        br.borrow_date,
        br.due_date,
        DATEDIFF(CURDATE(), br.due_date) AS days_overdue,
        ROUND(DATEDIFF(CURDATE(), br.due_date) * b.fine_per_day, 2) AS projected_fine
    FROM lib_borrowings br
    JOIN users u  ON br.user_id  = u.id
    JOIN lib_books b ON br.book_id = b.id
    WHERE br.status IN ('borrowed', 'overdue')
      AND br.due_date < CURDATE();
