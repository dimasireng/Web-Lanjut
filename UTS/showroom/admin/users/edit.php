<?php
require_once __DIR__ . '/../../inc_db.php';
require_once __DIR__ . '/../../inc_auth.php';
require_role($pdo, 1);

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo "<div class='alert alert-danger'>User not found.</div>";
    return;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role_id = intval($_POST['role_id']);
    $active = isset($_POST['is_active']) ? 1 : 0;

    if (!$full_name)
        $errors[] = "Full name required.";
    if (!$email)
        $errors[] = "Email required.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, role_id=?, is_active=? WHERE id=?");
        $stmt->execute([$full_name, $email, $phone, $role_id, $active, $id]);

        if (!empty($_POST['password'])) {
            $stmt = $pdo->prepare("UPDATE users SET password_hash=MD5(?) WHERE id=?");
            $stmt->execute([$_POST['password'], $id]);
        }

        header("Location: index.php?folder=users&page=list");
        exit;
    }
}
?>

<h1 class="h4 mb-3">Edit User</h1>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $e)
                echo "<li>$e</li>"; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="row g-3">

    <div class="col-md-6">
        <label class="form-label">Full Name</label>
        <input name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>">
    </div>

    <div class="col-md-3">
        <label class="form-label">Role</label>
        <select name="role_id" class="form-select">
            <option value="1" <?= $user['role_id'] == 1 ? 'selected' : '' ?>>Admin</option>
            <option value="2" <?= $user['role_id'] == 2 ? 'selected' : '' ?>>Customer</option>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">New Password (optional)</label>
        <input name="password" type="password" class="form-control">
    </div>

    <div class="col-12">
        <label>
            <input type="checkbox" name="is_active" <?= $user['is_active'] ? 'checked' : '' ?>> Active
        </label>
    </div>

    <div class="col-12 text-end">
        <a href="index.php?folder=users&page=list" class="btn btn-secondary">Cancel</a>
        <button class="btn btn-primary">Save</button>
    </div>
</form>