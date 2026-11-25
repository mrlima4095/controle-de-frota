<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Relatório de manutenções';
$pageSubtitle = 'Serviços realizados e agendamentos futuros.';
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
    $condicoes[] = 'm.data_man >= :de';
    $parametros[':de'] = $de;
}
if ($ate !== '') {
    $condicoes[] = 'm.data_man <= :ate';
    $parametros[':ate'] = $ate;
}
$where = $condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '';

$stmt = $pdo->prepare(
    'SELECT m.id_man, m.tipo, m.data_man, m.oficina, m.servico, m.custo,
            m.quilometragem, m.proxima_data, v.placa, v.modelo
     FROM manutencao m
     JOIN veiculo v ON v.id_vei = m.id_vei' . $where . '
     ORDER BY m.data_man DESC'
);
$stmt->execute($parametros);
$manutencoes = $stmt->fetchAll();

$stmtTipos = $pdo->prepare(
    'SELECT m.tipo, COUNT(*) AS quantidade, COALESCE(SUM(m.custo), 0) AS total
     FROM manutencao m' . $where . '
     GROUP BY m.tipo
     ORDER BY total DESC'
);
$stmtTipos->execute($parametros);
$porTipo = $stmtTipos->fetchAll();

$totalCusto = 0.0;
foreach ($manutencoes as $linha) {
    $totalCusto += (float) $linha['custo'];
}

$proximas = $pdo->query(
    'SELECT m.proxima_data, m.tipo, v.placa, v.modelo
     FROM manutencao m
     JOIN veiculo v ON v.id_vei = m.id_vei
     WHERE m.proxima_data IS NOT NULL
     ORDER BY m.proxima_data ASC
     LIMIT 10'
)->fetchAll();

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
                <a class="btn btn-outline-secondary" href="manutencoes.php">Limpar</a>
            </div>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">Serviços no período</small>
            <strong class="fs-3 d-block my-1"><?= count($manutencoes) ?></strong>
        </div></div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">Custo total</small>
            <strong class="fs-3 d-block my-1"><?= moeda($totalCusto) ?></strong>
        </div></div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">Ticket médio</small>
            <strong class="fs-3 d-block my-1"><?= $manutencoes ? moeda($totalCusto / count($manutencoes)) : '—' ?></strong>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3"><h2 class="h6 mb-0">Serviços realizados</h2></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Veículo</th><th>Tipo</th><th>Oficina</th><th>Custo</th><th>Próxima</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$manutencoes): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma manutenção no período.</td></tr>
                        <?php else: ?>
                            <?php foreach ($manutencoes as $linha): ?>
                                <tr>
                                    <td class="small text-nowrap"><?= dataBr($linha['data_man']) ?></td>
                                    <td>
                                        <strong class="small d-block"><?= h($linha['placa']) ?></strong>
                                        <small class="text-muted"><?= h((string) ($linha['modelo'] ?? '')) ?></small>
                                    </td>
                                    <td class="small text-capitalize"><?= h($linha['tipo']) ?></td>
                                    <td class="small text-muted"><?= h((string) ($linha['oficina'] ?? '')) ?: '—' ?></td>
                                    <td class="small fw-semibold"><?= moeda((float) $linha['custo']) ?></td>
                                    <td class="small text-muted text-nowrap"><?= dataBr($linha['proxima_data']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-3"><h2 class="h6 mb-0">Por tipo</h2></div>
            <div class="list-group list-group-flush">
                <?php if (!$porTipo): ?>
                    <p class="list-group-item text-muted small text-center py-4 mb-0">Sem dados.</p>
                <?php else: ?>
                    <?php foreach ($porTipo as $linha): ?>
                        <div class="list-group-item d-flex justify-content-between gap-2">
                            <span class="small text-capitalize"><?= h($linha['tipo']) ?> <small class="text-muted">(<?= (int) $linha['quantidade'] ?>)</small></span>
                            <strong class="small"><?= moeda((float) $linha['total']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3"><h2 class="h6 mb-0">Próximas manutenções</h2></div>
            <div class="list-group list-group-flush">
                <?php if (!$proximas): ?>
                    <p class="list-group-item text-muted small text-center py-4 mb-0">Nenhuma agendada.</p>
                <?php else: ?>
                    <?php foreach ($proximas as $linha): ?>
                        <div class="list-group-item d-flex justify-content-between gap-2">
                            <span class="small"><strong><?= h($linha['placa']) ?></strong><br><small class="text-muted text-capitalize"><?= h($linha['tipo']) ?></small></span>
                            <span class="badge rounded-pill text-bg-primary align-self-start flex-shrink-0"><?= dataBr($linha['proxima_data']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
