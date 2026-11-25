<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Consumo de combustível';
$pageSubtitle = 'Abastecimentos agrupados por veículo.';
$activeMenu = 'relatorios';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function dataBr(?string $valor): string
{
    return $valor ? date('d/m/Y', (int) strtotime($valor)) : '—';
}

$de = trim($_GET['de'] ?? '');
$ate = trim($_GET['ate'] ?? '');

$condicoes = [];
$parametros = [];
if ($de !== '') {
    $condicoes[] = 'a.data_aba >= :de';
    $parametros[':de'] = $de;
}
if ($ate !== '') {
    $condicoes[] = 'a.data_aba <= :ate';
    $parametros[':ate'] = $ate;
}
$where = $condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '';

$sql = 'SELECT v.id_vei, v.placa, v.modelo,
               COUNT(a.id_aba) AS abastecimentos,
               COALESCE(SUM(a.litros), 0) AS litros,
               COALESCE(SUM(a.valor), 0) AS valor,
               MAX(a.quilometragem) AS km
        FROM abastecimento a
        JOIN veiculo v ON v.id_vei = a.id_vei' . $where . '
        GROUP BY v.id_vei, v.placa, v.modelo
        ORDER BY litros DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$porVeiculo = $stmt->fetchAll();

$stmtMeses = $pdo->prepare(
    'SELECT DATE_FORMAT(a.data_aba, "%Y-%m") AS mes,
            COALESCE(SUM(a.litros), 0) AS litros,
            COALESCE(SUM(a.valor), 0) AS valor
     FROM abastecimento a' . $where . '
     GROUP BY DATE_FORMAT(a.data_aba, "%Y-%m")
     ORDER BY mes DESC
     LIMIT 12'
);
$stmtMeses->execute($parametros);
$porMes = $stmtMeses->fetchAll();

$totalLitros = 0.0;
$totalValor = 0.0;
$maxLitros = 0.0;
foreach ($porVeiculo as $linha) {
    $totalLitros += (float) $linha['litros'];
    $totalValor += (float) $linha['valor'];
    $maxLitros = max($maxLitros, (float) $linha['litros']);
}

require __DIR__ . '/../../includes/header.php';
?>

<form class="card border-0 shadow-sm mb-4" method="get">
    <div class="card-body p-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-lg-3">
                <label for="de" class="form-label fw-semibold small mb-1">De</label>
                <input type="date" class="form-control" id="de" name="de" value="<?= h($de) ?>">
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label for="ate" class="form-label fw-semibold small mb-1">Até</label>
                <input type="date" class="form-control" id="ate" name="ate" value="<?= h($ate) ?>">
            </div>
            <div class="col-12 col-lg-auto d-flex gap-2">
                <button type="submit" class="btn btn-brand"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
                <a class="btn btn-outline-secondary" href="consumo.php">Limpar</a>
            </div>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">Total de litros</small>
            <strong class="fs-3 d-block my-1"><?= number_format($totalLitros, 2, ',', '.') ?> L</strong>
        </div></div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">Valor total</small>
            <strong class="fs-3 d-block my-1"><?= moeda($totalValor) ?></strong>
        </div></div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">Preço médio por litro</small>
            <strong class="fs-3 d-block my-1"><?= $totalLitros > 0 ? moeda($totalValor / $totalLitros) : '—' ?></strong>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3"><h2 class="h6 mb-0">Por veículo</h2></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Veículo</th>
                    <th>Abastecimentos</th>
                    <th>Litros</th>
                    <th>Valor</th>
                    <th>Preço médio</th>
                    <th>KM registrado</th>
                    <th style="width: 20%;">Participação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$porVeiculo): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Nenhum abastecimento no período.</td></tr>
                <?php else: ?>
                    <?php foreach ($porVeiculo as $linha): ?>
                        <?php $litros = (float) $linha['litros']; ?>
                        <tr>
                            <td>
                                <strong class="small d-block"><?= h($linha['placa']) ?></strong>
                                <small class="text-muted"><?= h((string) ($linha['modelo'] ?? '')) ?></small>
                            </td>
                            <td class="small"><?= (int) $linha['abastecimentos'] ?></td>
                            <td class="small"><?= number_format($litros, 2, ',', '.') ?> L</td>
                            <td class="small"><?= moeda((float) $linha['valor']) ?></td>
                            <td class="small"><?= $litros > 0 ? moeda((float) $linha['valor'] / $litros) : '—' ?></td>
                            <td class="small text-muted"><?= number_format((int) $linha['km'], 0, ',', '.') ?> km</td>
                            <td>
                                <div class="progress" style="height: 8px;" role="progressbar" aria-label="Participação de <?= h($linha['placa']) ?>" aria-valuenow="<?= $maxLitros > 0 ? (int) round($litros / $maxLitros * 100) : 0 ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar" style="width: <?= $maxLitros > 0 ? round($litros / $maxLitros * 100, 1) : 0 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3"><h2 class="h6 mb-0">Últimos 12 meses</h2></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Mês</th><th>Litros</th><th>Valor</th></tr></thead>
            <tbody>
                <?php if (!$porMes): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">Sem dados no período.</td></tr>
                <?php else: ?>
                    <?php foreach ($porMes as $linha): ?>
                        <tr>
                            <td class="small fw-semibold"><?= date('m/Y', (int) strtotime($linha['mes'] . '-01')) ?></td>
                            <td class="small"><?= number_format((float) $linha['litros'], 2, ',', '.') ?> L</td>
                            <td class="small"><?= moeda((float) $linha['valor']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
