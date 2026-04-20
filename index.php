<?php
// A.R.M.A - Aplicação de Registro e Mapeamento de Ativos
// Front controller
require_once __DIR__ . '/includes/bootstrap.php';

if (!Config::isInstalled()) {
    header('Location: install/');
    exit;
}

Auth::requireLogin();
$user = Auth::currentUser();

if ($user['must_change_password']) {
    header('Location: change-password.php');
    exit;
}

include __DIR__ . '/includes/header.php';
?>
<main class="container">
  <section class="hero">
    <h2>Painel de Ativos</h2>
    <p class="muted">Bem-vindo, <strong><?= htmlspecialchars($user['username']) ?></strong> · perfil <?= htmlspecialchars($user['role']) ?></p>
  </section>

  <div id="stats" class="stats-bar"></div>
  <div class="toolbar">
    <input type="search" id="search" placeholder="Buscar ativo..." />
    <select id="category"><option value="">Todas categorias</option></select>
    <?php if ($user['role'] === 'admin'): ?>
      <button id="newAsset" class="btn primary">+ Novo Ativo</button>
    <?php endif; ?>
    <button id="refresh" class="btn">↻ Atualizar status</button>
  </div>
  <div id="assets" class="grid"></div>
</main>
<script>window.ARMA = { isAdmin: <?= $user['role']==='admin' ? 'true':'false' ?> };</script>
<script src="assets/js/app.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
