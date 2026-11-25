<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Sinistros e multas';
$pageSubtitle = 'Infrações registradas, valores e pontos da frota.';
$activeMenu = 'sinistros';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function moeda(float $v): string
{
    return 'R$ ' . number_format($v, 2, ',', '.');
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

function dataBr(?string $valor): string
{
    return $valor ? date('d/m/Y', (int) strtotime($valor)) : '—';
}

$statusLista = ['pendente', 'paga', 'cancelada'];
$busca = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

if (!in_array($status, $statusLista, true)) {
    $status = '';
}

$resumo = $pdo->query(
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(valor_multa), 0) AS valor,
            COALESCE(SUM(pontos), 0) AS pontos
     FROM multa
     WHERE status = 'pendente'"
)->fetch();

$sql = 'SELECT mu.id_mul, mu.data_infra, mu.hora_infra, mu.`local`, mu.infracao,
               mu.valor_multa, mu.pontos, mu.status, mu.data_pagamento,
               v.placa, v.modelo, mot.nome AS motorista
        FROM multa mu
        LEFT JOIN veiculo v ON v.id_vei = mu.id_vei
        LEFT JOIN motorista mot ON mot.id_mot = mu.id_mot';
$condicoes = [];
$parametros = [];

if ($busca !== '') {
    $condicoes[] = '(mu.infracao LIKE :q1 OR mu.`local` LIKE :q2 OR v.placa LIKE :q3 OR mot.nome LIKE :q4)';
    $parametros[':q1'] = '%' . $busca . '%';
    $parametros[':q2'] = '%' . $busca . '%';
    $parametros[':q3'] = '%' . $busca . '%';
    $parametros[':q4'] = '%' . $busca . '%';
}

if ($status !== '') {
    $condicoes[] = 'mu.status = :status';
    $parametros[':status'] = $status;
}

if ($condicoes !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}

$sql .= ' ORDER BY mu.data_infra DESC, mu.id_mul DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$multas = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Multa salva com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
    <div>
        <h2 class="h5 mb-1">Multas registradas</h2>
        <p class="text-muted small mb-0"><?= count($multas) ?> multa(s) encontrada(s).</p>
    </div>
    <a class="btn btn-brand" href="create.php"><i class="fa-solid fa-plus"></i> Nova multa</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-triangle-exclamation"></i> Multas pendentes</small>
            <strong class="fs-4"><?= (int) ($resumo['total'] ?? 0) ?></strong>
        </div></div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-coins"></i> Valor pendente</small>
            <strong class="fs-4"><?= moeda((float) ($resumo['valor'] ?? 0)) ?></strong>
        </div></div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-thumbtack"></i> Pontos pendentes</small>
            <strong class="fs-4"><?= (int) ($resumo['pontos'] ?? 0) ?></strong>
        </div></div>
    </div>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-sm-8 col-lg-5">
        <input type="search" class="form-control" name="q" value="<?= h($busca) ?>" placeholder="Buscar por infração, local, placa ou motorista">
    </div>
    <div class="col-12 col-sm-4 col-lg-3">
        <select class="form-select" name="status">
            <option value="">Todos os status</option>
            <?php foreach ($statusLista as $opcao): ?>
                <option value="<?= $opcao ?>"<?= $status === $opcao ? ' selected' : '' ?>><?= ucfirst($opcao) ?></option>
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
                    <th scope="col">Infração</th>
                    <th scope="col">Veículo</th>
                    <th scope="col">Motorista</th>
                    <th scope="col">Data/Hora</th>
                    <th scope="col">Valor</th>
                    <th scope="col">Pontos</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$multas): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma multa encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($multas as $multa): ?>
                        <tr>
                            <td>
                                <span class="d-block small fw-semibold"><?= h((string) $multa['infracao']) ?></span>
                                <small class="text-muted"><?= h((string) ($multa['local'] ?? '')) ?: '—' ?></small>
                            </td>
                            <td class="small text-nowrap"><?= h(trim(($multa['placa'] ?? '') . ' ' . ($multa['modelo'] ?? ''))) ?: '—' ?></td>
                            <td class="small"><?= h((string) ($multa['motorista'] ?? '')) ?: '—' ?></td>
                            <td class="small text-muted text-nowrap">
                                <?= dataBr($multa['data_infra']) ?>
                                <span class="d-block"><?= h(substr((string) ($multa['hora_infra'] ?? ''), 0, 5)) ?: '—' ?></span>
                            </td>
                            <td class="small text-nowrap"><?= moeda((float) $multa['valor_multa']) ?></td>
                            <td class="small"><?= (int) $multa['pontos'] ?></td>
                            <td><span class="badge rounded-pill <?= badgeStatus((string) $multa['status']) ?>"><?= h((string) $multa['status']) ?></span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $multa['id_mul'] ?>"><i class="fa-solid fa-eye"></i> Ver</a>
                                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int) $multa['id_mul'] ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
