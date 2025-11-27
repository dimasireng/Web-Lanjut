<?php
require_once __DIR__ . '/../inc_db.php';
require_once __DIR__ . '/../inc_header.php';

if (!defined('BASE_URL'))
  define('BASE_URL', '/showroom');

/* ===========================
   FETCH LIST MODEL
=========================== */
function fetchModels($pdo)
{
  if (!$pdo)
    return [];

  $sql = "SELECT 
            m.id, m.name, m.variant, m.year, m.body_type,
            COALESCE(mf.name, '') AS manufacturer,
            'available' AS status
          FROM models m
          LEFT JOIN manufacturers mf ON mf.id = m.manufacturer_id
          ORDER BY m.name";

  try {
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    error_log('fetchModels error: ' . $e->getMessage());
    return [];
  }
}

/* ===========================
   AMBIL GAMBAR UTAMA MODEL
=========================== */
function getModelImage($pdo, $model_id)
{
  $baseDir = realpath(__DIR__ . '/../uploads/models/') . '/';
  $baseUrl = '/showroom/uploads/models/';
  $placeholder = '/showroom/assets/placeholder-car.png';

  $sql = "SELECT filename 
          FROM model_images 
          WHERE model_id = ?
          ORDER BY is_primary DESC, id DESC
          LIMIT 1";

  try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$model_id]);
    $file = $stmt->fetchColumn();

    if ($file && file_exists($baseDir . $file)) {
      return $baseUrl . rawurlencode($file);
    }
  } catch (Throwable $e) {
    error_log('getModelImage error: ' . $e->getMessage());
  }

  return $placeholder;
}

$models = fetchModels($pdo);
?>

<section id="models" class="mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4">Daftar Model</h2>
  </div>

  <?php if (empty($models)): ?>
    <div class="alert alert-info">Belum ada model tersedia.</div>
  <?php else: ?>
    <div class="row g-3">

      <?php foreach ($models as $m):
        $id = (int) ($m['id'] ?? 0);
        $manufacturer = htmlspecialchars($m['manufacturer'] ?? '');
        $name = htmlspecialchars($m['name'] ?? '');
        $variant = htmlspecialchars($m['variant'] ?? '');
        $year = htmlspecialchars($m['year'] ?? '');
        $body_type = htmlspecialchars($m['body_type'] ?? '-');
        $status = htmlspecialchars($m['status'] ?? 'available');


        // AMBIL GAMBAR UTAMA
        $img = getModelImage($pdo, $id);

        $testDriveUrl = BASE_URL . '/cust/test_drive.php?model_id=' . $id;
        ?>

        <div class="col-sm-6 col-lg-4">
          <div class="card h-100 model-card">


            <img src="<?= $img ?>" class="card-img-top" style="height:200px;object-fit:cover;"
              alt="<?= $manufacturer . ' ' . $name ?>">

            <div class="card-body d-flex flex-column">
              <h5 class="card-title"><?= $manufacturer . ' ' . $name ?></h5>
              <p class="card-text small text-muted"><?= $variant ?> — <?= $year ?></p>
              <p class="small mb-3">Type: <?= $body_type ?></p>

              <div class="mt-auto d-flex justify-content-between align-items-center">
                <span class="badge badge-available">
                  <?= $status ?>
                </span>

                <a href="<?= htmlspecialchars($testDriveUrl) ?>"
                  class="btn btn-sm btn-primary<?= ($status !== 'available') ? ' disabled' : '' ?>">
                  Ajukan Test Drive
                </a>
              </div>
            </div>

          </div>
        </div>

      <?php endforeach; ?>

    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../inc_footer.php'; ?>