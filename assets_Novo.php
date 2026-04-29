<?php
require_once __DIR__ . '/includes/bootstrap.php';
Auth::requireAdmin();
$pdo = Database::connect();
$err = null; $msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $st = $pdo->prepare('INSERT INTO arma_assets (name, description, category, ip_lan, ip_dmz, port, environment, url, tags, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $st->execute([
                $_POST['name'], $_POST['description'] ?? '', $_POST['category'] ?? 'Geral',
                $_POST['ip_lan'] ?? '', $_POST['ip_dmz'] ?? '', $_POST['port'] ?? '',
                $_POST['environment'] ?? 'Produção', $_POST['url'] ?? '',
                $_POST['tags'] ?? '', date('Y-m-d H:i:s')
            ]);
            Audit::log($_SESSION['username'], 'CREATE', "Ativo '{$_POST['name']}' criado");
        } elseif ($action === 'update') {
            $st = $pdo->prepare('UPDATE arma_assets SET name=?, description=?, category=?, ip_lan=?, ip_dmz=?, port=?, environment=?, url=?, tags=? WHERE id=?');
            $st->execute([
                $_POST['name'], $_POST['description'] ?? '', $_POST['category'] ?? 'Geral',
                $_POST['ip_lan'] ?? '', $_POST['ip_dmz'] ?? '', $_POST['port'] ?? '',
                $_POST['environment'] ?? 'Produção', $_POST['url'] ?? '',
                $_POST['tags'] ?? '', (int)$_POST['id']
            ]);
            Audit::log($_SESSION['username'], 'UPDATE', "Ativo id={$_POST['id']} atualizado");
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM arma_assets WHERE id = ?')->execute([(int)$_POST['id']]);
            Audit::log($_SESSION['username'], 'DELETE', "Ativo id={$_POST['id']} removido");
        } elseif ($action === 'cat_create') {
            $name = trim($_POST['cat_name'] ?? '');
            if ($name) {
                $pdo->prepare('INSERT INTO arma_categories (name) VALUES (?)')->execute([$name]);
                Audit::log($_SESSION['username'], 'CREATE', "Categoria '$name'");
            }
        } elseif ($action === 'cat_delete') {
            $pdo->prepare('DELETE FROM arma_categories WHERE id = ?')->execute([(int)$_POST['id']]);
        }
        $msg = 'Operação concluída.';
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$assets = $pdo->query('SELECT * FROM arma_assets ORDER BY id DESC')->fetchAll();
$cats = $pdo->query('SELECT * FROM arma_categories ORDER BY name')->fetchAll();
include __DIR__ . '/includes/header.php';
?>

<main class="container">
    <h2>Gestão de Ativos</h2>
    
    <?php if ($msg): ?><div class="alert ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <details class="card">
        <summary><strong>Categorias</strong></summary>
        <form method="post" class="row-form">
            <input type="hidden" name="action" value="cat_create">
            <input name="cat_name" placeholder="Nova categoria" required>
            <button class="btn">Adicionar</button>
        </form>
        <ul class="taglist">
            <?php foreach ($cats as $c): ?>
                <li>
                    <?= htmlspecialchars($c['name']) ?>
                    <form method="post" class="inline">
                        <input type="hidden" name="action" value="cat_delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="link danger">×</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </details>

    <details class="card" id="form-container" open>
        <summary><strong id="form-title">+ Novo Ativo</strong></summary>
        <form method="post" class="grid-form" id="asset-form">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="id" id="asset-id" value="">
            
            <label>Nome<input name="name" id="field-name" required></label>
            
            <label>Categoria
                <input name="category" id="field-category" list="catlist" value="Geral">
                <datalist id="catlist">
                    <?php foreach ($cats as $c): ?><option value="<?= htmlspecialchars($c['name']) ?>"><?php endforeach; ?>
                </datalist>
            </label>

            <label>Ambiente
                <select name="environment" id="field-environment">
                    <option value="Produção">Produção</option>
                    <option value="Homologação">Homologação</option>
                    <option value="Laboratório">Laboratório</option>
                </select>
            </label>

            <label>IP LAN<input name="ip_lan" id="field-ip_lan"></label>
            <label>IP DMZ<input name="ip_dmz" id="field-ip_dmz"></label>
            <label>Porta<input name="port" id="field-port"></label>
            <label>URL<input name="url" id="field-url" placeholder="https://..."></label>
            
            <label class="full">Descrição<textarea name="description" id="field-description"></textarea></label>
            <label class="full">Tags<input name="tags" id="field-tags" placeholder="separadas por vírgula"></label>
            
            <div class="form-buttons">
                <button class="btn primary">Salvar Ativo</button>
                <button type="button" class="btn" id="btn-cancel" style="display:none; background:#666;" onclick="resetForm()">Cancelar Edição</button>
            </div>
        </form>
    </details>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Ambiente</th>
                <th>IP LAN</th>
                <th>Porta</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assets as $a): ?>
                <tr>
                    <td><?= $a['id'] ?></td>
                    <td><?= htmlspecialchars($a['name']) ?></td>
                    <td><?= htmlspecialchars($a['category']) ?></td>
                    <td><?= htmlspecialchars($a['environment']) ?></td>
                    <td><?= htmlspecialchars($a['ip_lan']) ?></td>
                    <td><?= htmlspecialchars($a['port']) ?></td>
                    <td>
                        <button class="btn sm" onclick='editAsset(<?= json_encode($a) ?>)'>Editar</button>
                        
                        <form method="post" class="inline" onsubmit="return confirm('Remover ativo?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <button class="btn danger sm">Remover</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

<script>
function editAsset(data) {
    // Alterna o cabeçalho e a ação do form
    document.getElementById('form-title').innerText = 'Editar Ativo: ' + data.name;
    document.getElementById('form-action').value = 'update';
    document.getElementById('asset-id').value = data.id;

    // Preenche os campos
    document.getElementById('field-name').value = data.name;
    document.getElementById('field-category').value = data.category;
    document.getElementById('field-environment').value = data.environment;
    document.getElementById('field-ip_lan').value = data.ip_lan;
    document.getElementById('field-ip_dmz').value = data.ip_dmz;
    document.getElementById('field-port').value = data.port;
    document.getElementById('field-url').value = data.url;
    document.getElementById('field-description').value = data.description;
    document.getElementById('field-tags').value = data.tags;

    // Exibe botão cancelar e sobe a página
    document.getElementById('btn-cancel').style.display = 'inline-block';
    document.getElementById('form-container').open = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('asset-form').reset();
    document.getElementById('form-title').innerText = '+ Novo Ativo';
    document.getElementById('form-action').value = 'create';
    document.getElementById('asset-id').value = '';
    document.getElementById('btn-cancel').style.display = 'none';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
