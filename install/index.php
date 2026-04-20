<?php
session_start();
require_once __DIR__ . '/../includes/bootstrap.php';

if (Config::isInstalled() && empty($_GET['force'])) {
    die('<div style="font-family:sans-serif;padding:2rem"><h2>A.R.M.A já está instalado</h2><p>Para reinstalar, remova <code>config/config.php</code>.</p><a href="../">← Ir para o sistema</a></div>');
}

$step = (int)($_GET['step'] ?? 1);
$drivers = Database::detectDrivers();
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 2) {
        $cfg = [
            'db_driver' => $_POST['db_driver'],
            'db_host' => $_POST['db_host'] ?: 'localhost',
            'db_port' => $_POST['db_port'] ?: ($_POST['db_driver']==='mysql' ? '3306' : '5432'),
            'db_name' => $_POST['db_name'],
            'db_user' => $_POST['db_user'],
            'db_pass' => $_POST['db_pass'] ?? '',
        ];
        $test = Database::testConnection($cfg['db_driver'],$cfg['db_host'],$cfg['db_port'],$cfg['db_name'],$cfg['db_user'],$cfg['db_pass']);
        if (!$test['ok']) {
            $err = 'Falha de conexão: ' . $test['error'];
        } else {
            $_SESSION['install_db'] = $cfg;
            header('Location: ?step=3'); exit;
        }
    } elseif ($step === 3) {
        $cfg = $_SESSION['install_db'] ?? null;
        if (!$cfg) { header('Location: ?step=2'); exit; }
        $admin_user = trim($_POST['admin_user'] ?? 'admin') ?: 'admin';
        $admin_pass = $_POST['admin_pass'] ?? 'admin';
        if ($admin_pass === '') $admin_pass = 'admin';
        try {
            // Cria schema
            $pdo = Database::connect($cfg);
            $sqlFile = __DIR__ . '/../sql/schema_' . $cfg['db_driver'] . '.sql';
            $sql = file_get_contents($sqlFile);
            foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
                $stmt = trim($stmt);
                if ($stmt !== '') $pdo->exec($stmt);
            }
            // Cria admin
            $hash = password_hash($admin_pass, PASSWORD_BCRYPT);
            $forceChange = ($admin_user === 'admin' && $admin_pass === 'admin') ? 1 : 0;
            $pdo->prepare('INSERT INTO arma_users (username, password_hash, role, must_change_password, created_at) VALUES (?,?,?,?,?)')
                ->execute([$admin_user, $hash, 'admin', $forceChange, date('Y-m-d H:i:s')]);
            // Salva config
            Config::write($cfg);
            unset($_SESSION['install_db']);
            header('Location: ?step=4&u=' . urlencode($admin_user)); exit;
        } catch (Throwable $e) {
            $err = 'Erro na instalação: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instalação · A.R.M.A</title>
<link rel="stylesheet" href="../assets/css/app.css">
</head><body>
<div class="install-wrap">
  <div class="brand-block">
    <h1 class="brand glow">A.R.M.A</h1>
    <p class="brand-sub">Instalador · Aplicação de Registro e Mapeamento de Ativos</p>
  </div>

  <div class="steps">
    <div class="step <?= $step==1?'active':($step>1?'done':'') ?>">1. Requisitos</div>
    <div class="step <?= $step==2?'active':($step>2?'done':'') ?>">2. Banco</div>
    <div class="step <?= $step==3?'active':($step>3?'done':'') ?>">3. Admin</div>
    <div class="step <?= $step==4?'active':'' ?>">4. Concluído</div>
  </div>

  <?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <?php if ($step === 1): ?>
    <div class="card">
      <h2>Requisitos do sistema</h2>
      <ul>
        <li>PHP <?= PHP_VERSION ?> <?= version_compare(PHP_VERSION,'7.4','>=')?'✅':'❌' ?></li>
        <li>PDO MySQL: <?= $drivers['mysql']?'✅ disponível':'❌ ausente' ?></li>
        <li>PDO PostgreSQL: <?= $drivers['pgsql']?'✅ disponível':'❌ ausente' ?></li>
        <li>Diretório <code>config/</code> gravável: <?= is_writable(dirname(ARMA_CONFIG))?'✅':'❌' ?></li>
        <li>Diretório <code>backups/</code> gravável: <?= is_writable(ARMA_ROOT.'/backups')?'✅':'❌' ?></li>
      </ul>
      <?php if (!$drivers['mysql'] && !$drivers['pgsql']): ?>
        <div class="alert error">Nenhum driver de banco PDO disponível. Instale <code>php-mysql</code> ou <code>php-pgsql</code>.</div>
      <?php else: ?>
        <a class="btn primary" href="?step=2">Continuar →</a>
      <?php endif; ?>
    </div>

  <?php elseif ($step === 2): ?>
    <form method="post" class="card">
      <h2>Conexão com o banco</h2>
      <label>Driver
        <select name="db_driver" id="drv" onchange="document.getElementById('port').value=this.value==='mysql'?'3306':'5432'">
          <?php if ($drivers['mysql']): ?><option value="mysql">MySQL / MariaDB</option><?php endif; ?>
          <?php if ($drivers['pgsql']): ?><option value="pgsql">PostgreSQL</option><?php endif; ?>
        </select>
      </label>
      <div class="grid-form">
        <label>Host<input name="db_host" value="localhost" required></label>
        <label>Porta<input name="db_port" id="port" value="3306" required></label>
        <label>Banco<input name="db_name" required placeholder="arma"></label>
        <label>Usuário<input name="db_user" required></label>
        <label class="full">Senha<input name="db_pass" type="password"></label>
      </div>
      <p class="muted small">O banco precisa existir previamente. O instalador criará apenas as tabelas.</p>
      <button class="btn primary">Testar e continuar →</button>
    </form>

  <?php elseif ($step === 3): ?>
    <form method="post" class="card">
      <h2>Usuário administrador</h2>
      <p class="muted small">Padrão de fábrica é <code>admin / admin</code> — você poderá alterar agora ou será forçado a trocar no primeiro acesso.</p>
      <label>Nome de usuário<input name="admin_user" value="admin" required></label>
      <label>Senha<input name="admin_pass" type="text" value="admin" required></label>
      <p class="muted small">⚠️ Se mantiver <code>admin/admin</code>, a troca de senha será obrigatória no primeiro login.</p>
      <button class="btn primary">Instalar A.R.M.A →</button>
    </form>

  <?php elseif ($step === 4): ?>
    <div class="card">
      <h2>✅ Instalação concluída</h2>
      <p>Usuário criado: <strong><?= htmlspecialchars($_GET['u'] ?? 'admin') ?></strong></p>
      <p class="muted">Por segurança, exclua o diretório <code>install/</code> do servidor.</p>
      <a class="btn primary" href="../login.php">Ir para o login →</a>
    </div>
  <?php endif; ?>
</div>
</body></html>
