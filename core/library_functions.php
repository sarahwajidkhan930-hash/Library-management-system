<?php
require_once __DIR__ . '/db.php';

// Prevent direct access to the core file and redirect to the dashboard
if (basename($_SERVER['PHP_SELF']) == 'library_functions.php') {
    header("Location: ../dashboards/librarian/librarian_dashboard.php");
    exit;
}

class Library {
    private $pdo;
    private $fine_rate = 10; // Rs. 10 per day

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function calculateFine($borrowingId, $returnDate) {
        $stmt = $this->pdo->prepare("SELECT due_date FROM lib_borrowings WHERE id = ?");
        $stmt->execute([$borrowingId]);
        $dueDate = $stmt->fetchColumn();
        
        if (!$dueDate || strtotime($returnDate) <= strtotime($dueDate)) {
            return 0;
        }

        $daysDiff = floor((strtotime($returnDate) - strtotime($dueDate)) / (60 * 60 * 24));
        return $daysDiff * $this->fine_rate;
    }

    public function updateOverdueStatus() {
        $today = date('Y-m-d');
        // 1. Identify items transitioning to OVERDUE for the first time in this check
        $stmt = $this->pdo->prepare("SELECT id, user_id, book_id FROM lib_borrowings WHERE status = 'borrowed' AND due_date < ?");
        $stmt->execute([$today]);
        $toFlag = $stmt->fetchAll();

        foreach ($toFlag as $item) {
            // 2. Perform granular update
            $uStmt = $this->pdo->prepare("UPDATE lib_borrowings SET status = 'overdue' WHERE id = ?");
            if ($uStmt->execute([$item['id']])) {
                // 3. Document in Audit Trail (Integrated Sync)
                $this->logAction($item['book_id'], 'AUTOMATED_OVERDUE', "System flagged Loan #{$item['id']} as OVERDUE for User #{$item['user_id']}");
            }
        }
        return true;
    }

    // --- Librarian ---
    
    public function getBooks($search = '') {
        $sql = "SELECT b.*, a.author_name, c.category_name 
                FROM lib_books b
                LEFT JOIN lib_authors a ON b.author_id = a.id
                LEFT JOIN lib_categories c ON b.category_id = c.id";
        if (!empty($search)) {
            $sql .= " WHERE b.title LIKE :s OR a.author_name LIKE :s OR c.category_name LIKE :s";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':s' => "%$search%"]);
        } else {
            $stmt = $this->pdo->query($sql);
        }
        return $stmt->fetchAll();
    }

    // --- Student Stats ---

    public function getStudentStats($userId) {
        $stats = [];
        
        // Total Borrowed
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM lib_borrowings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['total_borrowed'] = $stmt->fetchColumn();

        // Currently Held
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM lib_borrowings WHERE user_id = ? AND status = 'borrowed'");
        $stmt->execute([$userId]);
        $stats['currently_held'] = $stmt->fetchColumn();

        // Overdue Count (Real-time detection)
        $today = date('Y-m-d');
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM lib_borrowings WHERE user_id = ? AND status != 'returned' AND due_date < ?");
        $stmt->execute([$userId, $today]);
        $stats['overdue_count'] = $stmt->fetchColumn();

        // Total Fines calculation:
        // 1. Settled balance from user profile (unpaid previous fines)
        $stmt = $this->pdo->prepare("SELECT fines FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $settledFines = (float)($stmt->fetchColumn() ?? 0.00);

        // 2. Accrued fines (Current debt for unreturned overdue books)
        $accruedFines = 0;
        $stmt = $this->pdo->prepare("SELECT due_date FROM lib_borrowings WHERE user_id = ? AND status != 'returned' AND due_date < ?");
        $stmt->execute([$userId, $today]);
        $overdueRecords = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($overdueRecords as $dueDate) {
            $daysDiff = floor((strtotime($today) - strtotime($dueDate)) / (60 * 60 * 24));
            if ($daysDiff > 0) {
                // LITERAL: (Current Date - Due Date) * 10
                $accruedFines += ($daysDiff * 10); 
            }
        }

        $stats['total_fines'] = $settledFines + $accruedFines;

        return $stats;
    }

    // --- Borrowing Logic ---

    public function getStudentBorrowings($userId) {
        $stmt = $this->pdo->prepare("
            SELECT br.*, b.title, b.cover_image, a.author_name
            FROM lib_borrowings br
            JOIN lib_books b ON br.book_id = b.id
            LEFT JOIN lib_authors a ON b.author_id = a.id
            WHERE br.user_id = ? 
            ORDER BY br.borrow_date DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // --- Reservations ---

    public function reserveBook($userId, $bookId) {
        // Check if already reserved by this user (pending)
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM lib_reservations WHERE user_id = ? AND book_id = ? AND status = 'pending'");
        $stmt->execute([$userId, $bookId]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'You already have a pending reservation for this book.'];
        }

        // Check if book is actually out of stock
        $stmt = $this->pdo->prepare("SELECT available_copies FROM lib_books WHERE id = ?");
        $stmt->execute([$bookId]);
        $available = $stmt->fetchColumn();
        if ($available > 0) {
            return ['success' => false, 'message' => 'This book is currently available. You can borrow it directly.'];
        }

        $stmt = $this->pdo->prepare("INSERT INTO lib_reservations (user_id, book_id, status) VALUES (?, ?, 'pending')");
        if ($stmt->execute([$userId, $bookId])) {
            require_once __DIR__ . '/audit_helper.php';
            logAction('RESERVE', "Book ID $bookId reserved by User ID $userId");
            return ['success' => true, 'message' => 'Book reserved successfully! You will be notified when it is available.'];
        }
        return ['success' => false, 'message' => 'Reservation failed.'];
    }

    public function getStudentReservations($userId) {
        $stmt = $this->pdo->prepare("
            SELECT r.*, b.title, a.author_name, c.category_name
            FROM lib_reservations r
            JOIN lib_books b ON r.book_id = b.id
            LEFT JOIN lib_authors a ON b.author_id = a.id
            LEFT JOIN lib_categories c ON b.category_id = c.id
            WHERE r.user_id = ? AND r.status = 'pending'
            ORDER BY r.reserved_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function cancelReservation($resId, $userId) {
        $stmt = $this->pdo->prepare("UPDATE lib_reservations SET status = 'cancelled' WHERE id = ? AND user_id = ?");
        return $stmt->execute([$resId, $userId]);
    }

    // --- Fines & Exports ---

    public function settleStudentFines($userId) {
        try {
            $this->pdo->beginTransaction();
            // 1. Reset user fines balance
            $stmt = $this->pdo->prepare("UPDATE users SET fines = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            
            // 2. Mark amounts in borrowings as settled
            $stmt = $this->pdo->prepare("UPDATE lib_borrowings SET fine_amount = 0 WHERE user_id = ? AND status = 'returned'");
            $stmt->execute([$userId]);

            require_once __DIR__ . '/audit_helper.php';
            logAction('FINE_SETTLED', "All fines settled for User ID $userId (Settled via Command Center)");
            
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Fines settled successfully!'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to settle fines: ' . $e->getMessage()];
        }
    }

    public function manualImposeFine($userId, $bookId, $amount, $reason) {
        try {
            $this->pdo->beginTransaction();
            
            // 1. Update user total fines
            $stmt = $this->pdo->prepare("UPDATE users SET fines = fines + ? WHERE id = ?");
            $stmt->execute([$amount, $userId]);
            
            // 2. Log Action
            require_once __DIR__ . '/audit_helper.php';
            logAction('FINE_IMPOSED', "Manual fine of Rs. $amount imposed on User $userId. Reason: $reason (Linked to Book ID $bookId)");
            
            $this->pdo->commit();
            return ['success' => true, 'message' => "Fine of Rs. $amount imposed successfully."];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Error imposing fine: ' . $e->getMessage()];
        }
    }

    public function getActiveBorrowingId($userId, $bookId) {
        $stmt = $this->pdo->prepare("SELECT id FROM lib_borrowings WHERE user_id = ? AND book_id = ? AND status != 'returned' ORDER BY borrow_date DESC LIMIT 1");
        $stmt->execute([$userId, $bookId]);
        return $stmt->fetchColumn();
    }

    public function getExportData($userId) {
        $stmt = $this->pdo->prepare("
            SELECT b.title, a.author_name, br.borrow_date, br.due_date, br.return_date, br.fine_amount, br.status
            FROM lib_borrowings br
            JOIN lib_books b ON br.book_id = b.id
            LEFT JOIN lib_authors a ON b.author_id = a.id
            WHERE br.user_id = ?
            ORDER BY br.borrow_date DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Librarian Management ---

    private function getOrCreateAuthor($authorName) {
        $stmt = $this->pdo->prepare("SELECT id FROM lib_authors WHERE author_name = ?");
        $stmt->execute([$authorName]);
        $author = $stmt->fetch();
        if ($author) {
            return $author['id'];
        }
        $stmt = $this->pdo->prepare("INSERT INTO lib_authors (author_name) VALUES (?)");
        $stmt->execute([$authorName]);
        return $this->pdo->lastInsertId();
    }

    private function getOrCreateCategory($categoryName) {
        if (empty($categoryName)) return null;
        $stmt = $this->pdo->prepare("SELECT id FROM lib_categories WHERE category_name = ?");
        $stmt->execute([$categoryName]);
        $category = $stmt->fetch();
        if ($category) {
            return $category['id'];
        }
        $stmt = $this->pdo->prepare("INSERT INTO lib_categories (category_name) VALUES (?)");
        $stmt->execute([$categoryName]);
        return $this->pdo->lastInsertId();
    }

    public function getCategories() {
        return $this->pdo->query("SELECT * FROM lib_categories ORDER BY category_name")->fetchAll();
    }

    public function getBooksByCategory($catId) {
        $stmt = $this->pdo->prepare("
            SELECT b.*, a.author_name, c.category_name 
            FROM lib_books b
            LEFT JOIN lib_authors a ON b.author_id = a.id
            LEFT JOIN lib_categories c ON b.category_id = c.id
            WHERE b.category_id = ?
            ORDER BY b.title ASC
        ");
        $stmt->execute([$catId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addBook($title, $authorName, $isbn, $totalCopies, $categoryName = '') {
        $authorId = $this->getOrCreateAuthor($authorName);
        $categoryId = $this->getOrCreateCategory($categoryName);
        $stmt = $this->pdo->prepare("INSERT INTO lib_books (title, author_id, category_id, isbn, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$title, $authorId, $categoryId, $isbn, $totalCopies, $totalCopies]);
    }

    public function updateBook($id, $title, $authorName, $isbn, $totalCopies, $categoryName = '') {
        $authorId = $this->getOrCreateAuthor($authorName);
        $categoryId = $this->getOrCreateCategory($categoryName);
        
        // Calculate new available copies based on current loans
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM lib_borrowings WHERE book_id = ? AND status != 'returned'");
        $stmt->execute([$id]);
        $onLoan = $stmt->fetchColumn();
        $availableCopies = max(0, $totalCopies - $onLoan);

        $stmt = $this->pdo->prepare("UPDATE lib_books SET title = ?, author_id = ?, category_id = ?, isbn = ?, total_copies = ?, available_copies = ? WHERE id = ?");
        return $stmt->execute([$title, $authorId, $categoryId, $isbn, $totalCopies, $availableCopies, $id]);
    }

    public function deleteBook($id) {
        // SQL CASCADE should handle borrowings if implemented, but safety first:
        $this->pdo->prepare("DELETE FROM lib_borrowings WHERE book_id = ?")->execute([$id]);
        $stmt = $this->pdo->prepare("DELETE FROM lib_books WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getBookById($id) {
        $stmt = $this->pdo->prepare("SELECT b.*, a.author_name, c.category_name 
                                    FROM lib_books b 
                                    LEFT JOIN lib_authors a ON b.author_id = a.id 
                                    LEFT JOIN lib_categories c ON b.category_id = c.id
                                    WHERE b.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // --- Enhanced Librarian Features ---

    public function getLibrarianStats() {
        return [
            'total_books' => $this->pdo->query("SELECT SUM(total_copies) FROM lib_books")->fetchColumn() ?: 0,
            'active_loans' => $this->pdo->query("SELECT COUNT(*) FROM lib_borrowings WHERE status != 'returned'")->fetchColumn() ?: 0,
            'total_students' => $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn() ?: 0,
            'overdue_books' => $this->pdo->query("SELECT COUNT(*) FROM lib_borrowings WHERE status = 'overdue'")->fetchColumn() ?: 0,
            'pending_reservations' => $this->pdo->query("SELECT COUNT(*) FROM lib_reservations WHERE status = 'pending'")->fetchColumn() ?: 0
        ];
    }

    public function getStudents() {
        return $this->pdo->query("SELECT id, name, email FROM users WHERE role = 'student' ORDER BY name ASC")->fetchAll();
    }

    public function getStudentsWithStats($search = '') {
        $sql = "SELECT u.id, u.name, u.email, u.identity_no,
                (SELECT COUNT(*) FROM lib_borrowings WHERE user_id = u.id) as total_borrowed,
                (SELECT COUNT(*) FROM lib_borrowings WHERE user_id = u.id AND status = 'borrowed') as currently_held,
                (SELECT COUNT(*) FROM lib_borrowings WHERE user_id = u.id AND status = 'overdue') as overdue_count,
                (SELECT IFNULL(SUM(fine_amount), 0) FROM lib_borrowings WHERE user_id = u.id) as total_fines
                FROM users u 
                WHERE u.role = 'student'";
        if (!empty($search)) {
            $sql .= " AND (u.name LIKE :s OR u.email LIKE :s OR u.identity_no LIKE :s)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':s' => "%$search%"]);
        } else {
            $sql .= " ORDER BY u.name ASC";
            $stmt = $this->pdo->query($sql);
        }
        return $stmt->fetchAll();
    }

    // --- ERP Grade Features ---

    private function logTransaction($userId, $bookId, $action, $notes = '') {
        $stmt = $this->pdo->prepare("INSERT INTO lib_transactions (user_id, book_id, action, notes) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $bookId, $action, $notes]);
    }

    public function refreshOverdueStates() {
        // 1. Mark as overdue if due date passed
        $this->pdo->query("UPDATE lib_borrowings SET status = 'overdue' WHERE status = 'borrowed' AND due_date < CURDATE()");

        // 2. Refresh Fine Amounts (e.g., Rs. 10 per day after due date)
        // Only for books not yet returned
        $stmt = $this->pdo->query("SELECT id, due_date FROM lib_borrowings WHERE status = 'overdue' AND return_date IS NULL");
        $overdueRecords = $stmt->fetchAll();

        foreach ($overdueRecords as $record) {
            $daysOverdue = ceil((time() - strtotime($record['due_date'])) / 86400);
            if ($daysOverdue > 0) {
                $fine = $daysOverdue * 10; // Rs. 10 per day
                $updateStmt = $this->pdo->prepare("UPDATE lib_borrowings SET fine_amount = ? WHERE id = ?");
                $updateStmt->execute([$fine, $record['id']]);
            }
        }
    }

    public function getAdvancedAnalytics() {
        $analytics = [];
        
        // Most Borrowed Book
        $analytics['most_borrowed'] = $this->pdo->query("
            SELECT b.id, b.title, COUNT(br.id) as borrow_count 
            FROM lib_borrowings br 
            JOIN lib_books b ON br.book_id = b.id 
            GROUP BY br.book_id 
            ORDER BY borrow_count DESC LIMIT 1
        ")->fetch();

        // Top Library Users
        $analytics['top_users'] = $this->pdo->query("
            SELECT u.name, COUNT(br.id) as borrow_count 
            FROM lib_borrowings br 
            JOIN users u ON br.user_id = u.id 
            GROUP BY br.user_id 
            ORDER BY borrow_count DESC LIMIT 5
        ")->fetchAll();

        // Monthly Circulation (Last 6 Months)
        $analytics['monthly_circulation'] = $this->pdo->query("
            SELECT DATE_FORMAT(borrow_date, '%b') as month, COUNT(*) as count 
            FROM lib_borrowings 
            WHERE borrow_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY MONTH(borrow_date) 
            ORDER BY borrow_date ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Category Distribution
        $analytics['category_distribution'] = $this->pdo->query("
            SELECT c.category_name, COUNT(b.id) as count 
            FROM lib_categories c 
            JOIN lib_books b ON c.id = b.category_id 
            GROUP BY c.id 
            LIMIT 6
        ")->fetchAll(PDO::FETCH_ASSOC);

        return $analytics;
    }

    public function checkOutBook($bookId, $studentId, $dueDate, $override = false) {
        $book = $this->getBookById($bookId);
        
        try {
            // 1. Check Issueability status (always enforced unless critical)
            if (!$book || $book['is_issueable'] != 1) {
                logAction('ISSUE_BLOCKED', "TRX_BLOCKED: Non-issueable book. Book: " . ($book['title'] ?? 'Unknown') . " (ID $bookId) to Student ID $studentId");
                return ['success' => false, 'message' => 'TRX_BLOCKED: This book is marked as non-issueable.'];
            }

            if ($book['available_copies'] <= 0) {
                logAction('ISSUE_BLOCKED', "TRX_BLOCKED: No copies available. Book ID $bookId to Student ID $studentId");
                return ['success' => false, 'message' => 'Book not available for checkout.'];
            }

            // Skip limit/fine checks if override is active
            if (!$override) {
                // 1b. Check Limit Enforcement: Dynamic limit from user profile
                $stmt = $this->pdo->prepare("SELECT borrow_limit FROM users WHERE id = ?");
                $stmt->execute([$studentId]);
                $userLimit = $stmt->fetchColumn() ?: 5;

                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM lib_borrowings WHERE user_id = ? AND status != 'returned'");
                $stmt->execute([$studentId]);
                $currentLoans = $stmt->fetchColumn(); 
                
                if ($currentLoans >= $userLimit) {
                    logAction('ISSUE_BLOCKED', "TRX_BLOCKED: Max book limit ($userLimit) reached for Student ID $studentId");
                    return ['success' => false, 'message' => "LIMIT_REACHED: Student limit of $userLimit reached."];
                }

                // 1c. Check Limit Enforcement: Unpaid Fines
                $stmt = $this->pdo->prepare("SELECT SUM(fine_amount) FROM lib_borrowings WHERE user_id = ? AND status != 'returned'");
                $stmt->execute([$studentId]);
                $unpaidFines = $stmt->fetchColumn() ?: 0;
                if ($unpaidFines > 0) {
                    logAction('ISSUE_BLOCKED', "TRX_BLOCKED: Unpaid fines (Rs. $unpaidFines) for Student ID $studentId");
                    return ['success' => false, 'message' => "FINES_DUE: Transaction blocked due to unpaid fines."];
                }
            }

            $this->pdo->beginTransaction();
            
            // 2. Automate Time Calculation: Exactly 14 days from today
            $dueDate = date('Y-m-d', strtotime('+14 days'));
            
            // 3. Create Borrowing Record
            $stmt = $this->pdo->prepare("INSERT INTO lib_borrowings (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, CURDATE(), ?, 'borrowed')");
            $stmt->execute([$studentId, $bookId, $dueDate]);

            // 2. Decrement Available Copies
            $stmt = $this->pdo->prepare("UPDATE lib_books SET available_copies = available_copies - 1 WHERE id = ?");
            $stmt->execute([$bookId]);

            // 5. Audit Trail
            require_once __DIR__ . '/audit_helper.php';
            logAction('ISSUE', "Book ID $bookId issued to Student ID $studentId. Due: $dueDate");

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Book checked out successfully!'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Checkout failed: ' . $e->getMessage()];
        }
    }

    public function checkInBook($borrowingId) {
        try {
            $this->pdo->beginTransaction();
            
            // 1. Get borrowing info
            $stmt = $this->pdo->prepare("SELECT book_id, user_id, fine_amount FROM lib_borrowings WHERE id = ? AND status != 'returned'");
            $stmt->execute([$borrowingId]);
            $loan = $stmt->fetch();
            
            if (!$loan) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Loan record not found or already returned.'];
            }

            // 2. Automated Fine Calculation using standard logic
            $returnDate = date('Y-m-d');
            $fineAmount = $this->calculateFine($borrowingId, $returnDate);

            // 3. Update Borrowing Record
            $stmt = $this->pdo->prepare("UPDATE lib_borrowings SET return_date = ?, fine_amount = ?, status = 'returned' WHERE id = ?");
            $stmt->execute([$returnDate, $fineAmount, $borrowingId]);

            // 4. Update Fines in Users Table (Synchronize immediately)
            $stmt = $this->pdo->prepare("UPDATE users SET fines = fines + ? WHERE id = ?");
            $stmt->execute([$fineAmount, $loan['user_id']]);

            // 5. Increment Available Copies
            $stmt = $this->pdo->prepare("UPDATE lib_books SET available_copies = available_copies + 1 WHERE id = ?");
            $stmt->execute([$loan['book_id']]);

            // 6. Audit Trail
            require_once __DIR__ . '/audit_helper.php';
            logAction('RETURN', "Returned with fine settled: $fineAmount (Student ID: {$loan['user_id']})");

            // 7. Check for Pending Reservations for this book
            $resStmt = $this->pdo->prepare("
                SELECT r.id, r.user_id, u.name as student_name 
                FROM lib_reservations r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.book_id = ? AND r.status = 'pending' 
                ORDER BY r.reserved_at ASC LIMIT 1
            ");
            $resStmt->execute([$loan['book_id']]);
            $reservation = $resStmt->fetch();

            $this->pdo->commit();
            
            // 8. Notify User of Successful Return
            $this->addNotification($loan['user_id'], 'success', 'Book Returned', "Your book (ID: {$loan['book_id']}) has been successfully returned.");

            $res = ['success' => true, 'message' => 'Book returned successfully!'];
            if ($reservation) {
                $res['has_reservation'] = true;
                $res['reservation_user'] = $reservation['student_name'];
                $res['message'] .= " NOTE: This book is reserved by " . $reservation['student_name'] . ". Please set it aside.";
            }

            return $res;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Return failed: ' . $e->getMessage()];
        }
    }

    public function getActiveBorrowings() {
        return $this->pdo->query("
            SELECT br.*, b.title, u.name as student_name 
            FROM lib_borrowings br
            JOIN lib_books b ON br.book_id = b.id
            JOIN users u ON br.user_id = u.id
            WHERE br.status != 'returned'
            ORDER BY br.borrow_date DESC
        ")->fetchAll();
    }

    public function settleUserFines($userId) {
        $stmt = $this->pdo->prepare("UPDATE lib_borrowings SET fine_amount = 0 WHERE user_id = ? AND fine_amount > 0");
        return $stmt->execute([$userId]);
    }

    public function getTransactions() {
        return $this->pdo->query("
            SELECT t.*, u.name as user_name, b.title as book_title 
            FROM lib_transactions t
            JOIN users u ON t.user_id = u.id
            JOIN lib_books b ON t.book_id = b.id
            ORDER BY t.transaction_date DESC
        ")->fetchAll();
    }

    public function checkIsbnExists($isbn) {
        $stmt = $this->pdo->prepare("SELECT id FROM lib_books WHERE isbn = ?");
        $stmt->execute([$isbn]);
        return $stmt->rowCount() > 0;
    }

    public function getRegistrationStats() {
        return [
            'added_today' => $this->pdo->query("SELECT COUNT(*) FROM lib_books WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
            'last_book' => $this->pdo->query("SELECT title FROM lib_books ORDER BY created_at DESC LIMIT 1")->fetchColumn() ?: 'None'
        ];
    }

    /**
     * Hidden audit log trigger for successful actions.
     */
    public function logAction($bookId, $action, $notes = '') {
        if (!isset($_SESSION['user_id'])) return false;
        
        $userId = $_SESSION['user_id'];
        $sql = "INSERT INTO lib_transactions (user_id, book_id, action, notes) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$userId, $bookId, $action, $notes]);
    }

    public function getBorrowingVitals($userId) {
        // Fetch borrow limit from users table
        $stmt = $this->pdo->prepare("SELECT borrow_limit, fines FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $limit = $user['borrow_limit'] ?? 5;
        $fines = (float)($user['fines'] ?? 0.00);

        // Fetch current active loans
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM lib_borrowings WHERE user_id = ? AND status != 'returned'");
        $stmt->execute([$userId]);
        $activeLoans = (int)$stmt->fetchColumn();

        return [
            'borrow_limit' => $limit,
            'active_loans' => $activeLoans,
            'fines'        => $fines,
            'has_exceeded' => ($activeLoans >= $limit),
            'has_fines'    => ($fines > 0)
        ];
    }
    public function getAllPendingReservations() {
        $stmt = $this->pdo->query("
            SELECT r.*, b.title, u.name as student_name, u.email as student_email, u.phone as student_phone
            FROM lib_reservations r
            JOIN lib_books b ON r.book_id = b.id
            JOIN users u ON r.user_id = u.id
            WHERE r.status = 'pending'
            ORDER BY r.reserved_at ASC
        ");
        return $stmt->fetchAll();
    }

    public function fulfillReservation($resId) {
        try {
            $this->pdo->beginTransaction();
            
            // 1. Get reservation info
            $stmt = $this->pdo->prepare("SELECT user_id, book_id FROM lib_reservations WHERE id = ? AND status = 'pending'");
            $stmt->execute([$resId]);
            $res = $stmt->fetch();
            
            if (!$res) throw new Exception("Reservation not found or already fulfilled.");

            // 2. Mark reservation as fulfilled
            $stmt = $this->pdo->prepare("UPDATE lib_reservations SET status = 'fulfilled' WHERE id = ?");
            $stmt->execute([$resId]);

            // 3. Issue the book directly (14 days)
            $this->checkOutBook($res['book_id'], $res['user_id'], date('Y-m-d', strtotime('+14 days')));

            require_once __DIR__ . '/audit_helper.php';
            logAction('RESERVATION_FULFILLED', "Reservation ID $resId fulfilled for User ID {$res['user_id']}");

            $this->pdo->commit();

            // 4. Notify Student
            $this->addNotification($res['user_id'], 'success', 'Hold Ready!', "Your reserved book (ID: {$res['book_id']}) is now ready and has been issued to your account.");

            return ['success' => true, 'message' => 'Reservation fulfilled and book issued!'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    public function addNotification($userId, $type, $title, $message, $uniqueKey = null) {
        $sql = "INSERT IGNORE INTO lib_notifications (user_id, type, title, message, unique_key) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$userId, $type, $title, $message, $uniqueKey]);
    }

    public function getUnreadNotifications($userId, $limit = 10) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM lib_notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?");
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // If table doesn't exist yet, return empty array instead of crashing
            return [];
        }
    }

    public function markAllAsRead($userId) {
        $stmt = $this->pdo->prepare("UPDATE lib_notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Automated Due Date Alert System
     * Scans for upcoming and overdue books for a specific student.
     */
    public function checkAndGenerateDueAlerts($userId) {
        // Fetch active borrowings
        $stmt = $this->pdo->prepare("
            SELECT br.*, b.title 
            FROM lib_borrowings br 
            JOIN lib_books b ON br.book_id = b.id 
            WHERE br.user_id = ? AND br.status != 'returned'
        ");
        $stmt->execute([$userId]);
        $borrowings = $stmt->fetchAll();

        foreach ($borrowings as $b) {
            $dueDate = $b['due_date'];
            $today = date('Y-m-d');
            $diff = (strtotime($dueDate) - strtotime($today)) / (60 * 60 * 24);

            $borrowId = $b['id'];
            $title = $b['title'];

            if ($diff == 3) {
                // 3rd day reminder
                $this->addNotification(
                    $userId, 'warning', 'Due Soon', 
                    "Your book '$title' is due in 3 days. Please return or renew it soon.",
                    "DUE_REMIND_{$borrowId}_3D"
                );
            } elseif ($diff == 1) {
                // 1st day reminder
                $this->addNotification(
                    $userId, 'danger', 'Due Tomorrow', 
                    "Urgent: '$title' is due tomorrow. Return it to avoid fines.",
                    "DUE_REMIND_{$borrowId}_1D"
                );
            } elseif ($diff < 0) {
                // Overdue alert
                $this->addNotification(
                    $userId, 'danger', 'OVERDUE ALERT', 
                    "Warning: '$title' is past its due date. Fines are accumulating.",
                    "OVERDUE_ALERT_{$borrowId}"
                );
            }
        }
    }
    /**
     * Book Review & Rating System
     */
    public function canUserReviewBook($userId, $bookId) {
        $stmt = $this->pdo->prepare("SELECT id FROM lib_reviews WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$userId, $bookId]);
        if ($stmt->fetch()) return false;
        $stmt = $this->pdo->prepare("SELECT id FROM lib_borrowings WHERE user_id = ? AND book_id = ? AND status = 'returned'");
        $stmt->execute([$userId, $bookId]);
        return $stmt->rowCount() > 0;
    }

    public function addReview($userId, $bookId, $rating, $comment) {
        if (!$this->canUserReviewBook($userId, $bookId)) {
            return ['success' => false, 'message' => 'You are not eligible to review this book yet.'];
        }
        $stmt = $this->pdo->prepare("INSERT INTO lib_reviews (user_id, book_id, rating, comment) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $bookId, $rating, $comment]) ? ['success' => true, 'message' => 'Review submitted!'] : ['success' => false, 'message' => 'Error saving review.'];
    }

    public function getBookReviews($bookId) {
        $stmt = $this->pdo->prepare("SELECT r.*, u.name as student_name FROM lib_reviews r JOIN users u ON r.user_id = u.id WHERE r.book_id = ? ORDER BY r.created_at DESC");
        $stmt->execute([$bookId]);
        return $stmt->fetchAll();
    }

    public function getBookRatingStats($bookId) {
        $stmt = $this->pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM lib_reviews WHERE book_id = ?");
        $stmt->execute([$bookId]);
        $stats = $stmt->fetch();
        return ['average' => round((float)$stats['avg_rating'], 1), 'count' => (int)$stats['count']];
    }
    public function getStudentTransactions($userId) {
        $stmt = $this->pdo->prepare("
            SELECT t.*, b.title as book_title
            FROM lib_transactions t
            LEFT JOIN lib_books b ON t.book_id = b.id
            WHERE t.user_id = ?
            ORDER BY t.transaction_date DESC
            LIMIT 15
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
