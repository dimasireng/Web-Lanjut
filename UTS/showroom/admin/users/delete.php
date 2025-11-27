<?php
require_once __DIR__ . '/../../inc_db.php';
require_once __DIR__ . '/../../inc_auth.php';
require_role($pdo, 1);

$id = intval($_POST['id'] ?? 0);

$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);

header("Location: index.php?folder=users&page=list");
exit;
