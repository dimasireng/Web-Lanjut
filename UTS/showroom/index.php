<?php
require_once __DIR__ . '/inc_db.php';
require_once __DIR__ . '/inc_header.php';

// Home page - simple hero and featured models
function getFeaturedModels($pdo){
    if(!$pdo) return [
        ['id'=>1,'manufacturer'=>'Ferrari','name'=>'Roma','year'=>2023,'status'=>'available'],
        ['id'=>2,'manufacturer'=>'Lamborghini','name'=>'Huracan','year'=>2024,'status'=>'available']
    ];
    $stmt = $pdo->query("SELECT m.id, m.name, m.variant, m.year, mf.name AS manufacturer FROM models m JOIN manufacturers mf ON mf.id = m.manufacturer_id ORDER BY m.id LIMIT 3");
    return $stmt->fetchAll();
}
$featured = getFeaturedModels($pdo);
?>
<section class="hero text-dark">
  <div class="row align-items-center">
    <div class="col-md-8">
      <h1 class="display-6">Selamat datang di Showroom SuperCar</h1>
      <p class="lead">Temukan mobil impianmu, ajukan test drive, dan tinggalkan feedback setelah mencoba unit.</p>
      <a href="/showroom/cust/models.php" class="btn btn-primary me-2">Lihat Model</a>
      <a href="/showroom/cust/test_drive.php" class="btn btn-outline-primary">Ajukan Test Drive</a>
    </div>
    <div class="col-md-4 text-center"><img src="https://via.placeholder.com/220x140?text=SuperCar" alt="car" class="img-fluid rounded"></div>
  </div>
</section>

<section id="featured">
  <h2 class="h5 mb-3">Featured Models</h2>
  <div class="row g-3">
    <?php foreach($featured as $m): ?>
      <div class="col-sm-6 col-lg-4">
        <div class="card h-100">
          <img src="https://via.placeholder.com/600x300?text=Model+Image" class="card-img-top">
          <div class="card-body">
            <h5 class="card-title"><?=htmlspecialchars(($m['manufacturer'] ?? ''). ' ' . $m['name'])?></h5>
            <p class="card-text small text-muted"><?=htmlspecialchars(($m['variant'] ?? ''). ' — ' . ($m['year'] ?? ''))?></p>
            <a href="/cust/models.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php require_once __DIR__ . '/inc_footer.php'; ?>
