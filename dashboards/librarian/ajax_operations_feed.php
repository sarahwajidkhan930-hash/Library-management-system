<?php
require_once '../../core/db.php';

function timeAgo($timestamp) {
    if (empty($timestamp)) return "N/A";
    $time = strtotime($timestamp);
    $diff = time() - $time;
    if ($diff < 1) return 'Just now';
    $intervals = [
        31536000 => 'year', 2592000 => 'month', 604800 => 'week',
        86400    => 'day',  3600     => 'hour',  60     => 'minute', 1 => 'second'
    ];
    foreach ($intervals as $secs => $str) {
        $d = $diff / $secs;
        if ($d >= 1) {
            $r = round($d);
            return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
        }
    }
}

$startDate = $_GET['start'] ?? '';
$endDate = $_GET['end'] ?? '';

$sql = "SELECT al.activity, al.notes, al.created_at,
               u.name AS staff_name, u.role AS staff_role, u.avatar AS staff_avatar
        FROM audit_logs al
        JOIN users u ON al.user_id = u.id
        WHERE u.role IN ('assistant_manager', 'librarian')";

$params = [];
if (!empty($startDate)) {
    $sql .= " AND DATE(al.created_at) >= :start";
    $params[':start'] = $startDate;
}
if (!empty($endDate)) {
    $sql .= " AND DATE(al.created_at) <= :end";
    $params[':end'] = $endDate;
}

$sql .= " ORDER BY al.created_at DESC LIMIT 10";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$operationsFeed = $stmt->fetchAll();

if (empty($operationsFeed)) {
    echo '<tr><td colspan="4" class="text-center py-5 text-muted">
            <i class="bi bi-calendar-x fs-1 opacity-25 d-block mb-3"></i>
            No operations recorded for the selected period.
          </td></tr>';
} else {
    foreach ($operationsFeed as $op) {
        $badgeColor = match(true) {
            str_contains($op['activity'], 'RETURN')  => 'success',
            str_contains($op['activity'], 'ISSUE')   => 'primary',
            str_contains($op['activity'], 'BLOCKED') => 'danger',
            str_contains($op['activity'], 'FINE')    => 'warning',
            str_contains($op['activity'], 'CATEGORY')=> 'info',
            str_contains($op['activity'], 'REMINDER')=> 'indigo',
            default => 'secondary'
        };
        ?>
        <tr class="animate-fade-in">
            <td class="ps-4">
                <div class="d-flex align-items-center">
                    <img src="<?= $op['staff_avatar'] ?: '../../assets/img/avatar.png' ?>" class="staff-avatar-sm me-3 border shadow-sm" alt="AV" onerror="this.src='../../assets/img/avatar.png'">
                    <div>
                        <div class="fw-semibold"><?= htmlspecialchars($op['staff_name']) ?></div>
                        <span class="badge bg-<?= $op['staff_role'] === 'assistant_manager' ? 'warning text-dark' : 'primary' ?> rounded-pill" style="font-size:.7rem;">
                            <?= ucwords(str_replace('_', ' ', $op['staff_role'])) ?>
                        </span>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge bg-<?= $badgeColor ?> bg-opacity-10 text-<?= $badgeColor ?> border border-<?= $badgeColor ?> border-opacity-25 rounded-pill px-3">
                    <i class="bi bi-arrow-right me-1"></i><?= htmlspecialchars($op['activity']) ?>
                </span>
            </td>
            <td class="text-muted small" style="max-width:300px;">
                <?= htmlspecialchars(substr($op['notes'] ?? '', 0, 80)) ?>
                <?= strlen($op['notes'] ?? '') > 80 ? '...' : '' ?>
            </td>
            <td class="text-end pe-4 text-muted small fw-bold">
                <i class="bi bi-clock-history me-1"></i><?= timeAgo($op['created_at']) ?>
            </td>
        </tr>
        <?php
    }
}
?>
