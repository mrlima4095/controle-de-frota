<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Detalhes da multa';
$pageSubtitle = 'Informações completas da infração registrada.';
$activeMenu = 'sinistros';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function moeda(float $v): string
{
    return 'R$ ' . number_format($v, 2, ',', '.');
}

function dataBr(?string $valor): string
{
    return $valor ? date('d/m/Y', (int) strtotime($valor)) : '—';
}

function badgeStatus(string $status): string
{
    return match ($status) {
        'pendente' => 'text-bg-danger',
        'paga' => 'text-bg-success',
        'cancelada' => 'text-bg-secondary',
        default => 'text-bg-secondary',
    };
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT mu.*, v.placa, v.modelo, mot.nome AS motorista
     FROM multa mu
     LEFT JOIN veiculo v ON v.id_vei = mu.id_vei
     LEFT JOIN motorista mot ON mot.id_mot = mu.id_mot
     WHERE mu.id_mul = :id LIMIT 1'
);
$stmt->execute([':id' => $id]);
$multa = $stmt->fetch();

if (!$multa) {
    header('Location: /dashboard/sinistros/index.php');
    exit;
}

$status = (string) $multa['status'];
$badgeStatus = badgeStatus($status);

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Multa atualizada com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 mb-1">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= h((string) $multa['infracao']) ?>
            <span class="badge rounded-pill <?= $badgeStatus ?>"><?= h(ucfirst($status)) ?></span>
        </h2>
        <p class="text-muted small mb-0">
            <?= h(trim(($multa['placa'] ?? '') . ' ' . ($multa['modelo'] ?? ''))) ?: 'Veículo não informado' ?>
            · <?= dataBr($multa['data_infra']) ?>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="index.php">← Voltar</a>
        <a class="btn btn-brand" href="edit.php?id=<?= $id ?>"><i class="fa-solid fa-pen"></i> Editar</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-coins"></i> Valor da multa</small>
            <strong class="fs-4"><?= moeda((float) $multa['valor_multa']) ?></strong>
        </div></div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-thumbtack"></i> Pontos</small>
            <strong class="fs-4"><?= (int) ($multa['pontos'] ?? 0) ?></strong>
        </div></div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-car"></i> Veículo</small>
            <strong class="fs-5"><?= h((string) ($multa['placa'] ?? '')) ?: '—' ?></strong>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Dados da infração</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhas = [
                    ['Infração', (string) $multa['infracao']],
                    ['Local', (string) ($multa['local'] ?? '')],
                    ['Data da infração', dataBr($multa['data_infra'])],
                    ['Hora da infração', substr((string) ($multa['hora_infra'] ?? ''), 0, 5)],
                    ['Veículo', trim(($multa['placa'] ?? '') . ' ' . ($multa['modelo'] ?? ''))],
                    ['Motorista', (string) ($multa['motorista'] ?? '')],
                    ['Observações', (string) ($multa['observacoes'] ?? '')],
                ];
                foreach ($linhas as [$rotulo, $valor]): ?>
                    <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                        <strong class="small"><?= h($rotulo) ?></strong>
                        <span class="small text-sm-end"><?= $valor !== '' ? h($valor) : '—' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="col-12 col-lg-5">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Situação e pagamento</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhasSituacao = [
                    ['Valor da multa', moeda((float) $multa['valor_multa'])],
                    ['Pontos', (string) (int) ($multa['pontos'] ?? 0)],
                    ['Status', ucfirst($status)],
                    ['Data de pagamento', dataBr($multa['data_pagamento'])],
                    ['Registrada em', dataBr($multa['data_criacao'])],
                ];
                foreach ($linhasSituacao as [$rotulo, $valor]): ?>
                    <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                        <strong class="small"><?= h($rotulo) ?></strong>
                        <span class="small text-sm-end"><?= $valor !== '' ? h($valor) : '—' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
