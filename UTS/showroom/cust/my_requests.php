<?php
require_once __DIR__ . '/../inc_db.php';
require_once __DIR__ . '/../inc_auth.php';

if (!defined('BASE_URL'))
  define('BASE_URL', '/showroom');


require_login();

function set_flash($type, $text)
{
  $_SESSION['_flash'] = ['type' => $type, 'text' => $text];
}
function get_flash()
{
  $f = $_SESSION['_flash'] ?? null;
  unset($_SESSION['_flash']);
  return $f;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  /* SUBMIT FEEDBACK */
  if ($action === 'submit_feedback') {
    $test_drive_id = intval($_POST['test_drive_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comments = trim($_POST['comments'] ?? '');

    if ($test_drive_id <= 0) {
      set_flash('danger', 'ID pengajuan tidak valid.');
      header('Location: ' . BASE_URL . '/cust/my_requests.php');
      exit;
    }
    if ($rating < 1 || $rating > 5) {
      set_flash('danger', 'Rating harus 1–5.');
      header('Location: ' . BASE_URL . '/cust/my_requests.php');
      exit;
    }

    $stmt = $pdo->prepare('SELECT id, user_id, status FROM test_drive_requests WHERE id = ? LIMIT 1');
    $stmt->execute([$test_drive_id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
      set_flash('danger', 'Pengajuan tidak ditemukan.');
    } else {
      $isAdmin = intval($_SESSION['role_id'] ?? 0) === 1;

      if (!$isAdmin && intval($req['user_id']) !== intval($_SESSION['user_id'])) {
        set_flash('danger', 'Anda tidak berwenang memberi feedback.');
      } elseif (!in_array($req['status'], ['finished', 'completed'])) {
        set_flash('danger', 'Feedback hanya untuk pengajuan yang sudah selesai.');
      } else {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM test_drive_feedback WHERE test_drive_id = ?');
        $chk->execute([$test_drive_id]);
        if (intval($chk->fetchColumn() ?? 0) > 0) {
          set_flash('warning', 'Feedback sudah pernah dikirim.');
        } else {
          $ins = $pdo->prepare('INSERT INTO test_drive_feedback (test_drive_id, rating, comments, created_at) VALUES (?, ?, ?, NOW())');
          try {
            $ins->execute([$test_drive_id, $rating, $comments]);
            set_flash('success', 'Feedback berhasil dikirim.');
          } catch (Throwable $e) {
            set_flash('danger', 'Gagal menyimpan feedback.');
          }
        }
      }
    }

    header('Location: ' . BASE_URL . '/cust/my_requests.php');
    exit;
  }

  /* CANCEL REQUEST */
  if ($action === 'cancel_request') {
    $request_id = intval($_POST['request_id'] ?? 0);

    if ($request_id <= 0) {
      set_flash('danger', 'ID tidak valid.');
      header('Location: ' . BASE_URL . '/cust/my_requests.php');
      exit;
    }

    $stmt = $pdo->prepare('SELECT id, user_id, status FROM test_drive_requests WHERE id = ? LIMIT 1');
    $stmt->execute([$request_id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
      set_flash('danger', 'Pengajuan tidak ditemukan.');
      header('Location: ' . BASE_URL . '/cust/my_requests.php');
      exit;
    }

    $isAdmin = intval($_SESSION['role_id'] ?? 0) === 1;

    if (!$isAdmin && intval($req['user_id']) !== intval($_SESSION['user_id'])) {
      set_flash('danger', 'Anda tidak boleh membatalkan pengajuan ini.');
      header('Location: ' . BASE_URL . '/cust/my_requests.php');
      exit;
    }

    if (!in_array($req['status'], ['pending', 'approved'])) {
      set_flash('danger', 'Pengajuan ini tidak dapat dibatalkan.');
      header('Location: ' . BASE_URL . '/showroom/cust/my_requests.php');
      exit;
    }

    $upd = $pdo->prepare("UPDATE test_drive_requests SET status='cancelled' WHERE id = ?");
    try {
      $upd->execute([$request_id]);
      set_flash('success', 'Pengajuan berhasil dibatalkan.');
    } catch (Throwable $e) {
      set_flash('danger', 'Gagal membatalkan pengajuan.');
    }

    header('Location: ' . BASE_URL . '/cust/my_requests.php');
    exit;
  }
}

require_once __DIR__ . '/../inc_header.php';
?>
<style>
  /* ================================
   WRAPPER CARD
================================ */
  .requests-card-wrapper {
    background: #ffffff;
    border-radius: 16px;
    padding: 20px 25px;
    box-shadow: 0px 4px 14px rgba(0, 0, 0, 0.08);
  }

  /* ================================
   BLUE ADMIN TABLE
================================ */
  .table.requests-blue {
    width: 100%;
    border-collapse: collapse;
    overflow: hidden;
    border-radius: 14px;
  }

  .table.requests-blue thead {
    background: #002b7f;
    color: white;
  }

  .table.requests-blue thead th {
    padding: 14px 18px;
    font-weight: 600;
    font-size: 14px;
    letter-spacing: .3px;
  }

  .table.requests-blue tbody tr:nth-child(even) {
    background: #eaf0ff;
  }

  .table.requests-blue tbody tr:nth-child(odd) {
    background: #f5f7ff;
  }

  .table.requests-blue tbody td {
    padding: 14px 18px;
    font-size: 14px;
  }

  /* Hover */
  .table.requests-blue tbody tr:hover {
    background: #d6e4ff !important;
  }

  /* Status badge rapi */
  .table .badge {
    font-size: .75rem;
    padding: 6px 10px;
    border-radius: 10px;
  }

  /* Button */
  .btn-danger.btn-sm {
    padding: 4px 10px;
    font-size: 12px;
    border-radius: 8px;
  }

  .btn-outline-primary.btn-sm {
    padding: 4px 10px;
    font-size: 12px;
    border-radius: 8px;
  }

  /* Collapse feedback card */
  #fb-form form {
    background: #f8faff;
    border-radius: 10px;
  }
</style>

<?php
$flash = get_flash();

$isAdmin = intval($_SESSION['role_id'] ?? 0) === 1;

if ($isAdmin) {
  $stmt = $pdo->prepare("
        SELECT t.*, m.name AS model_name, mf.name AS manufacturer
        FROM test_drive_requests t
        LEFT JOIN models m ON m.id = t.model_id
        LEFT JOIN manufacturers mf ON mf.id = m.manufacturer_id
        ORDER BY t.created_at DESC
    ");
  $stmt->execute();
} else {
  $stmt = $pdo->prepare("
        SELECT t.*, m.name AS model_name, mf.name AS manufacturer
        FROM test_drive_requests t
        LEFT JOIN models m ON m.id = t.model_id
        LEFT JOIN manufacturers mf ON mf.id = m.manufacturer_id
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC
    ");
  $stmt->execute([$_SESSION['user_id']]);
}

$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="py-3">
  <h2 class="h4">Pengajuan Test Drive</h2>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['text']) ?></div>
  <?php endif; ?>

  <?php if (empty($requests)): ?>
    <div class="alert alert-secondary">Belum ada pengajuan.</div>
  <?php else: ?>
    <div class="table-responsive requests-card-wrapper">
      <table class="table requests-blue">
        <thead>
          <tr>
            <th>ID</th>
            <th>Model</th>
            <th>Jadwal</th>
            <th>Status</th>
            <th>Catatan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $r): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><?= htmlspecialchars(($r['manufacturer'] ? $r['manufacturer'] . ' ' : '') . $r['model_name']) ?></td>
              <td><?= htmlspecialchars($r['preferred_date'] . ' ' . ($r['preferred_time'] ?? '-')) ?></td>
              <td>
                <?php
                $status = $r['status'];
                $badge = [
                  'pending' => 'warning text-dark',
                  'approved' => 'primary',
                  'finished' => 'success',
                  'completed' => 'success',
                  'rejected' => 'danger',
                  'cancelled' => 'secondary'
                ][$status] ?? 'secondary';
                ?>
                <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($status) ?></span>
              </td>
              <td><?= nl2br(htmlspecialchars($r['notes'] ?? '-')) ?></td>
              <td>

                <?php
                $isCompleted = in_array($r['status'], ['finished', 'completed']);
                $isCancelable = in_array($r['status'], ['pending', 'approved']);

                $hasFeedback = false;
                if ($isCompleted) {
                  $chk = $pdo->prepare('SELECT COUNT(*) FROM test_drive_feedback WHERE test_drive_id = ?');
                  $chk->execute([$r['id']]);
                  $hasFeedback = intval($chk->fetchColumn()) > 0;
                }
                ?>

                <?php if ($isCompleted && !$hasFeedback): ?>
                  <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#fb-<?= $r['id'] ?>">Feedback</button>

                  <div class="collapse mt-2" id="fb-<?= $r['id'] ?>">
                    <form method="post" class="card card-body p-2">
                      <input type="hidden" name="action" value="submit_feedback">
                      <input type="hidden" name="test_drive_id" value="<?= $r['id'] ?>">
                      <div class="mb-2">
                        <label class="form-label small">Rating</label>
                        <select name="rating" class="form-select form-select-sm" required>
                          <option value="">Pilih...</option>
                          <option value="5">5 — Sangat memuaskan</option>
                          <option value="4">4 — Puas</option>
                          <option value="3">3 — Cukup</option>
                          <option value="2">2 — Kurang</option>
                          <option value="1">1 — Tidak puas</option>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label small">Komentar</label>
                        <textarea name="comments" class="form-control form-control-sm" rows="2"></textarea>
                      </div>
                      <button class="btn btn-sm btn-primary">Kirim</button>
                    </form>
                  </div>

                <?php elseif ($isCompleted && $hasFeedback): ?>
                  <span class="text-success small">Feedback terkirim</span>

                <?php elseif ($isCancelable && ($isAdmin || $r['user_id'] == $_SESSION['user_id'])): ?>
                  <form method="post" class="d-inline" onsubmit="return confirm('Batalkan pengajuan ini?');">
                    <input type="hidden" name="action" value="cancel_request">
                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                    <button class="btn btn-sm btn-danger">Batalkan</button>
                  </form>

                <?php else: ?>
                  <span class="text-muted small">-</span>
                <?php endif; ?>

              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../inc_footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"></script>