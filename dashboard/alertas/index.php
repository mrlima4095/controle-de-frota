<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Alertas';
$pageSubtitle = 'Pendências que precisam de atenção.';
$activeMenu = 'alertas';

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


function consultaSegura(PDO $pdo, string $sql): array
{
    try {
        return $pdo->query($sql)->fetchAll();
    } catch (PDOException $exception) {
        return [];
    }
}

$cnhs = consultaSegura($pdo,
    'SELECT nome, validade_cnh FROM motorista
     WHERE ativo = TRUE AND validade_cnh IS NOT NULL
       AND validade_cnh <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
     ORDER BY validade_cnh ASC LIMIT 20'
);

$manutencoes = consultaSegura($pdo,
    'SELECT m.proxima_data, m.tipo, v.placa
     FROM manutencao m
     JOIN veiculo v ON v.id_vei = m.id_vei
     WHERE m.proxima_data IS NOT NULL
       AND m.proxima_data <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY m.proxima_data ASC LIMIT 20'
);

$multas = consultaSegura($pdo,
    "SELECT mu.id_mul, mu.data_infra, mu.infracao, mu.valor_multa, v.placa
     FROM multa mu
     JOIN veiculo v ON v.id_vei = mu.id_vei
     WHERE mu.status = 'pendente'
     ORDER BY mu.data_infra DESC LIMIT 20"
);

$documentos = consultaSegura($pdo,
    'SELECT d.id_doc, d.tipo, d.data_validade, v.placa
     FROM documento d
     JOIN veiculo v ON v.id_vei = d.id_vei
     WHERE d.data_validade IS NOT NULL
       AND d.data_validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY d.data_validade ASC LIMIT 20'
);

$pneus = consultaSegura($pdo,
    'SELECT p.id_pneu, p.posicao, p.km_instalacao, p.km_atual, p.vida_util_km, v.placa
     FROM pneu p
     JOIN veiculo v ON v.id_vei = p.id_vei
     WHERE p.status = \'em_uso\' AND p.vida_util_km > 0
       AND (p.km_atual - p.km_instalacao) >= (p.vida_util_km * 0.8)
     ORDER BY p.km_atual DESC LIMIT 20'
);

$totalAlertas = count($cnhs) + count($manutencoes) + count($multas) + count($documentos) + count($pneus);

$grupos = [
    ['CNHs a vencer', '<i class="fa-solid fa-user"></i>', $cnhs, '/dashboard/motoristas/index.php', 'warning', function (array $item): array {
        return [$item['nome'], 'Validade: ' . dataBr($item['validade_cnh'])];
    }],
    ['Manutenções próximas', '<i class="fa-solid fa-wrench"></i>', $manutencoes, '/dashboard/manutencao/index.php', 'primary', function (array $item): array {
        return [$item['placa'] . ' · ' . ucfirst($item['tipo']), 'Prevista: ' . dataBr($item['proxima_data'])];
    }],
    ['Multas pendentes', '<i class="fa-solid fa-triangle-exclamation"></i>', $multas, '/dashboard/sinistros/index.php', 'danger', function (array $item): array {
        return [$item['placa'] . ' · ' . $item['infracao'], dataBr($item['data_infra']) . ' · ' . moeda((float) $item['valor_multa'])];
    }],
    ['Documentos a vencer', '<i class="fa-solid fa-file-lines"></i>', $documentos, '/dashboard/documentos/index.php', 'info', function (array $item): array {
        return [$item['placa'] . ' · ' . ucfirst($item['tipo']), 'Validade: ' . dataBr($item['data_validade'])];
    }],
    ['Pneus desgastados', '<i class="fa-solid fa-circle-dot"></i>', $pneus, '/dashboard/pneus/index.php', 'warning', function (array $item): array {
        return [$item['placa'] . ' · ' . str_replace('_', ' ', $item['posicao']), number_format((int) $item['km_atual'], 0, ',', '.') . ' km'];
    }],
];

require __DIR__ . '/../../includes/header.php';
?>

<div class="alert <?= $totalAlertas > 0 ? 'alert-warning' : 'alert-success' ?> d-flex align-items-center gap-2" role="alert">
    <span aria-hidden="true"><?= $totalAlertas > 0 ? '<i class="fa-solid fa-bell"></i>' : '<i class="fa-solid fa-check"></i>' ?></span>
    <span>
        <?php if ($totalAlertas > 0): ?>
            <strong><?= $totalAlertas ?> item(ns)</strong> precisam de atenção neste momento.
        <?php else: ?>
            Nenhum alerta no momento. Tudo em dia!
        <?php endif; ?>
    </span>
</div>

<div class="row g-3">
    <?php foreach ($grupos as [$titulo, $icone, $itens, $link, $cor, $formatar]): ?>
        <div class="col-12 col-xl-6">
            <section class="card h-100 border-0 shadow-sm">
                <header class="card-header bg-white py-3 d-flex align-items-center justify-content-between gap-2">
                    <h2 class="h6 mb-0"><?= $icone ?> <?= h($titulo) ?> <span class="badge rounded-pill text-bg-<?= $cor ?>"><?= count($itens) ?></span></h2>
                    <a class="text-brand small fw-bold text-nowrap" href="<?= $link ?>">Ver todos</a>
                </header>
                <div class="list-group list-group-flush">
                    <?php if (!$itens): ?>
                        <p class="list-group-item text-muted small text-center py-4 mb-0">Nada pendente por aqui.</p>
                    <?php else: ?>
                        <?php foreach ($itens as $item): ?>
                            <?php [$principal, $secundario] = $formatar($item); ?>
                            <div class="list-group-item d-flex align-items-center justify-content-between gap-3">
                                <div class="min-w-0">
                                    <strong class="d-block text-truncate small"><?= h($principal) ?></strong>
                                    <small class="text-muted"><?= h($secundario) ?></small>
                                </div>
                                <span class="badge rounded-pill text-bg-<?= $cor ?> flex-shrink-0">!</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
