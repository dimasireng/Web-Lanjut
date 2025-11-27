<?php
require_once __DIR__ . '/inc_db.php';
require_once __DIR__ . '/inc_auth.php';

$next = $_GET['next'] ?? '/cust/index.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    $errors[] = 'Email dan password wajib diisi.';
  } else {
    if (login_user($pdo, $email, $password)) {

      $role_id = $_SESSION['role_id'];

      if ($role_id == 1) {
        header("Location: /showroom/admin/index.php");
        exit;
      }

      if ($role_id == 2) {
        header("Location: /showroom/cust/index.php");
        exit;
      }

      header("Location: /showroom/cust/index.php");
      exit;
    } else {
      $errors[] = 'Email atau password salah.';
    }
  }
}
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Login — Showroom SuperCar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    /* === CINEMATIC LOGIN BACKGROUND === */
    .login-bg {
      position: fixed;
      inset: 0;
      z-index: -3;
      overflow: hidden;
    }

    .login-bg img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0;
      animation: bgFade 28s infinite ease-in-out, bgZoom 28s infinite linear;
    }

    /* each image delay */
    .login-bg img:nth-child(1) {
      animation-delay: 0s;
    }

    .login-bg img:nth-child(2) {
      animation-delay: 7s;
    }

    .login-bg img:nth-child(3) {
      animation-delay: 14s;
    }

    .login-bg img:nth-child(4) {
      animation-delay: 21s;
    }

    /* Fade cycle */
    @keyframes bgFade {
      0% {
        opacity: 0;
      }

      10% {
        opacity: 1;
      }

      40% {
        opacity: 1;
      }

      55% {
        opacity: 0;
      }

      100% {
        opacity: 0;
      }
    }

    /* cinematic slow zoom */
    @keyframes bgZoom {
      0% {
        transform: scale(1.08);
      }

      50% {
        transform: scale(1.0);
      }

      100% {
        transform: scale(1.08);
      }
    }

    /* Gradient overlay */
    .login-overlay {
      position: fixed;
      inset: 0;
      z-index: -2;
      background: linear-gradient(135deg,
          rgba(0, 61, 165, 0.50),
          rgba(255, 210, 0, 0.35));
      backdrop-filter: blur(2px);
    }

    /* === GLASS CARD PREMIUM === */
    .login-card {
      background: rgba(255, 255, 255, 0.22);
      backdrop-filter: blur(18px) saturate(180%);
      -webkit-backdrop-filter: blur(18px) saturate(180%);
      border-radius: 18px;
      padding: 36px;
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35);
      border: 1px solid rgba(255, 255, 255, 0.35);
      animation: floatUp .7s ease-out;
    }

    /* subtle float-up animation */
    @keyframes floatUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* HEADERS */
    h4 {
      font-weight: 700;
      color: #fff;
      text-shadow: 0 0 8px rgba(0, 0, 0, 0.4);
    }

    /* LABELS */
    label {
      color: #f0f0f0;
      font-weight: 500;
    }

    /* INPUTS */
    .form-control {
      background: rgba(255, 255, 255, 0.75) !important;
      border-radius: 10px;
      border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .form-control:focus {
      background: rgba(255, 255, 255, 0.9) !important;
      border-color: #003da5;
      box-shadow: 0 0 12px rgba(0, 131, 255, 0.6);
    }

    /* BUTTON */
    .btn-primary {
      background: #0057ff;
      border: none;
      padding: 10px;
      border-radius: 12px;
      font-weight: 600;
      transition: 0.3s;
    }

    .btn-primary:hover {
      background: #0a6aff;
      box-shadow: 0 0 14px rgba(0, 140, 255, 0.75);
      transform: translateY(-2px);
    }

    /* SHOW PASSWORD */
    .show-pass {
      position: absolute;
      top: 36px;
      right: 14px;
      cursor: pointer;
      color: #003da5;
      font-weight: bold;
      user-select: none;
    }

    .show-pass:hover {
      color: #0a6aff;
    }
  </style>

</head>

<body>

  <div class="login-bg">
    <img src="/showroom/assets/login/bg1.jpg">
    <img src="/showroom/assets/login/bg2.jpg">
    <img src="/showroom/assets/login/bg3.jpg">
    <img src="/showroom/assets/login/bg4.jpg">
  </div>

  <div class="d-flex justify-content-center align-items-center" style="min-height:100vh;">

    <div class="login-card" style="width:380px;">

      <h4 class="mb-3">Login</h4>

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <ul class="mb-0"><?= "<li>" . implode("</li><li>", $errors) . "</li>" ?></ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="?next=<?= urlencode($next) ?>">

        <div class="mb-3">
          <label>Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
          <label>Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>

        <button class="btn btn-primary w-100">Login</button>

      </form>

    </div>

  </div>

</body>

</html>