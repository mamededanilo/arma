<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (!Config::isInstalled()) { header('Location: install/'); exit; }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if (Auth::attempt($u, $p)) {
        header('Location: ' . ($_SESSION['must_change_password'] ? 'change-password.php' : 'index.php'));
        exit;
    }
    $error = 'Credenciais inválidas';
}
?>
<!DOCTYPE html>
<html lang="pt-BR"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login · A.R.M.A</title>
<link rel="stylesheet" href="assets/css/app.css">
</head><body class="login-bg">
<div class="login-wrap">
  <div class="brand-block">
    <h1 class="brand glow">A.R.M.A</h1>
    <p class="brand-sub">Aplicação de Registro e Mapeamento de Ativos</p>
  </div>
  <form method="post" class="card">
    <h2>Acessar</h2>
    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <label>Usuário<input name="username" autofocus required></label>
    <label>Senha<input name="password" type="password" required></label>
    <button class="btn primary block">Entrar</button>
    <p class="muted small">Padrão de fábrica: admin / admin (troca obrigatória no 1º acesso)</p>
  </form>
</div>
</body></html>
