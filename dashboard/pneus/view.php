<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Detalhes do pneu';
$pageSubtitle = 'Informações completas do pneu e de seu desgaste.';
$activeMenu = 'pneus';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function dataBr(?string $valor): string
{
    return $valor ? date('d/m/Y', (int) strtotime($valor)) : '—';
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT p.*, v.placa, v.modelo AS veiculo_modelo
     FROM pneu p
     INNER JOIN veiculo v ON v.id_vei = p.id_vei
     WHERE p.id_pneu = :id LIMIT 1'
);
$stmt->execute([':id' => $id]);
$pneu = $stmt->fetch();

if (!$pneu) {
    header('Location: /dashboard/pneus/index.php');
    exit;
}

$badgeStatus = match ((string) ($pneu['status'] ?? '')) {
    'em_uso' => 'text-bg-success',
    'reserva' => 'text-bg-primary',
    'desgastado' => 'text-bg-warning',
    'descartado' => 'text-bg-secondary',
    default => 'text-bg-secondary',
};

$kmInstalacao = (int) ($pneu['km_instalacao'] ?? 0);
$kmAtual = (int) ($pneu['km_atual'] ?? 0);
$vidaUtil = (int) ($pneu['vida_util_km'] ?? 0);
$kmRodados = max(0, $kmAtual - $kmInstalacao);
$percentual = $vidaUtil > 0 ? min(100.0, $kmRodados / $vidaUtil * 100) : null;

$badgeDesgaste = $percentual === null
    ? 'text-bg-secondary'
    : match (true) {
        $percentual > 80 => 'text-bg-danger',
        $percentual > 60 => 'text-bg-warning',
        default => 'text-bg-success',
    };

$posicao = ucfirst(str_replace('_', ' ', (string) ($pneu['posicao'] ?? 'outro')));
$status = ucfirst(str_replace('_', ' ', (string) ($pneu['status'] ?? 'outro')));

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Pneu atualizado com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 mb-1">🛞 Pneu #<?= $id ?> <span class="badge rounded-pill <?= $badgeStatus ?>"><?= h($status) ?></span></h2>
        <p class="text-muted small mb-0"><?= h($pneu['placa']) ?> · <?= h($posicao) ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="index.php">← Voltar</a>
        <a class="btn btn-brand" href="edit.php?id=<?= $id ?>">✏️ Editar</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">🛣️ KM rodados</small>
            <strong class="fs-4"><?= number_format($kmRodados, 0, ',', '.') ?></strong>
            <small class="text-muted">desde a instalação</small>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">📏 Vida útil</small>
            <strong class="fs-4"><?= $vidaUtil > 0 ? number_format($vidaUtil, 0, ',', '.') : '—' ?></strong>
            <?php if ($vidaUtil > 0): ?><small class="text-muted">km previstos</small><?php endif; ?>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">⚠️ Desgaste</small>
            <?php if ($percentual === null): ?>
                <strong class="fs-4">—</strong>
            <?php else: ?>
                <strong class="fs-4"><?= number_format($percentual, 0, ',', '.') ?>%</strong>
                <div class="progress mt-2" style="height: 6px;" role="progressbar" aria-valuenow="<?= (int) $percentual ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar <?= str_replace('text-bg-', 'bg-', $badgeDesgaste) ?>" style="width: <?= (int) $percentual ?>%"></div>
                </div>
            <?php endif; ?>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">🚗 Veículo</small>
            <strong class="fs-5 d-block text-truncate"><?= h($pneu['placa']) ?></strong>
            <small class="text-muted text-truncate d-block"><?= h((string) ($pneu['veiculo_modelo'] ?? '')) ?: '—' ?></small>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Ficha do pneu</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhas = [
                    ['Veículo', $pneu['placa']],
                    ['Posição', $posicao],
                    ['Marca', $pneu['marca']],
                    ['Modelo', $pneu['modelo']],
                    ['Dimensão', $pneu['dimensao']],
                    ['Nº de série', $pneu['numero_serie']],
                    ['Data de instalação', dataBr($pneu['data_instalacao'] ?: null)],
                ];
                foreach ($linhas as [$rotulo, $valor]): ?>
                    <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                        <strong class="small"><?= h($rotulo) ?></strong>
                        <span class="small text-sm-end"><?= h((string) ($valor ?? '')) ?: '—' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="col-12 col-lg-5">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Situação</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhasSituacao = [
                    ['Status', $status],
                    ['KM na instalação', number_format($kmInstalacao, 0, ',', '.') . ' km'],
                    ['KM atual', number_format($kmAtual, 0, ',', '.') . ' km'],
                    ['KM rodados', number_format($kmRodados, 0, ',', '.') . ' km'],
                    ['Vida útil', $vidaUtil > 0 ? number_format($vidaUtil, 0, ',', '.') . ' km' : '—'],
                    ['Desgaste', $percentual === null ? '—' : number_format($percentual, 0, ',', '.') . '%'],
                    ['Observações', $pneu['observacoes']],
                    ['Cadastrado em', dataBr($pneu['data_criacao'] ?: null)],
                ];
                foreach ($linhasSituacao as [$rotulo, $valor]): ?>
                    <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                        <strong class="small"><?= h($rotulo) ?></strong>
                        <span class="small text-sm-end"><?= h((string) ($valor ?? '')) ?: '—' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
