<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Pneus';
$pageSubtitle = 'Controle dos pneus, posições e desgaste da frota.';
$activeMenu = 'pneus';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function badgeStatus(string $status): string
{
    return match ($status) {
        'em_uso' => 'text-bg-success',
        'reserva' => 'text-bg-primary',
        'desgastado' => 'text-bg-warning',
        'descartado' => 'text-bg-secondary',
        default => 'text-bg-secondary',
    };
}

function desgastePercentual(array $pneu): ?float
{
    $vidaUtil = (int) ($pneu['vida_util_km'] ?? 0);
    if ($vidaUtil <= 0) {
        return null;
    }

    $rodados = max(0, (int) ($pneu['km_atual'] ?? 0) - (int) ($pneu['km_instalacao'] ?? 0));
    return min(100.0, $rodados / $vidaUtil * 100);
}

function badgeDesgaste(float $percentual): string
{
    return match (true) {
        $percentual > 80 => 'text-bg-danger',
        $percentual > 60 => 'text-bg-warning',
        default => 'text-bg-success',
    };
}

$busca = trim((string) ($_GET['q'] ?? ''));

$sql = 'SELECT p.*, v.placa
        FROM pneu p
        INNER JOIN veiculo v ON v.id_vei = p.id_vei';
$parametros = [];

if ($busca !== '') {
    $sql .= ' WHERE v.placa LIKE :q1 OR p.marca LIKE :q2 OR p.modelo LIKE :q3 OR p.numero_serie LIKE :q4';
    $parametros[':q1'] = '%' . $busca . '%';
    $parametros[':q2'] = '%' . $busca . '%';
    $parametros[':q3'] = '%' . $busca . '%';
    $parametros[':q4'] = '%' . $busca . '%';
}

$sql .= ' ORDER BY v.placa ASC, p.posicao ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$pneus = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Pneu salvo com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
    <div>
        <h2 class="h5 mb-1">Pneus cadastrados</h2>
        <p class="text-muted small mb-0"><?= count($pneus) ?> pneu(s) encontrado(s).</p>
    </div>
    <a class="btn btn-brand" href="create.php"><i class="fa-solid fa-plus"></i> Novo pneu</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-sm-8 col-lg-6">
        <input type="search" class="form-control" name="q" value="<?= h($busca) ?>" placeholder="Buscar por placa, marca, modelo ou nº de série">
    </div>
    <div class="col-12 col-sm-auto">
        <button type="submit" class="btn btn-outline-secondary w-100"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">Veículo</th>
                    <th scope="col">Posição</th>
                    <th scope="col">Pneu</th>
                    <th scope="col">Nº série</th>
                    <th scope="col">KM atual</th>
                    <th scope="col">Desgaste</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$pneus): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Nenhum pneu encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($pneus as $pneu): ?>
                        <?php $percentual = desgastePercentual($pneu); ?>
                        <tr>
                            <td><strong><?= h((string) $pneu['placa']) ?></strong></td>
                            <td class="small text-muted text-capitalize"><?= h(ucfirst(str_replace('_', ' ', (string) ($pneu['posicao'] ?? 'outro')))) ?></td>
                            <td>
                                <span class="d-block small"><?= h(trim((string) ($pneu['marca'] ?? '') . ' ' . (string) ($pneu['modelo'] ?? ''))) ?: '—' ?></span>
                                <small class="text-muted"><?= h((string) ($pneu['dimensao'] ?? '')) ?: '—' ?></small>
                            </td>
                            <td class="small text-muted"><?= h((string) ($pneu['numero_serie'] ?? '')) ?: '—' ?></td>
                            <td class="small text-muted text-nowrap"><?= number_format((int) ($pneu['km_atual'] ?? 0), 0, ',', '.') ?> km</td>
                            <td>
                                <?php if ($percentual === null): ?>
                                    <span class="text-muted small">—</span>
                                <?php else: ?>
                                    <span class="badge rounded-pill <?= badgeDesgaste($percentual) ?>"><?= number_format($percentual, 0, ',', '.') ?>%</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge rounded-pill <?= badgeStatus((string) ($pneu['status'] ?? '')) ?>"><?= h(ucfirst(str_replace('_', ' ', (string) ($pneu['status'] ?? 'outro')))) ?></span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $pneu['id_pneu'] ?>"><i class="fa-solid fa-eye"></i> Ver</a>
                                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int) $pneu['id_pneu'] ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
