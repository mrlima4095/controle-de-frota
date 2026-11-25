<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Relatórios';
$pageSubtitle = 'Indicadores e demonstrativos da operação.';
$activeMenu = 'relatorios';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

$totalAbastecimentos = (int) $pdo->query('SELECT COUNT(*) FROM abastecimento')->fetchColumn();
$gastoCombustivel = (float) $pdo->query('SELECT COALESCE(SUM(valor), 0) FROM abastecimento')->fetchColumn();
$gastoManutencao = (float) $pdo->query('SELECT COALESCE(SUM(custo), 0) FROM manutencao')->fetchColumn();
$gastoDespesas = 0.0;
try {
    $gastoDespesas = (float) $pdo->query('SELECT COALESCE(SUM(valor), 0) FROM despesa')->fetchColumn();
} catch (PDOException $exception) {
    
}
$totalQuilometros = (int) $pdo->query('SELECT COALESCE(SUM(quilometragem), 0) FROM percurso')->fetchColumn();

$relatorios = [
    ['consumo', 'Consumo de combustível', 'Litros, valor e preço médio por litro de cada veículo.', '<i class="fa-solid fa-gas-pump"></i>'],
    ['custos', 'Custos da frota', 'Despesas, manutenções, combustível e multas por categoria.', '<i class="fa-solid fa-coins"></i>'],
    ['manutencoes', 'Manutenções', 'Serviços realizados, custos e próximas manutenções.', '<i class="fa-solid fa-wrench"></i>'],
    ['viagens', 'Viagens', 'Percursos por veículo e motorista, com total de quilômetros.', '<i class="fa-solid fa-road"></i>'],
];

require __DIR__ . '/../../includes/header.php';
?>

<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
    <div class="col">
        <article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-gas-pump"></i> Abastecimentos</small>
            <strong class="fs-3 d-block my-2"><?= $totalAbastecimentos ?></strong>
            <span class="small text-muted"><?= moeda($gastoCombustivel) ?> em combustível</span>
        </div></article>
    </div>
    <div class="col">
        <article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-wrench"></i> Manutenções</small>
            <strong class="fs-3 d-block my-2"><?= moeda($gastoManutencao) ?></strong>
            <span class="small text-muted">Total em serviços</span>
        </div></article>
    </div>
    <div class="col">
        <article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-coins"></i> Despesas</small>
            <strong class="fs-3 d-block my-2"><?= moeda($gastoDespesas) ?></strong>
            <span class="small text-muted">Total lançado</span>
        </div></article>
    </div>
    <div class="col">
        <article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-road"></i> Quilômetros rodados</small>
            <strong class="fs-3 d-block my-2"><?= number_format($totalQuilometros, 0, ',', '.') ?></strong>
            <span class="small text-muted">Soma dos percursos</span>
        </div></article>
    </div>
</div>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
    <div>
        <h2 class="h5 mb-1">Relatórios disponíveis</h2>
        <p class="text-muted small mb-0">Use os filtros de período em cada relatório.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/dashboard/exportar.php"><i class="fa-solid fa-download"></i> Exportar veículos (CSV)</a>
</div>

<div class="row g-3">
    <?php foreach ($relatorios as [$slug, $titulo, $descricao, $icone]): ?>
        <div class="col-12 col-md-6 col-xl-3">
            <a class="card h-100 border-0 shadow-sm text-decoration-none text-reset" href="<?= h($slug) ?>.php">
                <div class="card-body">
                    <span class="kpi-icon mb-3 d-inline-block" aria-hidden="true"><?= $icone ?></span>
                    <h3 class="h6 fw-bold"><?= h($titulo) ?></h3>
                    <p class="small text-muted mb-0"><?= h($descricao) ?></p>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
