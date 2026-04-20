<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();
header('Content-Type: application/json');

$pdo = Database::connect();
$assets = $pdo->query('SELECT id, ip_lan, ip_dmz, port FROM arma_assets')->fetchAll();
$result = [];
foreach ($assets as $a) {
    $host = $a['ip_lan'] ?: $a['ip_dmz'];
    $port = (int)($a['port'] ?: 80);
    $status = 'offline';
    if ($host && $port > 0) {
        $errno = 0; $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, 2);
        if ($fp) { $status = 'online'; fclose($fp); }
    }
    $result[$a['id']] = $status;
}
echo json_encode(['checked_at' => date('c'), 'statuses' => $result]);
