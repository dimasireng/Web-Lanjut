<?php
// admin/feedback/list.php
require_once __DIR__ . '/../../inc_db.php';
require_once __DIR__ . '/../../inc_auth.php';
require_role($pdo, 'admin');

$errors = [];

// handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_feedback') {
    $fid = intval($_POST['feedback_id'] ?? 0);
    if ($fid > 0) {
        $del = $pdo->prepare('DELETE FROM test_drive_feedback WHERE id = ?');
        try {
            $del->execute([$fid]);
            header('Location: index.php?folder=feedback&page=list&deleted=1');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Gagal menghapus feedback.';
        }
    } else {
        $errors[] = 'ID feedback tidak valid.';
    }
}

// ambil data feedback
$sql = "
  SELECT f.id AS feedback_id, f.test_drive_id, f.rating, f.comments, f.created_at,
         t.model_id, t.user_id,
         u.full_name AS user_name, u.email AS user_email,
         m.name AS model_name,
         mf.name AS manufacturer
  FROM test_drive_feedback f
  LEFT JOIN test_drive_requests t ON t.id = f.test_drive_id
  LEFT JOIN users u ON u.id = t.user_id
  LEFT JOIN models m ON m.id = t.model_id
  LEFT JOIN manufacturers mf ON mf.id = m.manufacturer_id
  ORDER BY f.created_at DESC
  LIMIT 500
";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 class="h4 mb-3">Feedback Pelanggan</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">Feedback dihapus.</div>
<?php endif; ?>

<?php if (empty($rows)): ?>
    <div class="alert alert-secondary">Belum ada feedback.</div>
<?php else: ?>

    <!-- WRAPPER RADIUS + RESPONSIVE -->
    <div class="table-rounded">
        <div class="table-responsive">

            <table class="table data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Pelanggan</th>
                        <th>Model</th>
                        <th>Rating</th>
                        <th>Komentar</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>

                            <!-- ID -->
                            <td><?= htmlspecialchars($r['feedback_id']) ?></td>

                            <!-- Pelanggan + Email -->
                            <td>
                                <?= htmlspecialchars($r['user_name'] ?? '-') ?><br>
                                <small class="email-text"><?= htmlspecialchars($r['user_email'] ?? '-') ?></small>
                            </td>

                            <!-- Model -->
                            <td>
                                <?= htmlspecialchars(
                                    (($r['manufacturer'] ?? '') ? $r['manufacturer'] . ' ' : '') .
                                    ($r['model_name'] ?? '-')
                                ) ?>
                            </td>

                            <!-- Rating -->
                            <td><?= intval($r['rating']) ?> / 5</td>

                            <!-- Komentar -->
                            <td style="max-width:40ch; white-space:normal;">
                                <?= nl2br(htmlspecialchars($r['comments'] ?? '')) ?>
                            </td>

                            <!-- Tanggal -->
                            <td><?= htmlspecialchars($r['created_at']) ?></td>

                            <!-- Aksi -->
                            <td>

                                <!-- Tombol delete -->
                                <form method="post" onsubmit="return confirm('Hapus feedback ini?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_feedback">
                                    <input type="hidden" name="feedback_id" value="<?= htmlspecialchars($r['feedback_id']) ?>">
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>

                                <!-- Link ke request -->
                                <?php if (!empty($r['test_drive_id'])): ?>
                                    <a class="btn btn-sm btn-warning"
                                        href="index.php?folder=requests&page=view&id=<?= intval($r['test_drive_id']) ?>">
                                        Request
                                    </a>
                                <?php endif; ?>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>

        </div>
    </div>

<?php endif; ?>