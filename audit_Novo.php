<?php
require_once __DIR__ . '/includes/bootstrap.php';
Auth::requireAdmin();

// 1. Configurações da Paginação
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$db = Database::connect();

// 2. Contagem Total de Registros
$total_res = $db->query('SELECT COUNT(*) FROM arma_audit_logs')->fetchColumn();
$total_paginas = ceil($total_res / $itens_por_pagina);

// 3. Consulta com LIMIT e OFFSET
$stmt = $db->prepare('SELECT * FROM arma_audit_logs ORDER BY id DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<main class="container">
    <h2>Auditoria</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Quando</th>
                <th>Usuário</th>
                <th>Ação</th>
                <th>Detalhes</th>
            </tr>
        </thead>
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

    <nav aria-label="Navegação de página">
        <ul class="pagination">
            <?php if ($pagina_atual > 1): ?>
                <li class="page-item"><a class="page-link" href="?p=<?= $pagina_atual - 1 ?>">Anterior</a></li>
            <?php endif; ?>

            <li class="page-item disabled"><span class="page-link">Página <?= $pagina_atual ?> de <?= $total_paginas ?></span></li>

            <?php if ($pagina_atual < $total_paginas): ?>
                <li class="page-item"><a class="page-link" href="?p=<?= $pagina_atual + 1 ?>">Próxima</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
