<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Detalhes do abastecimento';
$pageSubtitle = 'Informações completas do abastecimento registrado.';
$activeMenu = 'abastecimentos';

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
    'SELECT a.*, v.placa, v.modelo
     FROM abastecimento a
     INNER JOIN veiculo v ON v.id_vei = a.id_vei
     WHERE a.id_aba = :id LIMIT 1'
);
$stmt->execute([':id' => $id]);
$registro = $stmt->fetch();

if (!$registro) {
    header('Location: /dashboard/abastecimentos/index.php');
    exit;
}

$badgeCombustivel = match ((string) ($registro['tipo_combustivel'] ?? '')) {
    'flex' => 'text-bg-success',
    'gasolina' => 'text-bg-primary',
    'diesel' => 'text-bg-warning',
    'eletrico' => 'text-bg-info',
    default => 'text-bg-secondary',
};

$litros = (float) $registro['litros'];
$custoPorLitro = $litros > 0
    ? 'R$ ' . number_format((float) $registro['valor'] / $litros, 2, ',', '.')
    : '—';

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Abastecimento atualizado com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 mb-1">
            ⛽ Abastecimento #<?= $id ?>
            <span class="badge rounded-pill <?= $badgeCombustivel ?>"><?= h(ucfirst((string) ($registro['tipo_combustivel'] ?? '')) ?: '—') ?></span>
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
            <small class="text-muted d-block">⛽ Litros</small>
            <strong class="fs-5"><?= number_format($litros, 2, ',', '.') ?> L</strong>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">💰 Valor total</small>
            <strong class="fs-5">R$ <?= number_format((float) $registro['valor'], 2, ',', '.') ?></strong>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">📈 Custo por litro</small>
            <strong class="fs-5"><?= h($custoPorLitro) ?></strong>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">🛣️ Quilometragem</small>
            <strong class="fs-5"><?= number_format((int) $registro['quilometragem'], 0, ',', '.') ?> km</strong>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Dados do abastecimento</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhas = [
                    ['Veículo', $registro['placa'] . ' — ' . (string) ($registro['modelo'] ?? '')],
                    ['🗓️ Data', dataBr($registro['data_aba'])],
                    ['🕐 Hora', substr((string) ($registro['hora_aba'] ?? ''), 0, 5)],
                    ['Posto', $registro['posto']],
                    ['Litros', number_format($litros, 2, ',', '.') . ' L'],
                    ['Valor', 'R$ ' . number_format((float) $registro['valor'], 2, ',', '.')],
                    ['Custo por litro', $custoPorLitro],
                    ['Quilometragem', number_format((int) $registro['quilometragem'], 0, ',', '.') . ' km'],
                    ['Tipo de combustível', ucfirst((string) ($registro['tipo_combustivel'] ?? ''))],
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
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">📝 Observações</h2></header>
            <div class="card-body">
                <p class="small mb-0 text-break"><?= nl2br(h((string) ($registro['observacoes'] ?? ''))) ?: '—' ?></p>
            </div>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
