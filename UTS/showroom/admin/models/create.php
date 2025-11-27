<?php
require_once __DIR__ . '/../../inc_db.php';
require_once __DIR__ . '/../../inc_auth.php';
require_role($pdo, 1);

$errors = [];
$manufacturers = $pdo->query('SELECT id, name FROM manufacturers ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

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
    $ins = $pdo->prepare("INSERT INTO models (manufacturer_id,name,variant,year,image_url)
                          VALUES (?,?,?,?,?)");
    $ins->execute([$manufacturer_id, $name, $variant ?: null, $year ?: null, $image_url ?: null]);
    header('Location: index.php?folder=models&page=list');
    exit;
  }
}
?>

<h1 class="h4 mb-3">Add Model</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul>
      <?php foreach ($errors as $e)
        echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" class="row g-3">

  <div class="col-md-6">
    <label class="form-label">Manufacturer</label>
    <select name="manufacturer_id" class="form-select" required>
      <option value="">Pilih...</option>
      <?php foreach ($manufacturers as $mf): ?>
        <option value="<?= $mf['id'] ?>" <?= (($_POST['manufacturer_id'] ?? '') == $mf['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($mf['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Model Name</label>
    <input name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
  </div>

  <div class="col-md-6">
    <label class="form-label">Variant</label>
    <input name="variant" class="form-control" value="<?= htmlspecialchars($_POST['variant'] ?? '') ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">Year</label>
    <input name="year" class="form-control" value="<?= htmlspecialchars($_POST['year'] ?? '') ?>">
  </div>

  <div class="col-12">
    <label class="form-label">Image URL (optional)</label>
    <input name="image_url" type="url" class="form-control" placeholder="https://example.com/image.jpg"
      value="<?= htmlspecialchars($_POST['image_url'] ?? '') ?>">
    <div class="form-text">Kosongkan untuk memakai placeholder.</div>
  </div>

  <div class="col-12 text-end">
    <a href="index.php?folder=models&page=list" class="btn btn-secondary">Batal</a>
    <button class="btn btn-primary">Simpan</button>
  </div>
</form>