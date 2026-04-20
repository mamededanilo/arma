<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();
header('Content-Type: application/json');
$pdo = Database::connect();
$rows = $pdo->query('SELECT * FROM arma_assets ORDER BY name')->fetchAll();
echo json_encode($rows);
