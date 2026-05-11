<?php
require_once '../../core/config.php';
require_once '../../core/db.php';

$isbn = $_GET['isbn'] ?? '';

if (empty($isbn)) {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT title FROM lib_books WHERE isbn = ? LIMIT 1");
$stmt->execute([$isbn]);
$book = $stmt->fetch();

if ($book) {
    echo json_encode(['exists' => true, 'title' => $book['title']]);
} else {
    echo json_encode(['exists' => false]);
}
?>
