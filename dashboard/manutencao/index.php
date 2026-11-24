<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Manutenção';
$pageSubtitle = 'Histórico de serviços e cuidados com a frota.';
$activeMenu = 'manutencao';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function dataBr(?string $valor): string
{
    return $valor ? date('d/m/Y', (int) strtotime($valor)) : '—';
}

function badgeTipo(string $tipo): string
{
    return match ($tipo) {
        'preventiva' => 'text-bg-success',
        'corretiva' => 'text-bg-danger',
        'revisao' => 'text-bg-primary',
        default => 'text-bg-secondary',
    };
}

$busca = trim($_GET['q'] ?? '');
$veiculoFiltro = (int) ($_GET['veiculo'] ?? 0);

$veiculos = $pdo->query('SELECT id_vei, placa, modelo FROM veiculo ORDER BY placa ASC')->fetchAll();

$sql = 'SELECT m.id_man, m.tipo, m.data_man, m.oficina, m.servico, m.custo,
               m.quilometragem, m.proxima_data, v.placa, v.modelo
        FROM manutencao m
        INNER JOIN veiculo v ON v.id_vei = m.id_vei';
$condicoes = [];
$parametros = [];

if ($busca !== '') {
    $condicoes[] = '(v.placa LIKE :q OR v.modelo LIKE :q OR m.oficina LIKE :q OR m.servico LIKE :q)';
    $parametros[':q'] = '%' . $busca . '%';
}
if ($veiculoFiltro > 0) {
    $condicoes[] = 'm.id_vei = :veiculo';
    $parametros[':veiculo'] = $veiculoFiltro;
}
if ($condicoes !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}
$sql .= ' ORDER BY m.data_man DESC, m.id_man DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$manutencoes = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Manutenção salva com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
    <div>
        <h2 class="h5 mb-1">Histórico de manutenções</h2>
        <p class="text-muted small mb-0"><?= count($manutencoes) ?> manutenção(ões) encontrada(s).</p>
    </div>
    <a class="btn btn-brand" href="create.php">➕ Nova manutenção</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-sm-6 col-lg-5">
        <input type="search" class="form-control" name="q" value="<?= h($busca) ?>" placeholder="Buscar por placa, modelo, oficina ou serviço">
    </div>
    <div class="col-12 col-sm-4 col-lg-3">
        <select class="form-select" name="veiculo">
            <option value="">Todos os veículos</option>
            <?php foreach ($veiculos as $veiculo): ?>
                <option value="<?= (int) $veiculo['id_vei'] ?>"<?= $veiculoFiltro === (int) $veiculo['id_vei'] ? ' selected' : '' ?>>
                    <?= h($veiculo['placa']) ?> — <?= h((string) ($veiculo['modelo'] ?? '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-sm-auto">
        <button type="submit" class="btn btn-outline-secondary w-100">🔎 Buscar</button>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">Veículo</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Data</th>
                    <th scope="col">Oficina / Serviço</th>
                    <th scope="col" class="text-end">Custo</th>
                    <th scope="col">Próxima data</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$manutencoes): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma manutenção encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($manutencoes as $item): ?>
                        <tr>
                            <td>
                                <strong><?= h($item['placa']) ?></strong>
                                <span class="d-block small"><?= h((string) ($item['modelo'] ?? '')) ?: '—' ?></span>
                            </td>
                            <td><span class="badge rounded-pill <?= badgeTipo((string) ($item['tipo'] ?? '')) ?>"><?= h(ucfirst((string) ($item['tipo'] ?? '')) ?: '—') ?></span></td>
                            <td class="small text-nowrap">🗓️ <?= dataBr($item['data_man']) ?></td>
                            <td class="small">
                                <?= h((string) ($item['oficina'] ?? '')) ?: '—' ?>
                                <span class="d-block text-muted text-truncate" style="max-width: 220px;"><?= h((string) ($item['servico'] ?? '')) ?: '—' ?></span>
                            </td>
                            <td class="small text-end text-nowrap">R$ <?= number_format((float) ($item['custo'] ?? 0), 2, ',', '.') ?></td>
                            <td class="small text-nowrap"><?= dataBr($item['proxima_data']) ?></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $item['id_man'] ?>">👁️ Ver</a>
                                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int) $item['id_man'] ?>">✏️ Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
