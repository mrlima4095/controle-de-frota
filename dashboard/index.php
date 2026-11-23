<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Visão geral';
$pageSubtitle = 'Um panorama da operação da sua frota.';
$activeMenu = 'visao';

// --- KPIs (contagens simples) ---
function contar(PDO $pdo, string $sql): int
{
    return (int) $pdo->query($sql)->fetchColumn();
}

$totalVeiculos = contar($pdo, 'SELECT COUNT(*) FROM veiculo WHERE ativo = TRUE');
$totalMotoristas = contar($pdo, 'SELECT COUNT(*) FROM motorista WHERE ativo = TRUE');
$veiculosEmUso = contar($pdo, "SELECT COUNT(*) FROM veiculo WHERE ativo = TRUE AND status = 'em_uso'");
$veiculosManutencao = contar($pdo, "SELECT COUNT(*) FROM veiculo WHERE ativo = TRUE AND status = 'manutencao'");
$totalViagens = contar($pdo, 'SELECT COUNT(*) FROM percurso');
$abastecimentosMes = contar($pdo, 'SELECT COUNT(*) FROM abastecimento WHERE YEAR(data_aba) = YEAR(CURDATE()) AND MONTH(data_aba) = MONTH(CURDATE())');
$multasPendentes = contar($pdo, "SELECT COUNT(*) FROM multa WHERE status = 'pendente'");
$manutencoesMes = contar($pdo, "SELECT COUNT(*) FROM manutencao WHERE YEAR(data_man) = YEAR(CURDATE()) AND MONTH(data_man) = MONTH(CURDATE())");

// --- Listas de apoio ---
$proximasManutencoes = $pdo->query(
    'SELECT m.proxima_data, m.tipo, v.placa, v.modelo
     FROM manutencao m
     JOIN veiculo v ON v.id_vei = m.id_vei
     WHERE m.proxima_data IS NOT NULL AND m.proxima_data >= CURDATE()
     ORDER BY m.proxima_data ASC
     LIMIT 5'
)->fetchAll();

$multasRecentes = $pdo->query(
    "SELECT mu.data_infra, mu.infracao, mu.valor_multa, v.placa
     FROM multa mu
     JOIN veiculo v ON v.id_vei = mu.id_vei
     WHERE mu.status = 'pendente'
     ORDER BY mu.data_infra DESC
     LIMIT 5"
)->fetchAll();

$cnhsVencendo = $pdo->query(
    'SELECT nome, validade_cnh
     FROM motorista
     WHERE ativo = TRUE AND validade_cnh IS NOT NULL
       AND validade_cnh <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
     ORDER BY validade_cnh ASC
     LIMIT 5'
)->fetchAll();

function dataBr(?string $data): string
{
    if (!$data) {
        return '—';
    }
    return date('d/m/Y', (int) strtotime($data));
}

function moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

$kpis = [
    ['Veículos', $totalVeiculos, $veiculosEmUso . ' em uso · ' . $veiculosManutencao . ' em manutenção', '🚗', ''],
    ['Motoristas', $totalMotoristas, 'Condutores ativos', '🧑‍✈️', ''],
    ['Viagens', $totalViagens, 'Percursos registrados', '🛣️', ''],
    ['Abastecimentos no mês', $abastecimentosMes, 'Registros do mês', '⛽', ''],
    ['Multas pendentes', $multasPendentes, 'Requerem atenção', '⚠️', $multasPendentes > 0 ? 'text-danger fw-bold' : ''],
    ['Manutenções no mês', $manutencoesMes, 'Serviços registrados', '🔧', ''],
];

require __DIR__ . '/../includes/header.php';
?>

<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3 mb-4">
    <?php foreach ($kpis as [$label, $valor, $dica, $icone, $classeDica]): ?>
        <div class="col">
            <article class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <span class="text-muted small fw-semibold"><?= htmlspecialchars($label) ?></span>
                        <span class="kpi-icon" aria-hidden="true"><?= $icone ?></span>
                    </div>
                    <p class="display-6 fw-bolder lh-1 my-3"><?= $valor ?></p>
                    <p class="small mb-0 <?= $classeDica ?>"><?= htmlspecialchars($dica) ?></p>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-6">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white d-flex align-items-center justify-content-between gap-2 py-3">
                <h2 class="h6 mb-0">Próximas manutenções</h2>
                <a class="text-brand small fw-bold text-nowrap" href="/dashboard/manutencao/index.php">Ver todas</a>
            </header>
            <div class="list-group list-group-flush">
                <?php if (!$proximasManutencoes): ?>
                    <p class="list-group-item text-muted small text-center py-4 mb-0">Nenhuma manutenção agendada.</p>
                <?php else: ?>
                    <?php foreach ($proximasManutencoes as $m): ?>
                        <div class="list-group-item d-flex align-items-center justify-content-between gap-3">
                            <div class="min-w-0">
                                <strong class="d-block text-truncate"><?= htmlspecialchars($m['placa']) ?> · <?= htmlspecialchars($m['modelo'] ?? '') ?></strong>
                                <small class="text-muted"><?= htmlspecialchars(ucfirst($m['tipo'])) ?></small>
                            </div>
                            <span class="badge text-bg-primary rounded-pill flex-shrink-0"><?= dataBr($m['proxima_data']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="col-12 col-xl-6">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white d-flex align-items-center justify-content-between gap-2 py-3">
                <h2 class="h6 mb-0">Multas pendentes</h2>
                <a class="text-brand small fw-bold text-nowrap" href="/dashboard/sinistros/index.php">Ver todas</a>
            </header>
            <div class="list-group list-group-flush">
                <?php if (!$multasRecentes): ?>
                    <p class="list-group-item text-muted small text-center py-4 mb-0">Nenhuma multa pendente. Tudo em dia!</p>
                <?php else: ?>
                    <?php foreach ($multasRecentes as $mu): ?>
                        <div class="list-group-item d-flex align-items-center justify-content-between gap-3">
                            <div class="min-w-0">
                                <strong class="d-block text-truncate"><?= htmlspecialchars($mu['placa']) ?> · <?= htmlspecialchars($mu['infracao']) ?></strong>
                                <small class="text-muted"><?= dataBr($mu['data_infra']) ?></small>
                            </div>
                            <span class="badge text-bg-danger rounded-pill flex-shrink-0"><?= moeda((float) $mu['valor_multa']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="col-12 col-xl-6">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white d-flex align-items-center justify-content-between gap-2 py-3">
                <h2 class="h6 mb-0">CNHs a vencer (60 dias)</h2>
                <a class="text-brand small fw-bold text-nowrap" href="/dashboard/motoristas/index.php">Ver motoristas</a>
            </header>
            <div class="list-group list-group-flush">
                <?php if (!$cnhsVencendo): ?>
                    <p class="list-group-item text-muted small text-center py-4 mb-0">Nenhuma CNH vencendo nos próximos 60 dias.</p>
                <?php else: ?>
                    <?php foreach ($cnhsVencendo as $m): ?>
                        <div class="list-group-item d-flex align-items-center justify-content-between gap-3">
                            <div class="min-w-0">
                                <strong class="d-block text-truncate"><?= htmlspecialchars($m['nome']) ?></strong>
                                <small class="text-muted">Validade da CNH</small>
                            </div>
                            <span class="badge text-bg-warning rounded-pill flex-shrink-0"><?= dataBr($m['validade_cnh']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="col-12 col-xl-6">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3">
                <h2 class="h6 mb-0">Acesso rápido</h2>
            </header>
            <div class="card-body">
                <div class="row row-cols-1 row-cols-sm-2 g-2">
                    <div class="col"><a class="quick-link" href="/dashboard/veiculos/create.php"><span class="menu-icon" aria-hidden="true">➕</span> Novo veículo</a></div>
                    <div class="col"><a class="quick-link" href="/dashboard/motoristas/create.php"><span class="menu-icon" aria-hidden="true">➕</span> Novo motorista</a></div>
                    <div class="col"><a class="quick-link" href="/dashboard/abastecimentos/create.php"><span class="menu-icon" aria-hidden="true">⛽</span> Abastecer</a></div>
                    <div class="col"><a class="quick-link" href="/dashboard/manutencao/create.php"><span class="menu-icon" aria-hidden="true">🔧</span> Manutenção</a></div>
                    <div class="col"><a class="quick-link" href="/dashboard/viagens/index.php"><span class="menu-icon" aria-hidden="true">🛣️</span> Viagens</a></div>
                    <div class="col"><a class="quick-link" href="/dashboard/alertas/index.php"><span class="menu-icon" aria-hidden="true">🔔</span> Alertas</a></div>
                    <div class="col"><a class="quick-link" href="/dashboard/relatorios/index.php"><span class="menu-icon" aria-hidden="true">📈</span> Relatórios</a></div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
