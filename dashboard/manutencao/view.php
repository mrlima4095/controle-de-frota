<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Detalhes da manutenção';
$pageSubtitle = 'Informações completas da manutenção registrada.';
$activeMenu = 'manutencao';

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
    'SELECT m.*, v.placa, v.modelo
     FROM manutencao m
     INNER JOIN veiculo v ON v.id_vei = m.id_vei
     WHERE m.id_man = :id LIMIT 1'
);
$stmt->execute([':id' => $id]);
$registro = $stmt->fetch();

if (!$registro) {
    header('Location: /dashboard/manutencao/index.php');
    exit;
}

$badgeTipo = match ((string) ($registro['tipo'] ?? '')) {
    'preventiva' => 'text-bg-success',
    'corretiva' => 'text-bg-danger',
    'revisao' => 'text-bg-primary',
    default => 'text-bg-secondary',
};

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Manutenção atualizada com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 mb-1">
            🔧 Manutenção #<?= $id ?>
            <span class="badge rounded-pill <?= $badgeTipo ?>"><?= h(ucfirst((string) ($registro['tipo'] ?? '')) ?: '—') ?></span>
        </h2>
        <p class="text-muted small mb-0"><?= h($registro['placa']) ?> — <?= h((string) ($registro['modelo'] ?? '')) ?: 'Sem modelo informado' ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="index.php">← Voltar</a>
        <a class="btn btn-brand" href="edit.php?id=<?= $id ?>">✏️ Editar</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">💰 Custo</small>
            <strong class="fs-5">R$ <?= number_format((float) ($registro['custo'] ?? 0), 2, ',', '.') ?></strong>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">🛣️ Quilometragem</small>
            <strong class="fs-5"><?= $registro['quilometragem'] !== null ? number_format((int) $registro['quilometragem'], 0, ',', '.') . ' km' : '—' ?></strong>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">🗓️ Próxima data</small>
            <strong class="fs-5"><?= dataBr($registro['proxima_data']) ?></strong>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">📅 Realizada em</small>
            <strong class="fs-5"><?= dataBr($registro['data_man']) ?></strong>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Dados da manutenção</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhas = [
                    ['Veículo', $registro['placa'] . ' — ' . (string) ($registro['modelo'] ?? '')],
                    ['Tipo', ucfirst((string) ($registro['tipo'] ?? ''))],
                    ['🗓️ Data', dataBr($registro['data_man'])],
                    ['Oficina', $registro['oficina']],
                    ['Custo', 'R$ ' . number_format((float) ($registro['custo'] ?? 0), 2, ',', '.')],
                    ['Quilometragem', $registro['quilometragem'] !== null ? number_format((int) $registro['quilometragem'], 0, ',', '.') . ' km' : ''],
                    ['Próxima data', dataBr($registro['proxima_data'])],
                    ['Cadastrado em', dataBr($registro['data_criacao'])],
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
        <section class="card border-0 shadow-sm mb-3">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">🔧 Serviço executado</h2></header>
            <div class="card-body">
                <p class="small mb-0 text-break"><?= nl2br(h((string) ($registro['servico'] ?? ''))) ?: '—' ?></p>
            </div>
        </section>
        <section class="card border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">📝 Observações</h2></header>
            <div class="card-body">
                <p class="small mb-0 text-break"><?= nl2br(h((string) ($registro['observacoes'] ?? ''))) ?: '—' ?></p>
            </div>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
