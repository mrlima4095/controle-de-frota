<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Despesas';
$pageSubtitle = 'Lançamentos de despesas e acompanhamento por categoria.';
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

$categorias = ['combustivel', 'manutencao', 'pneu', 'multa', 'seguro', 'pedagio', 'salario', 'outro'];

$busca = trim($_GET['q'] ?? '');
$categoriaFiltro = (string) ($_GET['categoria'] ?? '');
if (!in_array($categoriaFiltro, $categorias, true)) {
    $categoriaFiltro = '';
}

$inicioMes = date('Y-m-01');
$fimMes = date('Y-m-t');
$stmt = $pdo->prepare('SELECT COALESCE(SUM(valor), 0) AS total FROM despesa WHERE data_despesa BETWEEN :inicio AND :fim');
$stmt->execute([':inicio' => $inicioMes, ':fim' => $fimMes]);
$totalMes = (float) $stmt->fetchColumn();

$resumoGeral = $pdo->query('SELECT COALESCE(SUM(valor), 0) AS total, COUNT(*) AS quantidade FROM despesa')->fetch();
$totalGeral = (float) $resumoGeral['total'];
$quantidadeGeral = (int) $resumoGeral['quantidade'];

$sql = 'SELECT d.id_des, d.descricao, d.categoria, d.valor, d.data_despesa,
               v.placa, f.nome AS fornecedor
        FROM despesa d
        LEFT JOIN veiculo v ON v.id_vei = d.id_vei
        LEFT JOIN fornecedor f ON f.id_forn = d.id_forn';
$condicoes = [];
$parametros = [];

if ($busca !== '') {
    $condicoes[] = '(d.descricao LIKE :q OR v.placa LIKE :q OR f.nome LIKE :q)';
    $parametros[':q'] = '%' . $busca . '%';
}
if ($categoriaFiltro !== '') {
    $condicoes[] = 'd.categoria = :categoria';
    $parametros[':categoria'] = $categoriaFiltro;
}
if ($condicoes !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}
$sql .= ' ORDER BY d.data_despesa DESC, d.id_des DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$despesas = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Despesa salva com sucesso.</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between gap-3">
                <div>
                    <small class="text-muted d-block"><i class="fa-solid fa-calendar"></i> Despesas no mês</small>
                    <strong class="fs-4 d-block"><?= moeda($totalMes) ?></strong>
                    <small class="text-muted"><?= h(date('m/Y')) ?></small>
                </div>
                <span class="display-6" aria-hidden="true"><i class="fa-solid fa-coins"></i></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between gap-3">
                <div>
                    <small class="text-muted d-block"><i class="fa-solid fa-chart-bar"></i> Total geral</small>
                    <strong class="fs-4 d-block"><?= moeda($totalGeral) ?></strong>
                    <small class="text-muted"><?= $quantidadeGeral ?> lançamento(s) registrado(s)</small>
                </div>
                <span class="display-6" aria-hidden="true"><i class="fa-solid fa-receipt"></i></span>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
    <div>
        <h2 class="h5 mb-1">Lançamentos</h2>
        <p class="text-muted small mb-0"><?= count($despesas) ?> despesa(s) encontrada(s).</p>
    </div>
    <a class="btn btn-brand" href="create.php"><i class="fa-solid fa-plus"></i> Nova despesa</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-sm-6 col-lg-5">
        <input type="search" class="form-control" name="q" value="<?= h($busca) ?>" placeholder="Buscar por descrição, placa ou fornecedor">
    </div>
    <div class="col-12 col-sm-4 col-lg-3">
        <select class="form-select" name="categoria">
            <option value="">Todas as categorias</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?= $categoria ?>"<?= $categoriaFiltro === $categoria ? ' selected' : '' ?>><?= h(rotuloCategoria($categoria)) ?></option>
            <?php endforeach; ?>
        </select>
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
                    <th scope="col">Descrição</th>
                    <th scope="col">Categoria</th>
                    <th scope="col" class="text-end">Valor</th>
                    <th scope="col">Data</th>
                    <th scope="col">Veículo</th>
                    <th scope="col">Fornecedor</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$despesas): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma despesa encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($despesas as $despesa): ?>
                        <?php $categoria = (string) ($despesa['categoria'] ?? 'outro'); ?>
                        <tr>
                            <td><strong><?= h((string) $despesa['descricao']) ?></strong></td>
                            <td><span class="badge rounded-pill <?= badgeCategoria($categoria) ?>"><?= h(rotuloCategoria($categoria)) ?></span></td>
                            <td class="text-end text-nowrap fw-semibold"><?= moeda((float) $despesa['valor']) ?></td>
                            <td class="small text-muted text-nowrap"><?= dataBr($despesa['data_despesa']) ?></td>
                            <td class="small"><?= h((string) ($despesa['placa'] ?? '')) ?: '—' ?></td>
                            <td class="small"><?= h((string) ($despesa['fornecedor'] ?? '')) ?: '—' ?></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $despesa['id_des'] ?>"><i class="fa-solid fa-eye"></i> Ver</a>
                                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int) $despesa['id_des'] ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
