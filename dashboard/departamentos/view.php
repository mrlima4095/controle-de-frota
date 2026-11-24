<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Detalhes do departamento';
$pageSubtitle = 'Área e veículos vinculados.';
$activeMenu = 'departamentos';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id_departamento, nome, responsavel, ativo, data_criacao FROM departamento WHERE id_departamento = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$departamento = $stmt->fetch();

if (!$departamento) {
    header('Location: /dashboard/departamentos/index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id_vei, placa, marca, modelo, status FROM veiculo WHERE id_dep = :id ORDER BY placa ASC');
$stmt->execute([':id' => $id]);
$veiculos = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Departamento atualizado com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 mb-1"><?= h($departamento['nome']) ?></h2>
        <p class="text-muted small mb-2">Responsável: <?= h($departamento['responsavel'] ?? 'Não informado') ?></p>
        <span class="badge rounded-pill <?= $departamento['ativo'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $departamento['ativo'] ? 'Ativo' : 'Inativo' ?></span>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="index.php">← Voltar</a>
        <a class="btn btn-brand" href="edit.php?id=<?= $id ?>">✏️ Editar</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between gap-2">
        <h3 class="h6 mb-0">Veículos do departamento</h3>
        <span class="text-muted small"><?= count($veiculos) ?> veículo(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">Placa</th>
                    <th scope="col">Veículo</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$veiculos): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Nenhum veículo neste departamento.</td></tr>
                <?php else: ?>
                    <?php foreach ($veiculos as $veiculo): ?>
                        <tr>
                            <td><strong><?= h($veiculo['placa']) ?></strong></td>
                            <td class="small text-muted"><?= h(trim(($veiculo['marca'] ?? '') . ' ' . ($veiculo['modelo'] ?? ''))) ?></td>
                            <td><span class="badge rounded-pill text-bg-light text-dark"><?= h($veiculo['status']) ?></span></td>
                            <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" href="/dashboard/veiculos/view.php?id=<?= (int) $veiculo['id_vei'] ?>">👁️ Ver</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
