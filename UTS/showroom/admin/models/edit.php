<?php
require_once __DIR__ . '/../../inc_db.php';
require_once __DIR__ . '/../../inc_auth.php';
require_role($pdo, 1);

if (!function_exists('h')) {
  function h($v)
  {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
  }
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: index.php?folder=models&page=list');
  exit;
}

$manufacturers = $pdo->query('SELECT id,name FROM manufacturers ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT * FROM models WHERE id=? LIMIT 1');
$stmt->execute([$id]);
$model = $stmt->fetch();
if (!$model) {
  header('Location: index.php?folder=models&page=list');
  exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $manufacturer_id = intval($_POST['manufacturer_id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $variant = trim($_POST['variant'] ?? '');
  $year = trim($_POST['year'] ?? '');
  $image_url = trim($_POST['image_url'] ?? '');

  if (!$manufacturer_id)
    $errors[] = 'Pilih manufacturer.';
  if ($name === '')
    $errors[] = 'Nama model harus diisi.';
  if ($image_url !== '' && !filter_var($image_url, FILTER_VALIDATE_URL))
    $errors[] = 'Image URL tidak valid.';

  if (empty($errors)) {
    $upd = $pdo->prepare('UPDATE models SET manufacturer_id=?,name=?,variant=?,year=?,image_url=? WHERE id=?');
    $upd->execute([$manufacturer_id, $name, $variant ?: null, $year ?: null, $image_url ?: null, $id]);
    header('Location: index.php?folder=models&page=list');
    exit;
  }
}
?>

<h1 class="h4 mb-3">Edit Model</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul><?php foreach ($errors as $e)
      echo "<li>" . h($e) . "</li>"; ?></ul>
  </div>
<?php endif; ?>

<form method="post" class="row g-3">

  <div class="col-md-6">
    <label class="form-label">Manufacturer</label>
    <select name="manufacturer_id" class="form-select" required>
      <option value="">Pilih...</option>
      <?php foreach ($manufacturers as $mf): ?>
        <option value="<?= $mf['id'] ?>" <?= ((($_POST['manufacturer_id'] ?? $model['manufacturer_id']) == $mf['id']) ? 'selected' : '') ?>>
          <?= h($mf['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Model Name</label>
    <input name="name" class="form-control" required value="<?= h($_POST['name'] ?? $model['name']) ?>">
  </div>

  <div class="col-md-6">
    <label class="form-label">Variant</label>
    <input name="variant" class="form-control" value="<?= h($_POST['variant'] ?? $model['variant']) ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">Year</label>
    <input name="year" class="form-control" value="<?= h($_POST['year'] ?? $model['year']) ?>">
  </div>

  <div class="col-12">
    <label class="form-label">Image URL (optional)</label>
    <input name="image_url" type="url" class="form-control" placeholder="https://example.com/image.jpg"
      value="<?= h($_POST['image_url'] ?? $model['image_url']) ?>">
    <div class="form-text">Kosongkan untuk memakai placeholder.</div>
  </div>

  <div class="col-12 text-end">
    <a href="index.php?folder=models&page=list" class="btn btn-secondary">Batal</a>
    <button class="btn btn-primary">Simpan</button>
  </div>

</form>