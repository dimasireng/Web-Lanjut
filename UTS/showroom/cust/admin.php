<?php
require_once __DIR__ . '/../inc_db.php';
require_once __DIR__ . '/../inc_header.php';

// Simple admin list of test drive requests
function fetchRequests($pdo){
    if(!$pdo) return [
        ['id'=>1,'full_name'=>'Adi Pratama','email'=>'adi@example.com','model_name'=>'Ferrari Roma','preferred_date'=>'2025-11-20','preferred_time'=>'10:00','status'=>'pending']
    ];
    $sql = "SELECT t.id, u.full_name, u.email, COALESCE(m.name, '-') AS model_name, t.preferred_date, t.preferred_time, t.status
            FROM test_drive_requests t
            JOIN users u ON u.id = t.user_id
            LEFT JOIN models m ON m.id = t.model_id
            ORDER BY t.created_at DESC LIMIT 200";
    return $pdo->query($sql)->fetchAll();
}
$requests = fetchRequests($pdo);
?>
<section>
  <h2 class="h4">Admin — Manajemen Test Drive</h2>
  <div class="table-responsive">
    <table class="table table-sm">
      <thead><tr><th>#</th><th>User</th><th>Model</th><th>Tanggal</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($requests as $r): ?>
          <tr>
            <td><?=htmlspecialchars($r['id'])?></td>
            <td><?=htmlspecialchars($r['full_name']).'<br><small class="text-muted">'.htmlspecialchars($r['email']).'</small>'?></td>
            <td><?=htmlspecialchars($r['model_name'])?></td>
            <td><?=htmlspecialchars($r['preferred_date']).' '.htmlspecialchars($r['preferred_time'])?></td>
            <td><span class="badge bg-<?= $r['status'] === 'pending' ? 'warning' : ($r['status'] === 'approved' ? 'success' : 'secondary') ?>"><?=htmlspecialchars($r['status'])?></span></td>
            <td>
              <!-- For now actions are placeholders; implement permission checks and POST handlers -->
              <button class="btn btn-sm btn-success" disabled>Approve</button>
              <button class="btn btn-sm btn-danger" disabled>Reject</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once __DIR__ . '/../inc_footer.php'; ?>
