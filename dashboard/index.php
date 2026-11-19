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
$totalClientes = contar($pdo, 'SELECT COUNT(*) FROM cliente WHERE ativo = TRUE');
$veiculosEmUso = contar($pdo, "SELECT COUNT(*) FROM veiculo WHERE ativo = TRUE AND status = 'em_uso'");
$veiculosManutencao = contar($pdo, "SELECT COUNT(*) FROM veiculo WHERE ativo = TRUE AND status = 'manutencao'");
$reservasPendentes = contar($pdo, "SELECT COUNT(*) FROM reserva WHERE status = 'pendente'");
$multasPendentes = contar($pdo, "SELECT COUNT(*) FROM multa WHERE status = 'pendente'");
$manutencoesMes = contar($pdo, "SELECT COUNT(*) FROM manutencao WHERE YEAR(data_man) = YEAR(CURDATE()) AND MONTH(data_man) = MONTH(CURDATE())");

// --- Listas de apoio ---
$proximasManutencoes = $pdo->query(
    'SELECT m.data_man, m.tipo, v.placa, v.modelo
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
    return date('d/m/Y', strtotime($data));
}

function moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

require __DIR__ . '/../includes/header.php';
?>

<div class="grid-kpi">
    <article class="kpi">
        <div class="kpi-label">Veículos <span class="kpi-icon">▤</span></div>
        <p class="kpi-value"><?= $totalVeiculos ?></p>
        <p class="kpi-hint"><?= $veiculosEmUso ?> em uso · <?= $veiculosManutencao ?> em manutenção</p>
    </article>

    <article class="kpi">
        <div class="kpi-label">Motoristas <span class="kpi-icon">◍</span></div>
        <p class="kpi-value"><?= $totalMotoristas ?></p>
        <p class="kpi-hint">Condutores ativos</p>
    </article>

    <article class="kpi">
        <div class="kpi-label">Reservas pendentes <span class="kpi-icon">➔</span></div>
        <p class="kpi-value"><?= $reservasPendentes ?></p>
        <p class="kpi-hint <?= $reservasPendentes > 0 ? 'is-green' : '' ?>">Aguardando confirmação</p>
    </article>

    <article class="kpi">
        <div class="kpi-label">Multas pendentes <span class="kpi-icon">⚠</span></div>
        <p class="kpi-value"><?= $multasPendentes ?></p>
        <p class="kpi-hint <?= $multasPendentes > 0 ? 'is-red' : '' ?>">Requerem atenção</p>
    </article>

    <article class="kpi">
        <div class="kpi-label">Manutenções no mês <span class="kpi-icon">⚙</span></div>
        <p class="kpi-value"><?= $manutencoesMes ?></p>
        <p class="kpi-hint">Serviços registrados</p>
    </article>

    <article class="kpi">
        <div class="kpi-label">Clientes <span class="kpi-icon">⌂</span></div>
        <p class="kpi-value"><?= $totalClientes ?></p>
        <p class="kpi-hint">Cadastros ativos</p>
    </article>
</div>

<div class="grid-paineis">
    <section class="painel">
        <header class="painel-head">
            <h2>Próximas manutenções</h2>
            <a href="/dashboard/manutencao/index.php">Ver todas</a>
        </header>
        <div class="painel-body">
            <?php if (!$proximasManutencoes): ?>
                <p class="lista-vazia">Nenhuma manutenção agendada.</p>
            <?php else: ?>
                <?php foreach ($proximasManutencoes as $m): ?>
                    <div class="lista-item">
                        <div>
                            <strong><?= htmlspecialchars($m['placa']) ?> · <?= htmlspecialchars($m['modelo'] ?? '') ?></strong>
                            <small><?= htmlspecialchars(ucfirst($m['tipo'])) ?></small>
                        </div>
                        <span class="badge is-blue"><?= dataBr($m['data_man']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="painel">
        <header class="painel-head">
            <h2>Multas pendentes</h2>
            <a href="/dashboard/sinistros/index.php">Ver todas</a>
        </header>
        <div class="painel-body">
            <?php if (!$multasRecentes): ?>
                <p class="lista-vazia">Nenhuma multa pendente. Tudo em dia!</p>
            <?php else: ?>
                <?php foreach ($multasRecentes as $mu): ?>
                    <div class="lista-item">
                        <div>
                            <strong><?= htmlspecialchars($mu['placa']) ?> · <?= htmlspecialchars($mu['infracao']) ?></strong>
                            <small><?= dataBr($mu['data_infra']) ?></small>
                        </div>
                        <span class="badge is-red"><?= moeda((float) $mu['valor_multa']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="painel">
        <header class="painel-head">
            <h2>CNHs a vencer (60 dias)</h2>
            <a href="/dashboard/motoristas/index.php">Ver motoristas</a>
        </header>
        <div class="painel-body">
            <?php if (!$cnhsVencendo): ?>
                <p class="lista-vazia">Nenhuma CNH vencendo nos próximos 60 dias.</p>
            <?php else: ?>
                <?php foreach ($cnhsVencendo as $m): ?>
                    <div class="lista-item">
                        <div>
                            <strong><?= htmlspecialchars($m['nome']) ?></strong>
                            <small>Validade da CNH</small>
                        </div>
                        <span class="badge is-orange"><?= dataBr($m['validade_cnh']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="painel">
        <header class="painel-head">
            <h2>Acesso rápido</h2>
        </header>
        <div class="painel-body">
            <div class="quick-links">
                <a class="quick-link" href="/dashboard/veiculos/create.php"><span class="menu-icon">＋</span> Novo veículo</a>
                <a class="quick-link" href="/dashboard/motoristas/create.php"><span class="menu-icon">＋</span> Novo motorista</a>
                <a class="quick-link" href="/dashboard/viagens/index.php"><span class="menu-icon">➔</span> Viagens</a>
                <a class="quick-link" href="/dashboard/relatorios/index.php"><span class="menu-icon">▥</span> Relatórios</a>
            </div>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
