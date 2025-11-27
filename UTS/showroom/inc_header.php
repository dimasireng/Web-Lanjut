<?php

if (session_status() === PHP_SESSION_NONE)
    session_start();


require_once __DIR__ . '/inc_db.php';
require_once __DIR__ . '/inc_auth.php';

$logged_in = is_logged_in();
$requests = [];
$unreadCount = 0;

if ($logged_in) {
    // ambil 5 pengajuan terbaru milik user
    $stmt = $pdo->prepare("
        SELECT t.id, t.preferred_date, t.preferred_time, t.status, m.name AS model_name, mf.name AS manufacturer
        FROM test_drive_requests t
        LEFT JOIN models m ON m.id = t.model_id
        LEFT JOIN manufacturers mf ON mf.id = m.manufacturer_id
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // hitung berapa yang finished & belum feedback (untuk badge)
    $cnt = 0;
    foreach ($requests as $r) {
        if (in_array($r['status'], ['finished', 'completed'])) {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM test_drive_feedback WHERE test_drive_id = ?');
            $chk->execute([$r['id']]);
            if (intval($chk->fetchColumn() ?? 0) === 0)
                $cnt++;
        }
    }
    $unreadCount = $cnt;
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Showroom SuperCar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/showroom/assets/admin.css">
    <link rel="stylesheet" href="/showroom/assets/css/cust.css">
    <link rel="stylesheet" href="/showroom/assets/css/style.css">
    <link rel="stylesheet" href="/showroom/assets/css/michelin-cust.css?v=1">


    <link rel="stylesheet" href="/showroom/assets/css/models.css?v=5">
    <link rel="stylesheet" href="/showroom/assets/css/my-requests.css?v=10">


</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/showroom/cust/index.php">Showroom SuperCar</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="/showroom/cust/index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="/showroom/cust/models.php">Models</a></li>
                    <li class="nav-item"><a class="nav-link" href="/showroom/cust/test_drive.php">Ajukan Test Drive</a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto align-items-center">

                    <?php if (!$logged_in): ?>
                        <li class="nav-item"><a class="nav-link" href="/showroom/login.php">Login</a></li>

                    <?php else: ?>

                        <!-- Requests dropdown -->
                        <li class="nav-item dropdown me-2">
                            <a class="nav-link dropdown-toggle position-relative" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                    class="bi bi-list" viewBox="0 0 16 16">
                                    <path
                                        d="M2 12.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zm0-4A.5.5 0 0 1 2.5 4h11a.5.5 0 0 1 0 1h-11A.5.5 0 0 1 2 4.5z" />
                                </svg>
                                <?php if ($unreadCount > 0): ?>
                                    <span
                                        class="badge bg-danger position-absolute top-0 start-100 translate-middle p-1 rounded-circle"><?= intval($unreadCount) ?></span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width:320px;">
                                <li class="dropdown-header">Pengajuan Terbaru</li>
                                <?php if (empty($requests)): ?>
                                    <li class="px-3 py-2 text-muted small">Belum ada pengajuan.</li>
                                <?php else: ?>
                                    <?php foreach ($requests as $r):
                                        $status = $r['status'];
                                        $label = $status;
                                        // map status ke badge class
                                        $badgeClass = 'secondary';
                                        if ($status === 'pending')
                                            $badgeClass = 'warning text-dark';
                                        elseif ($status === 'approved')
                                            $badgeClass = 'primary';
                                        elseif ($status === 'finished' || $status === 'completed')
                                            $badgeClass = 'success';
                                        elseif ($status === 'rejected')
                                            $badgeClass = 'danger';
                                        ?>
                                        <li class="d-flex align-items-start gap-2 px-2 py-2 border-bottom">
                                            <div class="flex-grow-1">
                                                <div class="small fw-bold">
                                                    <?= htmlspecialchars(($r['manufacturer'] ? $r['manufacturer'] . ' ' : '') . ($r['model_name'] ?? '-')) ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <?= htmlspecialchars(($r['preferred_date'] ?? '-') . ' ' . ($r['preferred_time'] ?? '-')) ?>
                                                </div>
                                                <div class="mt-1"><span
                                                        class="badge bg-<?= htmlspecialchars($badgeClass) ?> small"><?= htmlspecialchars($label) ?></span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <?php
                                                $showFeedback = in_array($r['status'], ['finished', 'completed']);
                                                $hasFeedback = false;
                                                if ($showFeedback) {
                                                    $chk = $pdo->prepare('SELECT COUNT(*) FROM test_drive_feedback WHERE test_drive_id = ?');
                                                    $chk->execute([$r['id']]);
                                                    $hasFeedback = intval($chk->fetchColumn() ?? 0) > 0;
                                                }
                                                ?>
                                                <?php if ($showFeedback && !$hasFeedback): ?>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                        data-bs-target="#feedbackModal"
                                                        data-request-id="<?= htmlspecialchars($r['id']) ?>"
                                                        data-model="<?= htmlspecialchars($r['model_name'] ?? '-') ?>">Feedback</button>
                                                <?php else: ?>
                                                    <a class="btn btn-sm btn-outline-secondary"
                                                        href="/showroom/cust/my_requests.php">Lihat</a>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                    <li class="text-center mt-2"><a href="/showroom/cust/my_requests.php" class="small">Lihat
                                            semua pengajuan</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>

                        <li class="nav-item"><span class="nav-link">Selamat Datang</span></li>
                        <?php if (intval($_SESSION['role_id'] ?? 0) === 1): ?>
                            <li class="nav-item"><a class="nav-link" href="/admin/index.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="btn btn-sm btn-outline-light ms-2"
                                href="/showroom/logout.php">Logout</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- FEEDBACK MODAL -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form id="feedbackForm" method="post" action="/showroom/cust/submit_feedback.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Beri Feedback</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="test_drive_id" id="fd_test_drive_id" value="">
                        <div class="mb-2">
                            <label class="form-label small">Model</label>
                            <div id="fd_model" class="small text-muted"></div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Rating</label>
                            <select name="rating" id="fd_rating" class="form-select" required>
                                <option value="">Pilih...</option>
                                <option value="5">5 — Sangat memuaskan</option>
                                <option value="4">4 — Puas</option>
                                <option value="3">3 — Cukup</option>
                                <option value="2">2 — Kurang</option>
                                <option value="1">1 — Tidak puas</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Komentar (opsional)</label>
                            <textarea name="comments" id="fd_comments" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Kirim Feedback</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- script to populate modal -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var feedbackModal = document.getElementById('feedbackModal');
            if (!feedbackModal) return;

            feedbackModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var requestId = button.getAttribute('data-request-id');
                var model = button.getAttribute('data-model') || '';
                document.getElementById('fd_test_drive_id').value = requestId;
                document.getElementById('fd_model').textContent = model;
                // reset fields
                document.getElementById('fd_rating').value = '';
                document.getElementById('fd_comments').value = '';
            });
        });
    </script>
</body>

</html>