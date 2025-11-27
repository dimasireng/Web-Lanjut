<?php
require_once __DIR__ . '/../../inc_db.php';
require_once __DIR__ . '/../../inc_auth.php';
require_role($pdo, 1);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role_id = intval($_POST['role_id']);
    $pass = trim($_POST['password']);

    if (!$full_name)
        $errors[] = "Full name required.";
    if (!$email)
        $errors[] = "Email required.";
    if (!$pass)
        $errors[] = "Password required.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("
          INSERT INTO users (full_name,email,phone,role_id,password_hash,is_active)
          VALUES (?,?,?,?,MD5(?),1)
        ");
        $stmt->execute([$full_name, $email, $phone, $role_id, $pass]);

        header("Location: index.php?folder=users&page=list");
        exit;
    }
}
?>

<h1 class="h4 mb-3">Add User</h1>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul><?php foreach ($errors as $e)
            echo "<li>$e</li>"; ?></ul>
    </div>
<?php endif; ?>

<form method="post" class="row g-3">

    <div class="col-md-6">
        <label class="form-label">Full Name</label>
        <input name="full_name" class="form-control" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input name="email" type="email" class="form-control" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input name="phone" class="form-control">
    </div>

    <div class="col-md-3">
        <label class="form-label">Role</label>
        <select name="role_id" class="form-select">
            <option value="1">Admin</option>
            <option value="2">Customer</option>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Password</label>
        <input name="password" type="password" class="form-control" required>
    </div>

    <div class="col-12 text-end">
        <a href="index.php?folder=users&page=list" class="btn btn-secondary">Cancel</a>
        <button class="btn btn-primary">Save</button>
    </div>
</form>