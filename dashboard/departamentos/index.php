<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Departamentos';
$pageSubtitle = 'Áreas responsáveis pelos veículos da frota.';
$activeMenu = 'departamentos';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$busca = trim($_GET['q'] ?? '');

$sql = 'SELECT d.id_departamento, d.nome, d.responsavel, d.ativo, d.data_criacao,
               (SELECT COUNT(*) FROM veiculo v WHERE v.id_dep = d.id_departamento) AS total_veiculos
        FROM departamento d';
$parametros = [];

if ($busca !== '') {
    $sql .= ' WHERE d.nome LIKE :q OR d.responsavel LIKE :q';
    $parametros[':q'] = '%' . $busca . '%';
}

$sql .= ' ORDER BY d.nome ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$departamentos = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Departamento salvo com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
    <div>
        <h2 class="h5 mb-1">Departamentos cadastrados</h2>
        <p class="text-muted small mb-0"><?= count($departamentos) ?> departamento(s) encontrado(s).</p>
    </div>
    <a class="btn btn-brand" href="create.php">➕ Novo departamento</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-sm-8 col-lg-6">
        <input type="search" class="form-control" name="q" value="<?= h($busca) ?>" placeholder="Buscar por departamento ou responsável">
    </div>
    <div class="col-12 col-sm-auto">
        <button type="submit" class="btn btn-outline-secondary w-100">🔎 Buscar</button>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">Departamento</th>
                    <th scope="col">Responsável</th>
                    <th scope="col">Veículos</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$departamentos): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Nenhum departamento encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($departamentos as $departamento): ?>
                        <tr>
                            <td><strong><?= h($departamento['nome']) ?></strong></td>
                            <td class="small text-muted"><?= h($departamento['responsavel'] ?? '—') ?></td>
                            <td><span class="badge rounded-pill text-bg-light text-dark"><?= (int) $departamento['total_veiculos'] ?></span></td>
                            <td><span class="badge rounded-pill <?= $departamento['ativo'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $departamento['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $departamento['id_departamento'] ?>">👁️ Ver</a>
                                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int) $departamento['id_departamento'] ?>">✏️ Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
