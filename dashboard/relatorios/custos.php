<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Custos da frota';
$pageSubtitle = 'Visão consolidada dos gastos por origem.';
$activeMenu = 'relatorios';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

$de = trim($_GET['de'] ?? '');
$ate = trim($_GET['ate'] ?? '');

function parametrosPeriodo(string $de, string $ate, string $coluna): array
{
    $condicoes = [];
    $parametros = [];
    if ($de !== '') {
        $condicoes[] = $coluna . ' >= :de';
        $parametros[':de'] = $de;
    }
    if ($ate !== '') {
        $condicoes[] = $coluna . ' <= :ate';
        $parametros[':ate'] = $ate;
    }
    return [$condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '', $parametros];
}

function somar(PDO $pdo, string $sql, array $parametros): float
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    return (float) $stmt->fetchColumn();
}

[$whereDespesa, $paramsDespesa] = parametrosPeriodo($de, $ate, 'data_despesa');
[$whereManutencao, $paramsManutencao] = parametrosPeriodo($de, $ate, 'data_man');
[$whereAbastecimento, $paramsAbastecimento] = parametrosPeriodo($de, $ate, 'data_aba');
[$whereMulta, $paramsMulta] = parametrosPeriodo($de, $ate, 'data_infra');

$porCategoria = [];
try {
    $stmt = $pdo->prepare(
        'SELECT categoria, COUNT(*) AS lancamentos, COALESCE(SUM(valor), 0) AS total
         FROM despesa' . $whereDespesa . '
         GROUP BY categoria
         ORDER BY total DESC'
    );
    $stmt->execute($paramsDespesa);
    $porCategoria = $stmt->fetchAll();
} catch (PDOException $exception) {
    
}

$totalDespesas = 0.0;
foreach ($porCategoria as $linha) {
    $totalDespesas += (float) $linha['total'];
}

$totalManutencao = somar($pdo, 'SELECT COALESCE(SUM(custo), 0) FROM manutencao' . $whereManutencao, $paramsManutencao);
$totalCombustivel = somar($pdo, 'SELECT COALESCE(SUM(valor), 0) FROM abastecimento' . $whereAbastecimento, $paramsAbastecimento);
$totalMultas = somar($pdo, 'SELECT COALESCE(SUM(valor_multa), 0) FROM multa' . $whereMulta, $paramsMulta);

$origens = [
    ['Combustível', $totalCombustivel, '<i class="fa-solid fa-gas-pump"></i>'],
    ['Manutenção', $totalManutencao, '<i class="fa-solid fa-wrench"></i>'],
    ['Despesas lançadas', $totalDespesas, '<i class="fa-solid fa-coins"></i>'],
    ['Multas', $totalMultas, '<i class="fa-solid fa-triangle-exclamation"></i>'],
];

$totalGeral = $totalCombustivel + $totalManutencao + $totalDespesas + $totalMultas;
$maxOrigem = max(1.0, ...array_column($origens, 1));

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
                <a class="btn btn-outline-secondary" href="custos.php">Limpar</a>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <small class="text-muted d-block">Custo total no período</small>
        <p class="display-6 fw-bolder lh-1 my-2"><?= moeda($totalGeral) ?></p>
        <div class="row g-3 mt-3">
            <?php foreach ($origens as [$label, $valor, $icone]): ?>
                <div class="col-6 col-lg-3">
                    <small class="text-muted d-block"><?= $icone ?> <?= h($label) ?></small>
                    <strong class="d-block"><?= moeda($valor) ?></strong>
                    <div class="progress mt-2" style="height: 6px;" role="progressbar" aria-label="<?= h($label) ?>" aria-valuenow="<?= $totalGeral > 0 ? (int) round($valor / $totalGeral * 100) : 0 ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: <?= round($valor / $maxOrigem * 100, 1) ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3"><h2 class="h6 mb-0">Despesas por categoria</h2></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Categoria</th><th>Lançamentos</th><th>Total</th><th style="width: 30%;">Participação</th></tr></thead>
            <tbody>
                <?php if (!$porCategoria): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Nenhuma despesa lançada no período.</td></tr>
                <?php else: ?>
                    <?php foreach ($porCategoria as $linha): ?>
                        <?php $valor = (float) $linha['total']; ?>
                        <tr>
                            <td class="text-capitalize fw-semibold small"><?= h(str_replace('_', ' ', $linha['categoria'])) ?></td>
                            <td class="small"><?= (int) $linha['lancamentos'] ?></td>
                            <td class="small"><?= moeda($valor) ?></td>
                            <td>
                                <div class="progress" style="height: 8px;" role="progressbar" aria-label="Participação de <?= h($linha['categoria']) ?>" aria-valuenow="<?= $totalDespesas > 0 ? (int) round($valor / $totalDespesas * 100) : 0 ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-info" style="width: <?= $totalDespesas > 0 ? round($valor / $totalDespesas * 100, 1) : 0 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
