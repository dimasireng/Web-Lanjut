<?php
// admin/index.php — Admin panel untuk Showroom SuperCar
require_once __DIR__ . '/../inc_db.php';
require_once __DIR__ . '/../inc_auth.php';

// pastikan hanya admin yang mengakses
require_role($pdo, 'admin');

// helper untuk membaca folder/page
$folder = basename($_GET['folder'] ?? '');
$page = basename($_GET['page'] ?? 'home');
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin — Showroom SuperCar</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Michelin-style Admin CSS -->
  <link href="/showroom/assets/css/admin.css" rel="stylesheet">

  <style>
    body::-webkit-scrollbar {
      display: none;
    }

    /* sidebar override supaya warna konsisten */
    .sidebar {
      min-height: 100vh;
      background: var(--michelin-blue) !important;
      color: #fff !important;
      box-shadow: inset -1px 0 0 rgba(255, 255, 255, 0.1);
    }

    .sidebar .nav-link {
      color: rgba(255, 255, 255, 0.85) !important;
      padding: 10px 14px;
      border-radius: 8px;
      margin-bottom: 6px;
      font-weight: 600;
    }

    .sidebar .nav-link.active {
      background: var(--michelin-yellow) !important;
      color: #000 !important;
      border-left: 4px solid var(--michelin-blue-dark) !important;
    }

    .sidebar .nav-link:hover {
      background: rgba(255, 210, 0, 0.15) !important;
      color: #fff !important;
    }

    /* navbar */
    .navbar {
      background: var(--michelin-blue) !important;
      border-bottom: 4px solid var(--michelin-yellow) !important;
    }

    .navbar-brand {
      color: #fff !important;
      font-weight: 700;
      letter-spacing: 0.2px;
    }

    /* dashboard cards */
    .card {
      border: 1px solid var(--michelin-border) !important;
      border-radius: 10px !important;
    }

    main {
      background: var(--michelin-content-bg) !important;
      min-height: 100vh;
      padding-top: 25px !important;
    }
  </style>
</head>

<body>

  <header class="navbar sticky-top shadow">
    <a class="navbar-brand col-md-3 col-lg-2 px-3 fs-6" href="index.php">
      Showroom SuperCar — Admin
    </a>

    <div class="d-flex ms-auto me-3 align-items-center gap-3">
      <span class="text-white small">
        Halo, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>
      </span>
      <a class="btn btn-sm btn-outline-light" href="../logout.php">Sign out</a>
    </div>
  </header>

  <div class="container-fluid">
    <div class="row">

      <!-- SIDEBAR -->
      <nav class="sidebar col-md-3 col-lg-2 p-3">
        <h5 class="mb-3 text-white">Admin Menu</h5>

        <ul class="nav flex-column">
          <li class="nav-item">
            <a class="nav-link <?= $folder == '' ? 'active' : '' ?>" href="index.php">
              Dashboard
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link <?= $folder == 'requests' ? 'active' : '' ?>" href="index.php?folder=requests&page=list">
              Test Drive Requests
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link <?= $folder == 'models' ? 'active' : '' ?>" href="index.php?folder=models&page=list">
              Models
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link <?= $folder == 'users' ? 'active' : '' ?>" href="index.php?folder=users&page=list">
              Users
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link <?= $folder == 'feedback' ? 'active' : '' ?>" href="index.php?folder=feedback&page=list">
              Feedback
            </a>
          </li>
        </ul>

        <hr class="border-light">

        <ul class="nav flex-column mb-auto">
          <li class="nav-item">
            <a class="nav-link text-danger" href="../logout.php">Sign out</a>
          </li>
        </ul>
      </nav>

      <!-- MAIN CONTENT -->
      <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

        <?php
        // include modular page
        $folder = basename($_GET['folder'] ?? '');
        $page = basename($_GET['page'] ?? 'home');

        $file = $folder ? __DIR__ . "/$folder/$page.php" : __DIR__ . "/$page.php";

        if (file_exists($file)) {
          include $file;
        } else {
          try {
            $total = intval($pdo->query("SELECT COUNT(*) FROM test_drive_requests")->fetchColumn());
            $pending = intval($pdo->query("SELECT COUNT(*) FROM test_drive_requests WHERE status='pending'")->fetchColumn());
            $finished = intval($pdo->query("SELECT COUNT(*) FROM test_drive_requests WHERE status IN ('finished','completed')")->fetchColumn());
            $rejected = intval($pdo->query("SELECT COUNT(*) FROM test_drive_requests WHERE status='rejected'")->fetchColumn());
            $feedbacks = intval($pdo->query("SELECT COUNT(*) FROM test_drive_feedback")->fetchColumn());
          } catch (Throwable $e) {
            $total = $pending = $finished = $rejected = $feedbacks = 0;
          }
          ?>

          <h1 class="h4 mb-4">Dashboard</h1>
          <p class="text-muted mb-4">Ringkasan aktivitas Showroom SuperCar</p>

          <div class="row g-3">
            <div class="col-md-2">
              <div class="card text-center p-2">
                <h6>Total Requests</h6>
                <div class="display-6"><?= $total ?></div>
              </div>
            </div>

            <div class="col-md-2">
              <div class="card text-center p-2">
                <h6>Pending</h6>
                <div class="display-6"><?= $pending ?></div>
              </div>
            </div>

            <div class="col-md-2">
              <div class="card text-center p-2">
                <h6>Finished</h6>
                <div class="display-6"><?= $finished ?></div>
              </div>
            </div>

            <div class="col-md-2">
              <div class="card text-center p-2">
                <h6>Rejected</h6>
                <div class="display-6"><?= $rejected ?></div>
              </div>
            </div>

            <div class="col-md-2">
              <div class="card text-center p-2">
                <h6>Feedbacks</h6>
                <div class="display-6"><?= $feedbacks ?></div>
              </div>
            </div>
          </div>

          <?php
        }
        ?>
      </main>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>