<?php
// admin/request/list.php
require_once __DIR__ . '/../../inc_db.php';
require_once __DIR__ . '/../../inc_auth.php';

require_login();
if (intval($_SESSION['role_id'] ?? 0) !== 1) {
  header('HTTP/1.1 403 Forbidden');
  echo 'Forbidden';
  exit;
}

// Handle POST actions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $reqId = intval($_POST['request_id'] ?? 0);
  $action = $_POST['action'] ?? '';

  if ($reqId > 0 && $pdo) {
    try {
      if ($action === 'approve') {
        $pdo->prepare("UPDATE test_drive_requests SET status = 'approved' WHERE id = ?")->execute([$reqId]);
      } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE test_drive_requests SET status = 'rejected' WHERE id = ?")->execute([$reqId]);
      } elseif ($action === 'finish' || $action === 'complete') {
        $pdo->prepare("UPDATE test_drive_requests SET status = 'completed' WHERE id = ?")->execute([$reqId]);
      } elseif ($action === 'cancel') {
        $pdo->prepare("UPDATE test_drive_requests SET status = 'cancelled' WHERE id = ?")->execute([$reqId]);
      }
    } catch (Throwable $e) {
      error_log('admin/request/list action error: ' . $e->getMessage());
      $_SESSION['_flash_admin'] = ['type' => 'danger', 'text' => 'Gagal melakukan aksi.'];
    }
  }

  // redirect back to same page
  $back = $_SERVER['REQUEST_URI'] ?? ($_SERVER['PHP_SELF'] ?? '/');
  header('Location: ' . $back);
  exit;
}

// optional flash
$adminFlash = $_SESSION['_flash_admin'] ?? null;
if ($adminFlash) unset($_SESSION['_flash_admin']);

// fetch rows
$rows = $pdo->query("
  SELECT t.id, t.preferred_date, t.preferred_time, t.status, t.notes,
         u.full_name, u.email, m.name AS model_name
  FROM test_drive_requests t
  JOIN users u ON u.id = t.user_id
  LEFT JOIN models m ON m.id = t.model_id
  ORDER BY t.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if ($adminFlash): ?>
  <div class="alert alert-<?= htmlspecialchars($adminFlash['type']) ?>">
    <?= htmlspecialchars($adminFlash['text']) ?>
  </div>
<?php endif; ?>

<h1 class="h4 mb-3">Test Drive Requests</h1>

<?php if (empty($rows)): ?>
  <div class="alert alert-secondary">Belum ada request.</div>
<?php else: ?>

<!-- WRAPPER BARU: radius + responsive -->
<div class="table-rounded">
    <div class="table-responsive">
        <table class="table table-sm data-table">
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
            <?php foreach ($rows as $r):
              $status = strtolower(trim($r['status'] ?? ''));
              if ($status === '') $status = 'pending';

              // badge class
              if ($status === 'pending') $badge = 'bg-warning text-dark';
              elseif ($status === 'approved') $badge = 'bg-primary';
              elseif (in_array($status, ['completed', 'finished', 'done'])) $badge = 'bg-success';
              elseif ($status === 'rejected') $badge = 'bg-danger';
              elseif ($status === 'cancelled') $badge = 'bg-secondary';
              else $badge = 'bg-secondary';

              $current = htmlspecialchars($_SERVER['REQUEST_URI']);
            ?>
                <tr>
                    <td><?= htmlspecialchars($r['id']) ?></td>

                    <td>
                        <?= htmlspecialchars($r['full_name']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($r['email']) ?></small>
                    </td>

                    <td><?= htmlspecialchars($r['model_name'] ?? '-') ?></td>

                    <td><?= htmlspecialchars(($r['preferred_date'] ?? '-') . ' ' . ($r['preferred_time'] ?? '')) ?></td>

                    <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($status) ?></span></td>

                    <td><?= nl2br(htmlspecialchars($r['notes'] ?? '')) ?></td>

                    <td>
                        <!-- Approve -->
                        <form action="<?= $current ?>" method="post" style="display:inline">
                            <input type="hidden" name="request_id" value="<?= htmlspecialchars($r['id']) ?>">
                            <button class="btn btn-sm btn-success" name="action" value="approve"
                                <?= $status !== 'pending' ? 'disabled' : '' ?>>
                                Approve
                            </button>
                        </form>

                        <!-- Reject -->
                        <form action="<?= $current ?>" method="post" style="display:inline">
                            <input type="hidden" name="request_id" value="<?= htmlspecialchars($r['id']) ?>">
                            <button class="btn btn-sm btn-danger" name="action" value="reject"
                                <?= $status !== 'pending' ? 'disabled' : '' ?>>
                                Reject
                            </button>
                        </form>

                        <!-- Complete -->
                        <form action="<?= $current ?>" method="post" style="display:inline">
                            <input type="hidden" name="request_id" value="<?= htmlspecialchars($r['id']) ?>">
                            <button class="btn btn-sm btn-primary" name="action" value="finish"
                                <?= $status !== 'approved' ? 'disabled' : '' ?>>
                                Complete
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>

        </table>
    </div>
</div>

<?php endif; ?>
