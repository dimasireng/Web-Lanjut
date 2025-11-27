<?php
// admin/update_status.php
require_once __DIR__ . '/../inc_db.php';
require_once __DIR__ . '/../inc_auth.php';

require_login();
if (intval($_SESSION['role_id'] ?? 0) !== 1) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id > 0 && $pdo) {
        try {
            if ($action === 'approve') {
                $pdo->prepare("UPDATE test_drive_requests SET status='approved' WHERE id = ?")->execute([$id]);
            } elseif ($action === 'reject') {
                $pdo->prepare("UPDATE test_drive_requests SET status='rejected' WHERE id = ?")->execute([$id]);
            } elseif ($action === 'finish' || $action === 'complete') {
                $pdo->prepare("UPDATE test_drive_requests SET status='completed' WHERE id = ?")->execute([$id]);
            } elseif ($action === 'cancel') {
                $pdo->prepare("UPDATE test_drive_requests SET status='cancelled' WHERE id = ?")->execute([$id]);
            }
        } catch (Throwable $e) {
            error_log('admin/update_status error: ' . $e->getMessage());
        }
    }
}

// redirect back to referer or admin list
$back = $_SERVER['HTTP_REFERER'] ?? '/showroom/admin/test_drive/index.php';
header('Location: ' . $back);
exit;
