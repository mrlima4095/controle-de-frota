<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Detalhes da despesa';
$pageSubtitle = 'Informações completas do lançamento.';
$activeMenu = 'despesas';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function rotuloCategoria(string $categoria): string
{
    return match ($categoria) {
        'combustivel' => 'Combustível',
        'manutencao' => 'Manutenção',
        'pneu' => 'Pneu',
        'multa' => 'Multa',
        'seguro' => 'Seguro',
        'pedagio' => 'Pedágio',
        'salario' => 'Salário',
        default => 'Outro',
    };
}

function badgeCategoria(string $categoria): string
{
    return match ($categoria) {
        'combustivel' => 'text-bg-warning',
        'manutencao' => 'text-bg-primary',
        'pneu' => 'text-bg-info',
        'multa' => 'text-bg-danger',
        'seguro' => 'text-bg-success',
        'pedagio' => 'text-bg-secondary',
        'salario' => 'text-bg-dark',
        default => 'text-bg-secondary',
    };
}

function dataBr(?string $valor): string
{
    return $valor ? date('d/m/Y', (int) strtotime($valor)) : '—';
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT d.*, v.placa, f.nome AS fornecedor
     FROM despesa d
     LEFT JOIN veiculo v ON v.id_vei = d.id_vei
     LEFT JOIN fornecedor f ON f.id_forn = d.id_forn
     WHERE d.id_des = :id LIMIT 1'
);
$stmt->execute([':id' => $id]);
$despesa = $stmt->fetch();

if (!$despesa) {
    header('Location: /dashboard/despesas/index.php');
    exit;
}

$categoria = (string) ($despesa['categoria'] ?? 'outro');

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Despesa atualizada com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 mb-1"><?= h((string) $despesa['descricao']) ?></h2>
        <p class="text-muted small mb-0">Lançamento registrado em <?= dataBr($despesa['data_criacao']) ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="index.php">← Voltar</a>
        <a class="btn btn-brand" href="edit.php?id=<?= $id ?>"><i class="fa-solid fa-pen"></i> Editar</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-coins"></i> Valor</small>
            <strong class="fs-4 text-nowrap"><?= moeda((float) $despesa['valor']) ?></strong>
        </div></div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-tag"></i> Categoria</small>
            <strong class="d-block mt-1"><span class="badge rounded-pill <?= badgeCategoria($categoria) ?>"><?= h(rotuloCategoria($categoria)) ?></span></strong>
        </div></div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-calendar-days"></i> Data da despesa</small>
            <strong class="fs-4 d-block"><?= dataBr($despesa['data_despesa']) ?></strong>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Dados da despesa</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhas = [
                    ['Descrição', $despesa['descricao']],
                    ['Categoria', rotuloCategoria($categoria)],
                    ['Valor', moeda((float) $despesa['valor'])],
                    ['Data da despesa', dataBr($despesa['data_despesa'])],
                    ['Veículo', $despesa['placa']],
                    ['Fornecedor', $despesa['fornecedor']],
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
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Informações adicionais</h2></header>
            <div class="list-group list-group-flush">
                <div class="list-group-item">
                    <strong class="small d-block mb-1"><i class="fa-solid fa-note-sticky"></i> Observações</strong>
                    <span class="small text-muted"><?= h((string) ($despesa['observacoes'] ?? '')) ?: '—' ?></span>
                </div>
                <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                    <strong class="small"><i class="fa-solid fa-clock"></i> Cadastrado em</strong>
                    <span class="small text-sm-end"><?= dataBr($despesa['data_criacao']) ?></span>
                </div>
                <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                    <strong class="small"><i class="fa-solid fa-hashtag"></i> Registro</strong>
                    <span class="small text-sm-end">#<?= $id ?></span>
                </div>
            </div>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
