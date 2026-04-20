<?php
require 'd:/xampp/htdocs/universal/core/db.php';

try {
    $pdo->beginTransaction();

    // 1. Ensure Student Role exists (usually done by SQL but being safe)
    $stmt = $pdo->prepare("INSERT IGNORE INTO sys_roles (role_key, role_name, is_system_role) VALUES ('student', 'Student', 0)");
    $stmt->execute();

    // 2. Create a Test Student if not exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'student@test.com'");
    $stmt->execute();
    $studentId = $stmt->fetchColumn();

    if (!$studentId) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Test Student', 'student@test.com', password_hash('password123', PASSWORD_DEFAULT), 'student', 1]);
        $studentId = $pdo->lastInsertId();
    }

    // Also seed for Root Admin (ID 1) so the user can see it immediately if they are logged in as admin
    $targetUserIds = [1, $studentId];

    // 3. Sample Authors
    $authors = [
        ['J.K. Rowling', 'British author, best known for the Harry Potter series.'],
        ['George R.R. Martin', 'American novelist and short story writer in the fantasy genre.'],
        ['Agatha Christie', 'English writer known for her sixty-six detective novels.'],
        ['J.R.R. Tolkien', 'English writer, poet, philologist, and academic.']
    ];
    $authorIds = [];
    foreach ($authors as $a) {
        $stmt = $pdo->prepare("INSERT INTO lib_authors (author_name, biography) VALUES (?, ?)");
        $stmt->execute($a);
        $authorIds[] = $pdo->lastInsertId();
    }

    // 4. Sample Categories
    $categories = [
        ['Fantasy', 'Fantasy is a genre of speculative fiction.'],
        ['Mystery', 'A type of fiction in which a detective solves a crime.'],
        ['Adventure', 'Fiction that usually presents danger, or gives the reader a sense of excitement.'],
        ['Programming', 'Books about software development and computer science.']
    ];
    $categoryIds = [];
    foreach ($categories as $c) {
        $stmt = $pdo->prepare("INSERT INTO lib_categories (category_name, description) VALUES (?, ?)");
        $stmt->execute($c);
        $categoryIds[] = $pdo->lastInsertId();
    }

    // 5. Sample Books
    $books = [
        ['Harry Potter and the Sorcerer\'s Stone', $authorIds[0], $categoryIds[0], '9780439708180', 5],
        ['A Game of Thrones', $authorIds[1], $categoryIds[0], '9780553103540', 3],
        ['Murder on the Orient Express', $authorIds[2], $categoryIds[1], '9780007119318', 4],
        ['The Hobbit', $authorIds[3], $categoryIds[2], '9780618260300', 8],
        ['Clean Code', $authorIds[3], $categoryIds[3], '9780132350884', 2] // Misassigned author for variety
    ];
    $bookIds = [];
    foreach ($books as $b) {
        $stmt = $pdo->prepare("INSERT INTO lib_books (title, author_id, category_id, isbn, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(array_merge($b, [$b[4]]));
        $bookIds[] = $pdo->lastInsertId();
    }

    // 6. Borrowings for Target Users
    foreach ($targetUserIds as $uid) {
        // - 1 Overdue borrowing
        $stmt = $pdo->prepare("INSERT INTO lib_borrowings (user_id, book_id, borrow_date, due_date, status, fine_amount) VALUES (?, ?, ?, ?, 'overdue', 50.00)");
        $stmt->execute([$uid, $bookIds[0], date('Y-m-d', strtotime('-20 days')), date('Y-m-d', strtotime('-6 days'))]);

        // - 1 Active borrowing (On Loan)
        $stmt = $pdo->prepare("INSERT INTO lib_borrowings (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'borrowed')");
        $stmt->execute([$uid, $bookIds[1], date('Y-m-d', strtotime('-5 days')), date('Y-m-d', strtotime('+9 days'))]);

        // - 1 Returned borrowing (Normal)
        $stmt = $pdo->prepare("INSERT INTO lib_borrowings (user_id, book_id, borrow_date, due_date, return_date, status) VALUES (?, ?, ?, ?, ?, 'returned')");
        $stmt->execute([$uid, $bookIds[2], date('Y-m-d', strtotime('-30 days')), date('Y-m-d', strtotime('-16 days')), date('Y-m-d', strtotime('-17 days'))]);

        // - 1 Returned borrowing (Late with fine paid/accrued)
        $stmt = $pdo->prepare("INSERT INTO lib_borrowings (user_id, book_id, borrow_date, due_date, return_date, status, fine_amount) VALUES (?, ?, ?, ?, ?, 'returned', 25.00)");
        $stmt->execute([$uid, $bookIds[3], date('Y-m-d', strtotime('-40 days')), date('Y-m-d', strtotime('-26 days')), date('Y-m-d', strtotime('-20 days'))]);
    }

    $pdo->commit();
    echo "Seed successful! Created student user (ID: $studentId), 4 authors, 4 categories, 5 books, and " . (count($targetUserIds) * 4) . " borrowings." . PHP_EOL;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Seed failed: " . $e->getMessage() . PHP_EOL;
}
?>
