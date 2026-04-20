<?php
require_once __DIR__ . '/includes/bootstrap.php';
Auth::requireAdmin();
$pdo = Database::connect();
$msg = null; $err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $un = trim($_POST['username'] ?? '');
        $pw = $_POST['password'] ?? '';
        $role = ($_POST['role'] ?? 'padrao') === 'admin' ? 'admin' : 'padrao';
        if ($un === '' || strlen($pw) < 6) $err = 'Usuário e senha (>=6) obrigatórios';
        else {
            try {
                $st = $pdo->prepare('INSERT INTO arma_users (username, password_hash, role, must_change_password, created_at) VALUES (?, ?, ?, 0, ?)');
                $st->execute([$un, password_hash($pw, PASSWORD_BCRYPT), $role, date('Y-m-d H:i:s')]);
                Audit::log($_SESSION['username'], 'CREATE', "Usuário '$un' criado ($role)");
                $msg = 'Usuário criado.';
            } catch (Throwable $e) { $err = 'Falha: ' . $e->getMessage(); }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id && $id !== (int)$_SESSION['uid']) {
            $pdo->prepare('DELETE FROM arma_users WHERE id = ?')->execute([$id]);
            Audit::log($_SESSION['username'], 'DELETE', "Usuário id=$id removido");
        }
    } elseif ($action === 'role') {
        $id = (int)($_POST['id'] ?? 0);
        $role = ($_POST['role'] ?? 'padrao') === 'admin' ? 'admin' : 'padrao';
        $pdo->prepare('UPDATE arma_users SET role = ? WHERE id = ?')->execute([$role, $id]);
        Audit::log($_SESSION['username'], 'UPDATE', "Perfil id=$id => $role");
    }
}

$users = $pdo->query('SELECT id, username, role, must_change_password, created_at FROM arma_users ORDER BY id')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<main class="container">
  <h2>Gestão de Usuários</h2>
  <?php if ($msg): ?><div class="alert ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <form method="post" class="card row-form">
    <input type="hidden" name="action" value="create">
    <input name="username" placeholder="Usuário" required>
    <input name="password" type="password" placeholder="Senha (mín 6)" required>
    <select name="role"><option value="padrao">padrão</option><option value="admin">admin</option></select>
    <button class="btn primary">Criar</button>
  </form>

  <table class="table">
    <thead><tr><th>ID</th><th>Usuário</th><th>Perfil</th><th>Criado</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['username']) ?></td>
        <td>
          <form method="post" class="inline">
            <input type="hidden" name="action" value="role">
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <select name="role" onchange="this.form.submit()">
              <option value="padrao" <?= $u['role']==='padrao'?'selected':'' ?>>padrão</option>
              <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>admin</option>
            </select>
          </form>
        </td>
        <td><?= htmlspecialchars($u['created_at']) ?></td>
        <td>
          <?php if ((int)$u['id'] !== (int)$_SESSION['uid']): ?>
          <form method="post" class="inline" onsubmit="return confirm('Remover usuário?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <button class="btn danger sm">Remover</button>
          </form>
          <?php else: ?><span class="muted small">você</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
