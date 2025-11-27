<?php
// admin/models/list.php
require_once __DIR__ . '/../../inc_db.php';
require_once __DIR__ . '/../../inc_auth.php';
require_role(1, $pdo);

// ambil data
$sql = "SELECT m.id, m.name, m.variant, m.year, mf.name AS manufacturer,
               (SELECT filename FROM model_images WHERE model_id = m.id AND is_primary = 1 LIMIT 1) AS primary_image,
               COALESCE(m.image_url, '') AS image_url
        FROM models m
        JOIN manufacturers mf ON mf.id = m.manufacturer_id
        ORDER BY m.name";
$models = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// base dir
$uploadDir = __DIR__ . '/../../uploads/models/';
$uploadUrl = '/showroom/uploads/models/';
$placeholder = '/showroom/assets/placeholder-car.png';
?>

<h1 class="h4 mb-3">Models</h1>

<div class="mb-3">
  <a href="index.php?folder=models&page=create" class="btn btn-primary btn-sm">+ Add Model</a>
</div>

<style>
  .thumb-box {
    width: 120px;
    height: 72px;
    overflow: hidden;
    border-radius: 6px;
    background: #f5f5f5;
  }

  .thumb-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  td.img-cell {
    width: 140px;
  }
</style>

<div class="table-rounded">
  <div class="table-responsive">
    <table class="table data-table align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>Image</th>
          <th>Manufacturer</th>
          <th>Model</th>
          <th>Variant</th>
          <th>Year</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($models as $m):
          $primary = $m['primary_image'] ?? '';
          $imgSrc = $placeholder;

          if (!empty($m['image_url']) && filter_var($m['image_url'], FILTER_VALIDATE_URL)) {
            $imgSrc = $m['image_url'];
          } elseif ($primary && file_exists($uploadDir . $primary)) {
            $imgSrc = $uploadUrl . rawurlencode($primary);
          }
          ?>
          <tr>
            <td><?= htmlspecialchars($m['id']) ?></td>

            <td class="img-cell">
              <div class="thumb-box">
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($m['name']) ?>">
              </div>
            </td>

            <td><?= htmlspecialchars($m['manufacturer']) ?></td>
            <td><?= htmlspecialchars($m['name']) ?></td>
            <td><?= htmlspecialchars($m['variant']) ?></td>
            <td><?= htmlspecialchars($m['year']) ?></td>

            <td>
              <a class="btn btn-sm btn-warning"
                href="index.php?folder=models&page=edit&id=<?= intval($m['id']) ?>">Edit</a>

              <form method="post" action="index.php?folder=models&page=delete" style="display:inline"
                onsubmit="return confirm('Hapus model ini? Semua gambar akan ikut terhapus.');">
                <input type="hidden" name="id" value="<?= intval($m['id']) ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>

    </table>
  </div>
</div>