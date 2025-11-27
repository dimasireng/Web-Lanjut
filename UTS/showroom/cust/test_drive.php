<?php
// cust/test_drive.php
require_once __DIR__ . '/../inc_db.php';
require_once __DIR__ . '/../inc_auth.php';

// Ambil user login (jika ada)
$loggedUser = null;
if (isset($_SESSION['user_id'])) {
  $stmt = $pdo->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $loggedUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!defined('BASE_URL'))
  define('BASE_URL', '/showroom');

$flash = null;

// Helper
function to_int_or_null($v)
{
  if ($v === null || $v === '')
    return null;
  $i = filter_var($v, FILTER_VALIDATE_INT);
  return $i === false ? null : (int) $i;
}

/* =====================
   HANDLE FORM
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_test_drive') {

  $full_name = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $model_id = to_int_or_null($_POST['model_id'] ?? null);
  $preferred_date = trim($_POST['preferred_date'] ?? '');
  $preferred_time = trim($_POST['preferred_time'] ?? '');
  $notes = trim($_POST['notes'] ?? '');

  if ($full_name === '' || $email === '' || $preferred_date === '') {
    $flash = ['type' => 'danger', 'message' => 'Nama, email, dan tanggal harus diisi.'];
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $flash = ['type' => 'danger', 'message' => 'Format email tidak valid.'];
  } else {
    if (!$pdo) {
      $flash = ['type' => 'warning', 'message' => '(Demo) Database tidak terhubung.'];
    } else {
      try {
        $pdo->beginTransaction();

        // cek user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
          $pwd = password_hash(bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
          $ins = $pdo->prepare("INSERT INTO users (role_id, full_name, email, phone, password_hash) VALUES (3, ?, ?, ?, ?)");
          $ins->execute([$full_name, $email, $phone, $pwd]);
          $userId = $pdo->lastInsertId();
        } else {
          $userId = $user['id'];
          $pdo->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?")
            ->execute([$full_name, $phone, $userId]);
        }

        // insert request
        $pdo->prepare("INSERT INTO test_drive_requests 
                    (user_id, model_id, preferred_date, preferred_time, notes, status)
                    VALUES (?, ?, ?, ?, ?, 'pending')")
          ->execute([$userId, $model_id, $preferred_date, $preferred_time ?: null, $notes ?: null]);

        $pdo->commit();

        header("Location: " . BASE_URL . "/cust/test_drive.php?success=1");
        exit;

      } catch (Throwable $ex) {
        if ($pdo->inTransaction())
          $pdo->rollBack();
        $flash = ['type' => 'danger', 'message' => 'Terjadi kesalahan.'];
      }
    }
  }
}

/* =====================
   HEADER
===================== */
require_once __DIR__ . '/../inc_header.php';

/* =====================
   FETCH MODELS
===================== */
$models = [];
if ($pdo) {
  $sql = "SELECT m.id, m.name, COALESCE(mf.name,'') AS manufacturer, m.variant, m.year, m.body_type
            FROM models m
            LEFT JOIN manufacturers mf ON mf.id = m.manufacturer_id
            ORDER BY m.name";
  $models = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$preselectModel = isset($_GET['model_id']) ? intval($_GET['model_id']) : null;
$success = isset($_GET['success']);
?>

<!-- PREMIUM SECTION -->
<section class="premium-section mb-5">

  <h2 class="h4 fw-bold mb-3">Ajukan Test Drive</h2>

  <?php if ($success): ?>
    <div class="alert alert-success">Pengajuan test drive berhasil dikirim.</div>
  <?php endif; ?>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
  <?php endif; ?>

  <form method="post" class="row g-4 mt-2">
    <input type="hidden" name="action" value="request_test_drive">

    <!-- Nama -->
    <div class="col-md-6">
      <label class="form-label fw-semibold">Nama Lengkap</label>
      <input type="text" name="full_name" class="form-control form-control-lg"
        value="<?= htmlspecialchars($loggedUser['full_name'] ?? '') ?>" required>
    </div>

    <!-- Email -->
    <div class="col-md-6">
      <label class="form-label fw-semibold">Email</label>
      <input type="email" name="email" class="form-control form-control-lg"
        value="<?= htmlspecialchars($loggedUser['email'] ?? '') ?>" required>
    </div>

    <!-- Model -->
    <div class="col-md-6">
      <label class="form-label fw-semibold">Pilih Model</label>
      <select name="model_id" class="form-select form-select-lg" required>
        <option value="">Pilih…</option>
        <?php foreach ($models as $m): ?>
          <option value="<?= $m['id'] ?>" <?= ($preselectModel == $m['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($m['manufacturer'] . ' ' . $m['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Tanggal -->
    <div class="col-md-3">
      <label class="form-label fw-semibold">Tanggal</label>
      <input type="date" name="preferred_date" class="form-control form-control-lg" required>
    </div>

    <!-- Waktu -->
    <div class="col-md-3">
      <label class="form-label fw-semibold">Waktu</label>
      <input type="time" name="preferred_time" class="form-control form-control-lg">
    </div>

    <!-- Catatan -->
    <div class="col-12">
      <label class="form-label fw-semibold">Catatan</label>
      <textarea name="notes" rows="4" class="form-control form-control-lg"></textarea>
    </div>

    <!-- Submit -->
    <div class="col-12 text-end">
      <button class="btn btn-primary btn-lg px-4">Kirim Pengajuan</button>
    </div>
  </form>

</section>

<?php require_once __DIR__ . '/../inc_footer.php'; ?>