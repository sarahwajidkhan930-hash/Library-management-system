<?php
require_once 'config.php';
require_once 'db.php';
require_once 'library_functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$lib = new Library($pdo);
$userId = $_SESSION['user_id'];
$unreadNotifs = $lib->getUnreadNotifications($userId);
$unreadCount = count($unreadNotifs);

$html = '';
if ($unreadCount == 0) {
    $html = '<div class="dropdown-item text-center py-3 text-muted small">No new notifications</div>';
} else {
    foreach ($unreadNotifs as $n) {
        $html .= '<a href="#" class="dropdown-item p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-' . $n['type'] . ' bg-opacity-10 text-' . $n['type'] . ' rounded-circle p-2 me-3">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-0 fw-bold small text-dark">' . htmlspecialchars($n['title']) . '</p>
                            <p class="mb-0 text-muted extra-small">' . htmlspecialchars($n['message']) . '</p>
                            <p class="mb-0 extra-small text-secondary mt-1 opacity-75">' . date('h:i A', strtotime($n['created_at'])) . '</p>
                        </div>
                    </div>
                </a>
                <div class="dropdown-divider"></div>';
    }
}

echo json_encode([
    'success' => true,
    'count'   => $unreadCount,
    'html'    => $html
]);
?>
