<?php
// inc_db.php - konfigurasi DB dan koneksi PDO
$dbConfig = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'dbname' => 'showroom',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4'
];
$dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
try {
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
} catch (PDOException $e) {
    $pdo = null; // app will work in demo mode
    $dbError = $e->getMessage();
}
