<?php
// admin/models/delete.php
require_once __DIR__ . '/../../inc_db.php';
require_once __DIR__ . '/../../inc_auth.php';
require_role($pdo, 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        // jika sebelumnya ada model_images rows, Anda mungkin ingin menghapus mereka juga:
        $pdo->prepare('DELETE FROM model_images WHERE model_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM models WHERE id = ?')->execute([$id]);
    }
}

header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?folder=models&page=list');
exit;
