<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Fornecedores';
$pageSubtitle = 'Cadastro de fornecedores e parceiros da frota.';
$activeMenu = 'fornecedores';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

$busca = trim($_GET['q'] ?? '');

$resumo = $pdo->query(
    'SELECT COUNT(*) AS total,
            COALESCE(SUM(CASE WHEN ativo = TRUE THEN 1 ELSE 0 END), 0) AS ativos
     FROM fornecedor'
)->fetch();
$totalFornecedores = (int) $resumo['total'];
$totalAtivos = (int) $resumo['ativos'];
$totalInativos = $totalFornecedores - $totalAtivos;

$sql = 'SELECT f.id_forn, f.nome, f.cnpj_cpf, f.telefone, f.email, f.categoria,
               f.uf, f.cidade, f.ativo,
               COUNT(d.id_des) AS total_despesas,
               COALESCE(SUM(d.valor), 0) AS valor_despesas
        FROM fornecedor f
        LEFT JOIN despesa d ON d.id_forn = f.id_forn';
$parametros = [];

if ($busca !== '') {
    $sql .= ' WHERE f.nome LIKE :q OR f.cnpj_cpf LIKE :q OR f.categoria LIKE :q';
    $parametros[':q'] = '%' . $busca . '%';
}

$sql .= ' GROUP BY f.id_forn, f.nome, f.cnpj_cpf, f.telefone, f.email,
                   f.categoria, f.uf, f.cidade, f.ativo
         ORDER BY f.nome ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$fornecedores = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Fornecedor salvo com sucesso.</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-building"></i> Cadastrados</small>
            <strong class="fs-4 d-block"><?= $totalFornecedores ?></strong>
        </div></div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-check"></i> Ativos</small>
            <strong class="fs-4 d-block text-success"><?= $totalAtivos ?></strong>
        </div></div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-ban"></i> Inativos</small>
            <strong class="fs-4 d-block text-muted"><?= $totalInativos ?></strong>
        </div></div>
    </div>
</div>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
    <div>
        <h2 class="h5 mb-1">Cadastro de fornecedores</h2>
        <p class="text-muted small mb-0"><?= count($fornecedores) ?> fornecedor(es) encontrado(s).</p>
    </div>
    <a class="btn btn-brand" href="create.php"><i class="fa-solid fa-plus"></i> Novo fornecedor</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-sm-8 col-lg-6">
        <input type="search" class="form-control" name="q" value="<?= h($busca) ?>" placeholder="Buscar por nome, CNPJ/CPF ou categoria">
    </div>
    <div class="col-12 col-sm-auto">
        <button type="submit" class="btn btn-outline-secondary w-100"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">Nome</th>
                    <th scope="col">Contato</th>
                    <th scope="col">Categoria</th>
                    <th scope="col">Localização</th>
                    <th scope="col" class="text-end">Despesas</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$fornecedores): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Nenhum fornecedor encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($fornecedores as $fornecedor): ?>
                        <tr>
                            <td>
                                <strong><?= h((string) $fornecedor['nome']) ?></strong>
                                <small class="text-muted d-block"><?= h((string) ($fornecedor['cnpj_cpf'] ?? '')) ?: '—' ?></small>
                            </td>
                            <td class="small">
                                <span class="d-block"><?= h((string) ($fornecedor['telefone'] ?? '')) ?: '—' ?></span>
                                <small class="text-muted text-break"><?= h((string) ($fornecedor['email'] ?? '')) ?: '—' ?></small>
                            </td>
                            <td class="small"><?= h((string) ($fornecedor['categoria'] ?? '')) ?: '—' ?></td>
                            <td class="small text-nowrap"><?= h(trim((string) ($fornecedor['cidade'] ?? '') . ' - ' . (string) ($fornecedor['uf'] ?? ''), ' -')) ?: '—' ?></td>
                            <td class="text-end text-nowrap small">
                                <span class="d-block fw-semibold"><?= (int) $fornecedor['total_despesas'] ?> lançamento(s)</span>
                                <small class="text-muted"><?= moeda((float) $fornecedor['valor_despesas']) ?></small>
                            </td>
                            <td>
                                <span class="badge rounded-pill <?= $fornecedor['ativo'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= $fornecedor['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $fornecedor['id_forn'] ?>"><i class="fa-solid fa-eye"></i> Ver</a>
                                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int) $fornecedor['id_forn'] ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
