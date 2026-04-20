<?php
require_once __DIR__ . '/includes/bootstrap.php';
Auth::requireAdmin();
$rows = Database::connect()->query('SELECT * FROM arma_audit_logs ORDER BY id DESC LIMIT 500')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<main class="container">
  <h2>Auditoria</h2>
  <table class="table">
    <thead><tr><th>Quando</th><th>Usuário</th><th>Ação</th><th>Detalhes</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['created_at']) ?></td>
        <td><?= htmlspecialchars($r['username']) ?></td>
        <td><span class="tag"><?= htmlspecialchars($r['action']) ?></span></td>
        <td><?= htmlspecialchars($r['details']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
