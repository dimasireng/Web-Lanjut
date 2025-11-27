<?php
require_once __DIR__ . '/../inc_db.php';
require_once __DIR__ . '/../inc_header.php';

// helper aman untuk escaping (hindari passing null ke htmlspecialchars)
if (!function_exists('h')) {
  function h($v)
  {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}

// Home page - simple hero and featured models
function getFeaturedModels($pdo)
{
  if (!$pdo)
    return [
      ['id' => 1, 'manufacturer' => 'Ferrari', 'name' => 'Roma', 'year' => 2023, 'variant' => 'Base', 'image_url' => 'https://via.placeholder.com/600x300?text=Ferrari+Roma'],
      ['id' => 2, 'manufacturer' => 'Lamborghini', 'name' => 'Huracan', 'year' => 2024, 'variant' => 'Evo', 'image_url' => 'https://via.placeholder.com/600x300?text=Lamborghini+Huracan'],
      ['id' => 3, 'manufacturer' => 'BMW', 'name' => 'M4', 'year' => 2021, 'variant' => 'Competition M xDrive', 'image_url' => 'https://via.placeholder.com/600x300?text=BMW+M4'],
    ];

  // Ambil info model + image_url (kolom di table models) + satu file upload terbaik (jika ada)
  $sql = "
      SELECT m.id, m.name, m.variant, m.year, m.image_url,
        (SELECT mi.filename
         FROM model_images mi
         WHERE mi.model_id = m.id
         ORDER BY mi.is_primary DESC, mi.uploaded_at DESC, mi.id DESC
         LIMIT 1) AS image_file,
        mf.name AS manufacturer
      FROM models m
      LEFT JOIN manufacturers mf ON mf.id = m.manufacturer_id
      ORDER BY m.id
      LIMIT 3
    ";
  $stmt = $pdo->query($sql);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$featured = getFeaturedModels($pdo);

// base URL publik untuk file upload (sesuaikan jika project path berbeda)
$baseUploadUrl = '/showroom/uploads/models/';
// path absolut folder uploads (dipakai untuk file_exists)
$absUploadDir = realpath(__DIR__ . '/../') . '/uploads/models/';
// path placeholder (buat file placeholder di project: /showroom/assets/placeholder-car.png)
// jika tidak ada file tersebut, fallback ke via.placeholder
$placeholderLocal = '/showroom/assets/placeholder-car.png';
$placeholder = file_exists($_SERVER['DOCUMENT_ROOT'] . $placeholderLocal) ? $placeholderLocal : 'https://via.placeholder.com/600x300?text=No+Image';
?>
<section class="hero">
  <div class="container">
    <div class="row align-items-center">

      <!-- KIRI -->
      <div class="col-md-6">
        <h1 class="title">Selamat datang di Showroom SuperCar</h1>
        <p class="subtitle">
          Temukan mobil impianmu, ajukan test drive, dan tinggalkan feedback setelah mencoba unit.
        </p>

        <a href="/showroom/cust/models.php" class="btn btn-warning me-2 text-dark fw-bold">
          Lihat Model
        </a>

        <a href="/showroom/cust/test_drive.php" class="btn btn-light fw-bold">
          Ajukan Test Drive
        </a>
      </div>

      <!-- KANAN -->
      <div class="col-md-6 text-center">
        <img src="/showroom/assets/images/hero.png" class="img-fluid premium-hero" alt="GT3 RS Premium">
      </div>

    </div>
  </div>
</section>



<section id="featured" class="container mt-4">
  <h2 class="h5 mb-3">Featured Models</h2>
  <div class="row g-3">
    <?php foreach ($featured as $m):
      // safe casts / defaults
      $id = (int) ($m['id'] ?? 0);
      $manufacturer = (string) ($m['manufacturer'] ?? '');
      $name = (string) ($m['name'] ?? '');
      $variant = (string) ($m['variant'] ?? '');
      $year = (string) ($m['year'] ?? '');
      // tentukan sumber gambar: prioritas pertama = image_url (eksternal) jika valid,
      // jika tidak, coba file upload (image_file), jika tidak ada gunakan placeholder.
      $imageUrl = null;
      if (!empty($m['image_url']) && filter_var($m['image_url'], FILTER_VALIDATE_URL)) {
        $imageUrl = $m['image_url'];
      } else {
        $filename = $m['image_file'] ?? '';
        $filepath = $filename ? ($absUploadDir . $filename) : '';
        if ($filename && file_exists($filepath)) {
          // gunakan URL publik ke folder uploads
          $imageUrl = $baseUploadUrl . rawurlencode($filename);
        } else {
          $imageUrl = $placeholder;
        }
      }

      $modelLabel = trim(($manufacturer ? $manufacturer . ' ' : '') . $name);
      ?>

      <div class="col-sm-6 col-lg-4">
        <div class="card h-100 premium-card">
          <img src="<?= h($imageUrl) ?>" class="card-img-top" alt="<?= h($modelLabel) ?>"
            style="height:200px;object-fit:cover;">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?= h($modelLabel) ?></h5>
            <p class="card-text small text-muted"><?= h($variant . ' — ' . $year) ?></p>
            <div class="mt-auto d-flex justify-content-between align-items-center">
              <a href="/showroom/cust/models.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
              <a href="/showroom/cust/test_drive.php?model_id=<?= $id ?>" class="btn btn-sm btn-primary">Ajukan Test
                Drive</a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="why-section container mt-5 mb-5">
  <h2 class="why-title text-center mb-4">
    Kenapa Memilih Showroom SuperCar?
  </h2>

  <div class="row g-4 justify-content-center">

    <div class="col-6 col-md-3 text-center why-box">
      <div class="why-icon">
        <img src="/showroom/assets/icons/premium.svg" alt="">
      </div>
      <h4>Layanan Premium</h4>
      <p>Test drive VIP & konsultasi personal.</p>
    </div>

    <div class="col-6 col-md-3 text-center why-box">
      <div class="why-icon">
        <img src="/showroom/assets/icons/guarantee.svg" alt="">
      </div>
      <h4>Unit Bergaransi</h4>
      <p>Semua mobil tersertifikasi & original.</p>
    </div>

    <div class="col-6 col-md-3 text-center why-box">
      <div class="why-icon">
        <img src="/showroom/assets/icons/support.svg" alt="">
      </div>
      <h4>Dukungan 24/7</h4>
      <p>Layanan pelanggan selalu siap membantu.</p>
    </div>

    <div class="col-6 col-md-3 text-center why-box">
      <div class="why-icon">
        <img src="/showroom/assets/icons/finance.svg" alt="">
      </div>
      <h4>Skema Pembayaran Fleksibel</h4>
      <p>Pilihan kredit dan cicilan terbaik.</p>
    </div>

  </div>
</section>

<section class="testimonial-section container mt-5 mb-5">
  <h2 class="text-center mb-4">Apa Kata Pelanggan?</h2>

  <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">

      <div class="carousel-item active">
        <div class="testimonial-box">
          <p>"Pelayanan sangat profesional. Test drive VIP bener-bener beda kelas!"</p>
          <h5>— Adrian, Jakarta</h5>
        </div>
      </div>

      <div class="carousel-item">
        <div class="testimonial-box">
          <p>"Unit mobilnya semua original dan terawat. Proses cicilan pun simpel."</p>
          <h5>— Derry, Bandung</h5>
        </div>
      </div>

      <div class="carousel-item">
        <div class="testimonial-box">
          <p>"Customer service sangat responsif, bahkan malam-malam tetap dibantu."</p>
          <h5>— Kevin, Surabaya</h5>
        </div>
      </div>

    </div>
  </div>
</section>

<section class="container text-center my-5">
  <h2 class="fw-bold mb-4">Partner Brand Kami</h2>

  <div class="partner-logos">
    <img src="/showroom/assets/brand/ferrari.png" alt="Ferrari">
    <img src="/showroom/assets/brand/lamborghini.png" alt="Lamborghini">
    <img src="/showroom/assets/brand/bmw.png" alt="BMW">
    <img src="/showroom/assets/brand/mercedes.png" alt="Mercedes">
    <img src="/showroom/assets/brand/porsche.png" alt="Porsche">
  </div>
</section>





<?php require_once __DIR__ . '/../inc_footer.php'; ?>