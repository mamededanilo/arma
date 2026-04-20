<?php
require_once __DIR__ . '/includes/bootstrap.php';
Auth::requireLogin();
$u = Auth::currentUser();
$msg = null; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (strlen($new) < 8) $err = 'A senha deve ter ao menos 8 caracteres.';
    elseif ($new !== $confirm) $err = 'As senhas não coincidem.';
    else {
        Auth::changePassword((int)$u['id'], $new);
        Audit::log($u['username'], 'PASSWORD_CHANGE', 'Senha alterada');
        header('Location: index.php'); exit;
    }
}
include __DIR__ . '/includes/header.php';
?>
<main class="container">
  <form method="post" class="card narrow">
    <h2>Trocar senha</h2>
    <p class="muted">Por segurança, defina uma nova senha antes de prosseguir.</p>
    <?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <label>Nova senha<input type="password" name="new" required minlength="8"></label>
    <label>Confirmar senha<input type="password" name="confirm" required minlength="8"></label>
    <button class="btn primary">Salvar</button>
  </form>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
