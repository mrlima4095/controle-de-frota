<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Abastecimentos';
$pageSubtitle = 'Histórico de combustível, valores e quilometragem da frota.';
$activeMenu = 'abastecimentos';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function dataBr(?string $valor): string
{
    return $valor ? date('d/m/Y', (int) strtotime($valor)) : '—';
}

function custoPorLitro(array $registro): string
{
    $litros = (float) ($registro['litros'] ?? 0);
    if ($litros <= 0) {
        return '—';
    }

    return 'R$ ' . number_format((float) ($registro['valor'] ?? 0) / $litros, 2, ',', '.');
}

function badgeCombustivel(string $tipo): string
{
    return match ($tipo) {
        'flex' => 'text-bg-success',
        'gasolina' => 'text-bg-primary',
        'diesel' => 'text-bg-warning',
        'eletrico' => 'text-bg-info',
        default => 'text-bg-secondary',
    };
}

$busca = trim($_GET['q'] ?? '');
$veiculoFiltro = (int) ($_GET['veiculo'] ?? 0);

$veiculos = $pdo->query('SELECT id_vei, placa, modelo FROM veiculo ORDER BY placa ASC')->fetchAll();

$sql = 'SELECT a.id_aba, a.data_aba, a.hora_aba, a.posto, a.litros, a.valor,
               a.quilometragem, a.tipo_combustivel, v.placa, v.modelo
        FROM abastecimento a
        INNER JOIN veiculo v ON v.id_vei = a.id_vei';
$condicoes = [];
$parametros = [];

if ($busca !== '') {
    $condicoes[] = '(v.placa LIKE :q OR v.modelo LIKE :q OR a.posto LIKE :q)';
    $parametros[':q'] = '%' . $busca . '%';
}
if ($veiculoFiltro > 0) {
    $condicoes[] = 'a.id_vei = :veiculo';
    $parametros[':veiculo'] = $veiculoFiltro;
}
if ($condicoes !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}
$sql .= ' ORDER BY a.data_aba DESC, a.hora_aba DESC, a.id_aba DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$abastecimentos = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Abastecimento salvo com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
    <div>
        <h2 class="h5 mb-1">Histórico de abastecimentos</h2>
        <p class="text-muted small mb-0"><?= count($abastecimentos) ?> abastecimento(s) encontrado(s).</p>
    </div>
    <a class="btn btn-brand" href="create.php"><i class="fa-solid fa-plus"></i> Novo abastecimento</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-sm-6 col-lg-5">
        <input type="search" class="form-control" name="q" value="<?= h($busca) ?>" placeholder="Buscar por placa, modelo ou posto">
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
        <button type="submit" class="btn btn-outline-secondary w-100"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">Veículo</th>
                    <th scope="col">Data/Hora</th>
                    <th scope="col">Posto</th>
                    <th scope="col" class="text-end">Litros</th>
                    <th scope="col" class="text-end">Valor</th>
                    <th scope="col" class="text-end">R$/litro</th>
                    <th scope="col" class="text-end">KM</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$abastecimentos): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Nenhum abastecimento encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($abastecimentos as $item): ?>
                        <tr>
                            <td>
                                <strong><?= h($item['placa']) ?></strong>
                                <span class="d-block small"><?= h((string) ($item['modelo'] ?? '')) ?: '—' ?></span>
                                <span class="badge rounded-pill <?= badgeCombustivel((string) ($item['tipo_combustivel'] ?? '')) ?> mt-1">
                                    <?= h(ucfirst((string) ($item['tipo_combustivel'] ?? '')) ?: '—') ?>
                                </span>
                            </td>
                            <td class="small text-nowrap">
                                <?= dataBr($item['data_aba']) ?>
                                <span class="d-block text-muted"><i class="fa-solid fa-clock"></i> <?= h(substr((string) ($item['hora_aba'] ?? ''), 0, 5)) ?: '—' ?></span>
                            </td>
                            <td class="small"><?= h((string) ($item['posto'] ?? '')) ?: '—' ?></td>
                            <td class="small text-end text-nowrap"><?= number_format((float) $item['litros'], 2, ',', '.') ?> L</td>
                            <td class="small text-end text-nowrap">R$ <?= number_format((float) $item['valor'], 2, ',', '.') ?></td>
                            <td class="small text-end text-nowrap text-muted"><?= custoPorLitro($item) ?></td>
                            <td class="small text-end text-nowrap"><?= number_format((int) $item['quilometragem'], 0, ',', '.') ?></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $item['id_aba'] ?>"><i class="fa-solid fa-eye"></i> Ver</a>
                                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int) $item['id_aba'] ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
