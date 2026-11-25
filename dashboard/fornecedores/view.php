<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Detalhes do fornecedor';
$pageSubtitle = 'Informações completas do fornecedor.';
$activeMenu = 'fornecedores';

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

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM fornecedor WHERE id_forn = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$fornecedor = $stmt->fetch();

if (!$fornecedor) {
    header('Location: /dashboard/fornecedores/index.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT COUNT(*) AS quantidade, COALESCE(SUM(valor), 0) AS total, MAX(data_despesa) AS ultima_data
     FROM despesa WHERE id_forn = :id'
);
$stmt->execute([':id' => $id]);
$resumoDespesas = $stmt->fetch();

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Fornecedor atualizado com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 mb-1"><?= h((string) $fornecedor['nome']) ?></h2>
        <p class="text-muted small mb-0">
            <?= h((string) ($fornecedor['cnpj_cpf'] ?? '')) ?: 'Sem CNPJ/CPF informado' ?>
            <span class="badge rounded-pill <?= $fornecedor['ativo'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                <?= $fornecedor['ativo'] ? 'Ativo' : 'Inativo' ?>
            </span>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="index.php">← Voltar</a>
        <a class="btn btn-brand" href="edit.php?id=<?= $id ?>"><i class="fa-solid fa-pen"></i> Editar</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-receipt"></i> Despesas</small>
            <strong class="fs-4 d-block"><?= (int) $resumoDespesas['quantidade'] ?></strong>
            <small class="text-muted">lançamento(s) vinculado(s)</small>
        </div></div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-coins"></i> Total gasto</small>
            <strong class="fs-4 d-block text-nowrap"><?= moeda((float) $resumoDespesas['total']) ?></strong>
        </div></div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-calendar-days"></i> Última despesa</small>
            <strong class="fs-4 d-block"><?= dataBr($resumoDespesas['ultima_data']) ?></strong>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Dados cadastrais</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhas = [
                    ['Nome', $fornecedor['nome']],
                    ['CNPJ/CPF', $fornecedor['cnpj_cpf']],
                    ['Categoria', $fornecedor['categoria']],
                    ['Status', $fornecedor['ativo'] ? 'Ativo' : 'Inativo'],
                    ['Cadastrado em', dataBr($fornecedor['data_criacao'])],
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

    <div class="col-12 col-lg-6">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Contato e localização</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $contato = [
                    ['Telefone', $fornecedor['telefone']],
                    ['E-mail', $fornecedor['email']],
                    ['Cidade', $fornecedor['cidade']],
                    ['UF', $fornecedor['uf']],
                ];
                foreach ($contato as [$rotulo, $valor]): ?>
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
