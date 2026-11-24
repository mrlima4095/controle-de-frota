<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Detalhes do veículo';
$pageSubtitle = 'Informações completas do veículo.';
$activeMenu = 'veiculos';

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
    'SELECT v.*, d.nome AS departamento
     FROM veiculo v
     LEFT JOIN departamento d ON d.id_departamento = v.id_dep
     WHERE v.id_vei = :id LIMIT 1'
);
$stmt->execute([':id' => $id]);
$veiculo = $stmt->fetch();

if (!$veiculo) {
    header('Location: /dashboard/veiculos/index.php');
    exit;
}

function contarRelacionados(PDO $pdo, string $tabela, int $id): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $tabela WHERE id_vei = :id");
    $stmt->execute([':id' => $id]);
    return (int) $stmt->fetchColumn();
}

$totalManutencoes = contarRelacionados($pdo, 'manutencao', $id);
$totalAbastecimentos = contarRelacionados($pdo, 'abastecimento', $id);
$totalMultas = contarRelacionados($pdo, 'multa', $id);
$totalViagens = contarRelacionados($pdo, 'percurso', $id);

$badgeStatus = match ($veiculo['status']) {
    'disponivel' => 'text-bg-success',
    'em_uso' => 'text-bg-primary',
    'manutencao' => 'text-bg-warning',
    default => 'text-bg-secondary',
};

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Veículo atualizado com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 mb-1"><?= h($veiculo['placa']) ?> <span class="badge rounded-pill <?= $badgeStatus ?>"><?= h($veiculo['status']) ?></span></h2>
        <p class="text-muted small mb-0"><?= h(trim(($veiculo['marca'] ?? '') . ' ' . ($veiculo['modelo'] ?? ''))) ?: 'Sem marca/modelo informados' ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="index.php">← Voltar</a>
        <a class="btn btn-brand" href="edit.php?id=<?= $id ?>">✏️ Editar</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">🔧 Manutenções</small>
            <strong class="fs-4"><?= $totalManutencoes ?></strong>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">⛽ Abastecimentos</small>
            <strong class="fs-4"><?= $totalAbastecimentos ?></strong>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">⚠️ Multas</small>
            <strong class="fs-4"><?= $totalMultas ?></strong>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block">🛣️ Viagens</small>
            <strong class="fs-4"><?= $totalViagens ?></strong>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Ficha técnica</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhas = [
                    ['Placa', $veiculo['placa']],
                    ['Marca', $veiculo['marca']],
                    ['Modelo', $veiculo['modelo']],
                    ['Tipo', ucfirst($veiculo['tipo'])],
                    ['Ano de fabricação', $veiculo['ano_fabricacao']],
                    ['Ano do modelo', $veiculo['ano_modelo']],
                    ['Cor', $veiculo['cor']],
                    ['Chassi', $veiculo['chassi']],
                    ['Combustível', ucfirst($veiculo['combustivel'])],
                    ['Capacidade', $veiculo['capacidade']],
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
                    ['Status', ucfirst($veiculo['status'])],
                    ['Departamento', $veiculo['departamento']],
                    ['KM inicial', number_format((int) $veiculo['quilometragem_inicial'], 0, ',', '.') . ' km'],
                    ['KM atual', number_format((int) $veiculo['quilometragemAtual'], 0, ',', '.') . ' km'],
                    ['Ativo', $veiculo['ativo'] ? 'Sim' : 'Não'],
                    ['Cadastrado em', dataBr($veiculo['data_criacao'])],
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
