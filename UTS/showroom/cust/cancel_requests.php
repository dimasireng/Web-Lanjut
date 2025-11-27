<?php
require_once __DIR__ . '/../inc_db.php';
require_once __DIR__ . '/../inc_auth.php';

if (!is_logged_in()) {
    header("Location: /showroom/login.php");
    exit;
}

$request_id = intval($_POST['request_id'] ?? 0);

if ($request_id <= 0) {
    header("Location: /showroom/cust/my_requests.php?err=invalid");
    exit;
}

// Ambil request
$stmt = $pdo->prepare("SELECT user_id, status FROM test_drive_requests WHERE id = ? LIMIT 1");
$stmt->execute([$request_id]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$req) {
    header("Location: /showroom/cust/my_requests.php?err=notfound");
    exit;
}

// Pastikan user pemiliknya
if ($req['user_id'] != $_SESSION['user_id']) {
    header("HTTP/1.1 403 Forbidden");
    echo "Forbidden";
    exit;
}

// Pastikan masih boleh dibatalkan
if (!in_array($req['status'], ['pending', 'approved'])) {
    header("Location: /showroom/cust/my_requests.php?err=cannot_cancel");
    exit;
}

// Update status menjadi cancelled
$upd = $pdo->prepare("UPDATE test_drive_requests SET status = 'cancelled' WHERE id = ?");
$upd->execute([$request_id]);

header("Location: /showroom/cust/my_requests.php?cancel=1");
exit;
