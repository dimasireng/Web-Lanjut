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

$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
?>

<h1 class="h4 mb-3">Users</h1>

<a href="index.php?folder=users&page=create" class="btn btn-sm btn-primary mb-3">+ Add User</a>

<div class="table-rounded">
  <div class="table-responsive">
    <table class="table data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Active</th>
          <th width="140">Actions</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= intval($u['id']) ?></td>
            <td><?= h($u['full_name']) ?></td>

            <td>
              <span class="email-text"><?= h($u['email']) ?></span>
            </td>

            <td><?= h($u['phone']) ?></td>
            <td><?= $u['role_id'] == 1 ? 'Admin' : 'Customer' ?></td>
            <td><?= $u['is_active'] ? 'Yes' : 'No' ?></td>

            <td>
              <a href="index.php?folder=users&page=edit&id=<?= intval($u['id']) ?>"
                class="btn btn-sm btn-warning">Edit</a>

              <form method="post" action="index.php?folder=users&page=delete" style="display:inline"
                onsubmit="return confirm('Delete this user?');">
                <input type="hidden" name="id" value="<?= intval($u['id']) ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>

    </table>
  </div>
</div>