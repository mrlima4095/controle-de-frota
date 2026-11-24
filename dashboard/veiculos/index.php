<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Veículos';
$pageSubtitle = 'Frota cadastrada e situação atual de cada veículo.';
$activeMenu = 'veiculos';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function badgeStatus(string $status): string
{
    return match ($status) {
        'disponivel' => 'text-bg-success',
        'em_uso' => 'text-bg-primary',
        'manutencao' => 'text-bg-warning',
        default => 'text-bg-secondary',
    };
}

$busca = trim($_GET['q'] ?? '');

$sql = 'SELECT v.id_vei, v.placa, v.marca, v.modelo, v.tipo, v.status,
               v.quilometragemAtual, d.nome AS departamento
        FROM veiculo v
        LEFT JOIN departamento d ON d.id_departamento = v.id_dep';
$parametros = [];

if ($busca !== '') {
    $sql .= ' WHERE v.placa LIKE :q OR v.marca LIKE :q OR v.modelo LIKE :q';
    $parametros[':q'] = '%' . $busca . '%';
}

$sql .= ' ORDER BY v.placa ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$veiculos = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Veículo salvo com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
    <div>
        <h2 class="h5 mb-1">Frota cadastrada</h2>
        <p class="text-muted small mb-0"><?= count($veiculos) ?> veículo(s) encontrado(s).</p>
    </div>
    <a class="btn btn-brand" href="create.php">➕ Novo veículo</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-sm-8 col-lg-6">
        <input type="search" class="form-control" name="q" value="<?= h($busca) ?>" placeholder="Buscar por placa, marca ou modelo">
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
                    <th scope="col">Placa</th>
                    <th scope="col">Veículo</th>
                    <th scope="col">Departamento</th>
                    <th scope="col">Status</th>
                    <th scope="col">KM atual</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$veiculos): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Nenhum veículo encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($veiculos as $veiculo): ?>
                        <tr>
                            <td><strong><?= h($veiculo['placa']) ?></strong></td>
                            <td>
                                <span class="d-block small"><?= h(trim(($veiculo['marca'] ?? '') . ' ' . ($veiculo['modelo'] ?? ''))) ?: '—' ?></span>
                                <small class="text-muted text-capitalize"><?= h($veiculo['tipo']) ?></small>
                            </td>
                            <td class="small"><?= h($veiculo['departamento'] ?? '—') ?></td>
                            <td><span class="badge rounded-pill <?= badgeStatus($veiculo['status']) ?>"><?= h($veiculo['status']) ?></span></td>
                            <td class="small text-muted text-nowrap"><?= number_format((int) $veiculo['quilometragemAtual'], 0, ',', '.') ?> km</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $veiculo['id_vei'] ?>">👁️ Ver</a>
                                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int) $veiculo['id_vei'] ?>">✏️ Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
