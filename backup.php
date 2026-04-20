<?php
require_once __DIR__ . '/includes/bootstrap.php';
Auth::requireAdmin();
$cfg = Config::load();
$msg = null; $err = null;

$backupDir = ARMA_ROOT . '/backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0750, true);

function dump_database(array $cfg, string $outFile): void {
    $pdo = Database::connect();
    $driver = $cfg['db_driver'];
    $tables = ['arma_users', 'arma_assets', 'arma_categories', 'arma_audit_logs'];
    $sql = "-- A.R.M.A backup\n-- Driver: $driver\n-- Date: " . date('c') . "\n\n";
    foreach ($tables as $t) {
        $sql .= "-- Table: $t\n";
        $rows = $pdo->query("SELECT * FROM $t")->fetchAll();
        foreach ($rows as $row) {
            $cols = array_keys($row);
            $vals = array_map(function($v) use ($pdo) {
                if ($v === null) return 'NULL';
                return $pdo->quote((string)$v);
            }, array_values($row));
            $sql .= "INSERT INTO $t (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
        }
        $sql .= "\n";
    }
    file_put_contents($outFile, $sql);
}

function restore_database(array $cfg, string $sqlFile): void {
    $pdo = Database::connect();
    // Limpa tabelas (ordem para FK)
    foreach (['arma_audit_logs','arma_assets','arma_categories','arma_users'] as $t) {
        $pdo->exec("DELETE FROM $t");
    }
    $sql = file_get_contents($sqlFile);
    // Executa apenas INSERTs
    foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
        $stmt = trim($stmt);
        if (stripos($stmt, 'INSERT') === 0) $pdo->exec($stmt);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $f = $backupDir . '/arma-backup-' . date('Ymd-His') . '.sql';
            dump_database($cfg, $f);
            Audit::log($_SESSION['username'], 'BACKUP', basename($f));
            $msg = 'Backup criado: ' . basename($f);
        } elseif ($action === 'download') {
            $name = basename($_POST['file'] ?? '');
            $path = $backupDir . '/' . $name;
            if (is_file($path)) {
                header('Content-Type: application/sql');
                header('Content-Disposition: attachment; filename="' . $name . '"');
                readfile($path); exit;
            }
        } elseif ($action === 'delete') {
            $name = basename($_POST['file'] ?? '');
            @unlink($backupDir . '/' . $name);
            Audit::log($_SESSION['username'], 'DELETE', "Backup $name removido");
        } elseif ($action === 'restore_upload' && !empty($_FILES['sqlfile']['tmp_name'])) {
            $tmp = $_FILES['sqlfile']['tmp_name'];
            restore_database($cfg, $tmp);
            Audit::log($_SESSION['username'], 'RESTORE', 'Restauração via upload');
            $msg = 'Restauração concluída.';
        } elseif ($action === 'restore_local') {
            $name = basename($_POST['file'] ?? '');
            $path = $backupDir . '/' . $name;
            if (is_file($path)) {
                restore_database($cfg, $path);
                Audit::log($_SESSION['username'], 'RESTORE', "Restaurado de $name");
                $msg = "Restaurado de $name";
            }
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$files = array_values(array_filter(scandir($backupDir), fn($f) => preg_match('/\.sql$/', $f)));
rsort($files);
include __DIR__ . '/includes/header.php';
?>
<main class="container">
  <h2>Backup & Disaster Recovery</h2>
  <p class="muted">Driver atual: <strong><?= htmlspecialchars($cfg['db_driver']) ?></strong> · Banco: <strong><?= htmlspecialchars($cfg['db_name']) ?></strong></p>

  <?php if ($msg): ?><div class="alert ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="card">
    <h3>1. Gerar dump</h3>
    <form method="post"><input type="hidden" name="action" value="create"><button class="btn primary">Criar backup agora</button></form>
    <p class="muted small">Salvo em <code>/backups/</code> no servidor.</p>
  </div>

  <div class="card">
    <h3>2. Restaurar a partir de upload</h3>
    <form method="post" enctype="multipart/form-data" onsubmit="return confirm('Substituir todos os dados atuais?')">
      <input type="hidden" name="action" value="restore_upload">
      <input type="file" name="sqlfile" accept=".sql" required>
      <button class="btn danger">Restaurar</button>
    </form>
  </div>

  <div class="card">
    <h3>Backups armazenados</h3>
    <table class="table">
      <thead><tr><th>Arquivo</th><th>Tamanho</th><th>Ações</th></tr></thead>
      <tbody>
        <?php foreach ($files as $f): $size = filesize($backupDir.'/'.$f); ?>
          <tr>
            <td><?= htmlspecialchars($f) ?></td>
            <td><?= number_format($size/1024, 1) ?> KB</td>
            <td>
              <form method="post" class="inline"><input type="hidden" name="action" value="download"><input type="hidden" name="file" value="<?= htmlspecialchars($f) ?>"><button class="btn sm">Baixar</button></form>
              <form method="post" class="inline" onsubmit="return confirm('Restaurar este backup?')"><input type="hidden" name="action" value="restore_local"><input type="hidden" name="file" value="<?= htmlspecialchars($f) ?>"><button class="btn sm">Restaurar</button></form>
              <form method="post" class="inline" onsubmit="return confirm('Excluir backup?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="file" value="<?= htmlspecialchars($f) ?>"><button class="btn danger sm">Excluir</button></form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h3>Plano de Recuperação de Desastres</h3>
    <ol>
      <li>Backups diários automáticos via cron: <code>php <?= ARMA_ROOT ?>/cli/backup.php</code></li>
      <li>Replicar diretório <code>/backups/</code> em armazenamento externo (S3, NAS, etc.)</li>
      <li>Em caso de perda total: reinstalar o A.R.M.A em <code>www/html/arma/</code>, executar o instalador apontando para banco vazio, depois usar <strong>"Restaurar a partir de upload"</strong>.</li>
      <li>Para portar entre MySQL ↔ PostgreSQL, use o dump gerado por esta tela (apenas INSERTs portáveis).</li>
      <li>Consulte <code>docs/DISASTER_RECOVERY.md</code> para o runbook completo.</li>
    </ol>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
