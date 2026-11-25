<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Relatório de viagens';
$pageSubtitle = 'Percursos, distâncias e motoristas.';
$activeMenu = 'relatorios';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
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
    $condicoes[] = 'p.data_per >= :de';
    $parametros[':de'] = $de;
}
if ($ate !== '') {
    $condicoes[] = 'p.data_per <= :ate';
    $parametros[':ate'] = $ate;
}
$where = $condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '';

$stmt = $pdo->prepare(
    'SELECT p.id_perc, p.data_per, p.origem, p.destino, p.quilometragem,
            v.placa, v.modelo, m.nome AS motorista
     FROM percurso p
     JOIN veiculo v ON v.id_vei = p.id_vei
     JOIN motorista m ON m.id_mot = p.id_mot' . $where . '
     ORDER BY p.data_per DESC, p.id_perc DESC'
);
$stmt->execute($parametros);
$viagens = $stmt->fetchAll();

$stmtVeiculos = $pdo->prepare(
    'SELECT v.placa, v.modelo, COUNT(p.id_perc) AS viagens,
            COALESCE(SUM(p.quilometragem), 0) AS km
     FROM percurso p
     JOIN veiculo v ON v.id_vei = p.id_vei' . $where . '
     GROUP BY v.id_vei, v.placa, v.modelo
     ORDER BY km DESC'
);
$stmtVeiculos->execute($parametros);
$porVeiculo = $stmtVeiculos->fetchAll();

$stmtMotoristas = $pdo->prepare(
    'SELECT m.nome, COUNT(p.id_perc) AS viagens,
            COALESCE(SUM(p.quilometragem), 0) AS km
     FROM percurso p
     JOIN motorista m ON m.id_mot = p.id_mot' . $where . '
     GROUP BY m.id_mot, m.nome
     ORDER BY km DESC'
);
$stmtMotoristas->execute($parametros);
$porMotorista = $stmtMotoristas->fetchAll();

$totalKm = 0;
foreach ($viagens as $linha) {
    $totalKm += (int) $linha['quilometragem'];
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
                <a class="btn btn-outline-secondary" href="viagens.php">Limpar</a>
            </div>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">Viagens realizadas</small>
            <strong class="fs-3 d-block my-1"><?= count($viagens) ?></strong>
        </div></div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">Quilômetros rodados</small>
            <strong class="fs-3 d-block my-1"><?= number_format($totalKm, 0, ',', '.') ?></strong>
        </div></div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">Média por viagem</small>
            <strong class="fs-3 d-block my-1"><?= $viagens ? number_format($totalKm / count($viagens), 0, ',', '.') . ' km' : '—' ?></strong>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white py-3"><h2 class="h6 mb-0">Por veículo</h2></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Veículo</th><th>Viagens</th><th>KM</th></tr></thead>
                    <tbody>
                        <?php if (!$porVeiculo): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">Sem dados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($porVeiculo as $linha): ?>
                                <tr>
                                    <td class="small"><strong class="d-block"><?= h($linha['placa']) ?></strong><small class="text-muted"><?= h((string) ($linha['modelo'] ?? '')) ?></small></td>
                                    <td class="small"><?= (int) $linha['viagens'] ?></td>
                                    <td class="small fw-semibold"><?= number_format((int) $linha['km'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white py-3"><h2 class="h6 mb-0">Por motorista</h2></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Motorista</th><th>Viagens</th><th>KM</th></tr></thead>
                    <tbody>
                        <?php if (!$porMotorista): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">Sem dados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($porMotorista as $linha): ?>
                                <tr>
                                    <td class="small"><?= h($linha['nome']) ?></td>
                                    <td class="small"><?= (int) $linha['viagens'] ?></td>
                                    <td class="small fw-semibold"><?= number_format((int) $linha['km'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3"><h2 class="h6 mb-0">Percursos no período</h2></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Data</th><th>Veículo</th><th>Motorista</th><th>Rota</th><th>KM</th></tr>
            </thead>
            <tbody>
                <?php if (!$viagens): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma viagem no período.</td></tr>
                <?php else: ?>
                    <?php foreach ($viagens as $linha): ?>
                        <tr>
                            <td class="small text-nowrap"><?= dataBr($linha['data_per']) ?></td>
                            <td class="small fw-semibold"><?= h($linha['placa']) ?></td>
                            <td class="small"><?= h($linha['motorista']) ?></td>
                            <td class="small text-muted text-truncate" style="max-width: 260px;"><?= h($linha['origem']) ?> → <?= h($linha['destino']) ?></td>
                            <td class="small"><?= number_format((int) $linha['quilometragem'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
