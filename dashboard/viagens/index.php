<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Viagens';
$pageSubtitle = 'Percursos registrados com veículo e motorista.';
$activeMenu = 'viagens';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function dataBr(?string $valor): string
{
    return $valor ? date('d/m/Y', (int) strtotime($valor)) : '—';
}

$busca = trim($_GET['q'] ?? '');

$sql = 'SELECT p.id_perc, p.data_per, p.hora_ini, p.origem, p.destino, p.quilometragem,
               v.placa, v.modelo, m.nome AS motorista
        FROM percurso p
        JOIN veiculo v ON v.id_vei = p.id_vei
        JOIN motorista m ON m.id_mot = p.id_mot';
$parametros = [];

if ($busca !== '') {
    $sql .= ' WHERE v.placa LIKE :q1 OR m.nome LIKE :q2 OR p.origem LIKE :q3 OR p.destino LIKE :q4';
    $parametros[':q1'] = '%' . $busca . '%';
    $parametros[':q2'] = '%' . $busca . '%';
    $parametros[':q3'] = '%' . $busca . '%';
    $parametros[':q4'] = '%' . $busca . '%';
}

$sql .= ' ORDER BY p.data_per DESC, p.hora_ini DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$viagens = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Viagem salva com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
    <div>
        <h2 class="h5 mb-1">Percursos registrados</h2>
        <p class="text-muted small mb-0"><?= count($viagens) ?> viagem(ns) encontrada(s).</p>
    </div>
    <a class="btn btn-brand" href="create.php"><i class="fa-solid fa-plus"></i> Nova viagem</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-sm-8 col-lg-6">
        <input type="search" class="form-control" name="q" value="<?= h($busca) ?>" placeholder="Buscar por placa, motorista, origem ou destino">
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
                    <th scope="col">Data</th>
                    <th scope="col">Veículo</th>
                    <th scope="col">Motorista</th>
                    <th scope="col">Rota</th>
                    <th scope="col">KM</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$viagens): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma viagem encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($viagens as $viagem): ?>
                        <tr>
                            <td class="text-nowrap">
                                <strong class="d-block small"><?= dataBr($viagem['data_per']) ?></strong>
                                <small class="text-muted"><?= h(substr((string) $viagem['hora_ini'], 0, 5)) ?></small>
                            </td>
                            <td>
                                <strong class="small"><?= h($viagem['placa']) ?></strong>
                                <small class="d-block text-muted"><?= h($viagem['modelo'] ?? '') ?></small>
                            </td>
                            <td class="small"><?= h($viagem['motorista']) ?></td>
                            <td>
                                <span class="small d-block text-truncate" style="max-width: 220px;"><?= h($viagem['origem']) ?> → <?= h($viagem['destino']) ?></span>
                            </td>
                            <td class="small text-muted text-nowrap"><?= number_format((int) $viagem['quilometragem'], 0, ',', '.') ?> km</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $viagem['id_perc'] ?>"><i class="fa-solid fa-eye"></i> Ver</a>
                                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int) $viagem['id_perc'] ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
