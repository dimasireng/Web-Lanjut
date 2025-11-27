<?php
// admin/test_drive/index.php
// List test drive requests + handle approve/reject/finish/cancel

require_once __DIR__ . '/../../inc_db.php';
require_once __DIR__ . '/../../inc_auth.php';

// pastikan login & admin
require_login();
if (intval($_SESSION['role_id'] ?? 0) !== 1) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden';
    exit;
}

// Handle POST actions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);

    if ($id > 0 && $pdo) {
        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE test_drive_requests SET status = 'approved' WHERE id = ?");
                $stmt->execute([$id]);
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE test_drive_requests SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$id]);
            } elseif ($action === 'finish' || $action === 'complete') {
                // set to 'completed' to match customer's checks
                $stmt = $pdo->prepare("UPDATE test_drive_requests SET status = 'completed' WHERE id = ?");
                $stmt->execute([$id]);
            } elseif ($action === 'cancel') {
                $stmt = $pdo->prepare("UPDATE test_drive_requests SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$id]);
            }
        } catch (Throwable $e) {
            error_log('admin/test_drive action error: ' . $e->getMessage());
            // optionally set flash in session
            $_SESSION['_flash_admin'] = ['type' => 'danger', 'text' => 'Gagal melakukan aksi.'];
        }
    }

    // redirect to same page to avoid form resubmission
    $self = $_SERVER['PHP_SELF'] . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '');
    header('Location: ' . $self);
    exit;
}

// include header (navbar etc.)
require_once __DIR__ . '/../../inc_header.php';

// optional admin flash
$adminFlash = $_SESSION['_flash_admin'] ?? null;
if ($adminFlash)
    unset($_SESSION['_flash_admin']);

// fetch requests
$sql = "
    SELECT t.id, t.user_id, t.model_id, t.preferred_date, t.preferred_time,
           t.status, t.notes, t.created_at,
           u.full_name AS user_name, u.email AS user_email,
           m.name AS model_name, mf.name AS manufacturer
    FROM test_drive_requests t
    LEFT JOIN users u ON u.id = t.user_id
    LEFT JOIN models m ON m.id = t.model_id
    LEFT JOIN manufacturers mf ON mf.id = m.manufacturer_id
    ORDER BY t.id DESC
";
$stmt = $pdo->query($sql);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="row">
    <div class="col-2">
        <div class="list-group">
            <a class="list-group-item list-group-item-action"
                href="<?= htmlspecialchars(BASE_URL) ?>/admin/index.php">Dashboard</a>
            <a class="list-group-item list-group-item-action active" href="#">Test Drive Requests</a>
            <a class="list-group-item list-group-item-action"
                href="<?= htmlspecialchars(BASE_URL) ?>/admin/models.php">Models</a>
            <a class="list-group-item list-group-item-action"
                href="<?= htmlspecialchars(BASE_URL) ?>/admin/users.php">Users</a>
            <a class="list-group-item list-group-item-action text-danger"
                href="<?= htmlspecialchars(BASE_URL) ?>/logout.php">Sign out</a>
        </div>
    </div>

    <div class="col-10">
        <h3 class="mb-4">Test Drive Requests</h3>

        <?php if ($adminFlash): ?>
            <div class="alert alert-<?= htmlspecialchars($adminFlash['type']) ?>">
                <?= htmlspecialchars($adminFlash['text']) ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Model</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r):
                        $status = strtolower(trim($r['status'] ?? ''));
                        if ($status === '')
                            $status = 'pending';
                        $badgeClass = 'secondary';
                        if ($status === 'pending')
                            $badgeClass = 'warning text-dark';
                        elseif ($status === 'approved')
                            $badgeClass = 'primary';
                        elseif (in_array($status, ['completed', 'finished', 'done']))
                            $badgeClass = 'success';
                        elseif ($status === 'rejected')
                            $badgeClass = 'danger';
                        elseif ($status === 'cancelled')
                            $badgeClass = 'secondary';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($r['id']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($r['user_name'] ?? '-') ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($r['user_email'] ?? '-') ?></small>
                            </td>
                            <td><?= htmlspecialchars(($r['manufacturer'] ? $r['manufacturer'] . ' ' : '') . ($r['model_name'] ?? '-')) ?>
                            </td>
                            <td><?= htmlspecialchars(($r['preferred_date'] ?? '-') . ' ' . ($r['preferred_time'] ?? '-')) ?>
                            </td>
                            <td><span
                                    class="badge bg-<?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars($status) ?></span>
                            </td>
                            <td><?= nl2br(htmlspecialchars($r['notes'] ?? '-')) ?></td>
                            <td>
                                <!-- Approve -->
                                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
                                    style="display:inline">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button class="btn btn-sm btn-success">Approve</button>
                                </form>

                                <!-- Reject -->
                                <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
                                    style="display:inline; margin-left:6px;">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button class="btn btn-sm btn-danger">Reject</button>
                                </form>

                                <!-- Finish -->
                                <form method="post" action="/showroom/admin/test_drive/index.php" style="display:inline">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
                                    <input type="hidden" name="action" value="finish">
                                    <button class="btn btn-sm btn-primary">Finish</button>
                                </form>


                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../inc_footer.php'; ?>