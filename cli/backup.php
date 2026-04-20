<?php
// Uso: php cli/backup.php  (configurar via cron diário)
require_once __DIR__ . '/../includes/bootstrap.php';
$cfg = Config::load();
$dir = ARMA_ROOT . '/backups';
if (!is_dir($dir)) mkdir($dir, 0750, true);
$pdo = Database::connect();
$tables = ['arma_users','arma_assets','arma_categories','arma_audit_logs'];
$out = "-- A.R.M.A backup CLI\n-- " . date('c') . "\n\n";
foreach ($tables as $t) {
    foreach ($pdo->query("SELECT * FROM $t") as $row) {
        $cols = array_keys($row);
        $vals = array_map(fn($v) => $v===null?'NULL':$pdo->quote((string)$v), array_values($row));
        $out .= "INSERT INTO $t (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
    }
}
$file = $dir . '/arma-cron-' . date('Ymd-His') . '.sql';
file_put_contents($file, $out);
echo "Backup gerado: $file\n";

// Retenção 30 dias
foreach (glob($dir . '/arma-*.sql') as $f) {
    if (filemtime($f) < time() - 30*86400) @unlink($f);
}
