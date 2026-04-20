<?php $u = Auth::check() ? Auth::currentUser() : null; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>A.R.M.A — Aplicação de Registro e Mapeamento de Ativos</title>
<link rel="stylesheet" href="<?= str_repeat('../', 0) ?>assets/css/app.css" />
</head>
<body>
<header class="topbar">
  <div class="container row">
    <div>
      <h1 class="brand">A.R.M.A</h1>
      <p class="brand-sub">Aplicação de Registro e Mapeamento de Ativos</p>
    </div>
    <?php if ($u): ?>
    <nav class="nav">
      <span class="user">👤 <?= htmlspecialchars($u['username']) ?> · <?= htmlspecialchars($u['role']) ?></span>
      <?php if ($u['role'] === 'admin'): ?>
        <a href="users.php">Usuários</a>
        <a href="assets.php">Ativos</a>
        <a href="backup.php">Backup / DR</a>
        <a href="audit.php">Auditoria</a>
      <?php endif; ?>
      <a href="logout.php" class="danger">Sair</a>
    </nav>
    <?php endif; ?>
  </div>
</header>
