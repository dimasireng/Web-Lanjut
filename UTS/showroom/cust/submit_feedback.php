<?php
// simpan file ini sebagai showroom/cust/submit_feedback.php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

require_once __DIR__ . '/../inc_db.php';
require_once __DIR__ . '/../inc_auth.php';

// pastikan user login
if (!is_logged_in()) {
    header('Location: /showroom/login.php');
    exit;
}

// hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /showroom/cust/index.php');
    exit;
}

// ambil input
$test_drive_id = intval($_POST['test_drive_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$comments = trim($_POST['comments'] ?? '');

// validasi dasar
if ($test_drive_id <= 0 || $rating < 1 || $rating > 5) {
    $_SESSION['flash_error'] = 'Data tidak valid.';
    header('Location: /showroom/cust/my_requests.php');
    exit;
}

// verifikasi pengajuan milik user / status finished
$stmt = $pdo->prepare('SELECT id, user_id, status FROM test_drive_requests WHERE id = ? LIMIT 1');
$stmt->execute([$test_drive_id]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$req) {
    $_SESSION['flash_error'] = 'Pengajuan tidak ditemukan.';
    header('Location: /showroom/cust/my_requests.php');
    exit;
}

// pastikan user berhak (admin atau pemilik)
$isAdmin = intval($_SESSION['role_id'] ?? 0) === 1;
if (!$isAdmin && intval($req['user_id']) !== intval($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = 'Anda tidak berwenang memberi feedback untuk pengajuan ini.';
    header('Location: /showroom/cust/my_requests.php');
    exit;
}

// hanya untuk status finished/completed
if (!in_array($req['status'], ['finished', 'completed'])) {
    $_SESSION['flash_error'] = 'Feedback hanya boleh untuk pengajuan yang sudah selesai.';
    header('Location: /showroom/cust/my_requests.php');
    exit;
}

// cek apakah sudah pernah feedback
$chk = $pdo->prepare('SELECT COUNT(*) FROM test_drive_feedback WHERE test_drive_id = ?');
$chk->execute([$test_drive_id]);
if (intval($chk->fetchColumn() ?? 0) > 0) {
    $_SESSION['flash_error'] = 'Feedback sudah pernah dikirim untuk pengajuan ini.';
    header('Location: /showroom/cust/my_requests.php');
    exit;
}

// insert feedback
$ins = $pdo->prepare('INSERT INTO test_drive_feedback (test_drive_id, rating, comments, created_at) VALUES (?, ?, ?, NOW())');
try {
    $ins->execute([$test_drive_id, $rating, $comments]);
    $_SESSION['flash_success'] = 'Terima kasih — feedback berhasil dikirim.';
} catch (Throwable $e) {
    error_log('submit_feedback error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Gagal menyimpan feedback.';
}

header('Location: /showroom/cust/my_requests.php');
exit;
