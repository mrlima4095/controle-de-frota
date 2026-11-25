<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Documentos';
$pageSubtitle = 'Acompanhe a validade dos documentos da frota.';
$activeMenu = 'documentos';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function dataBr(?string $valor): string
{
    return $valor ? date('d/m/Y', (int) strtotime($valor)) : '—';
}

function diasParaVencimento(?string $valor): ?int
{
    if (!$valor) {
        return null;
    }

    $timestamp = strtotime($valor);
    if ($timestamp === false) {
        return null;
    }

    return (int) ceil(($timestamp - strtotime('today')) / 86400);
}

function validade(?string $valor): array
{
    $dias = diasParaVencimento($valor);

    if ($dias === null) {
        return ['text-bg-secondary', 'Sem validade'];
    }
    if ($dias < 0) {
        return ['text-bg-danger', 'Vencida'];
    }
    if ($dias <= 30) {
        return ['text-bg-warning', 'Vence em ' . $dias . ' dia(s)'];
    }

    return ['text-bg-success', 'Válida'];
}

$busca = trim((string) ($_GET['q'] ?? ''));

$sql = 'SELECT d.*, v.placa
        FROM documento d
        INNER JOIN veiculo v ON v.id_vei = d.id_vei';
$parametros = [];

if ($busca !== '') {
    $sql .= ' WHERE v.placa LIKE :q1 OR d.numero LIKE :q2 OR d.descricao LIKE :q3 OR d.tipo LIKE :q4';
    $parametros[':q1'] = '%' . $busca . '%';
    $parametros[':q2'] = '%' . $busca . '%';
    $parametros[':q3'] = '%' . $busca . '%';
    $parametros[':q4'] = '%' . $busca . '%';
}

$sql .= ' ORDER BY d.data_validade ASC, v.placa ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$documentos = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Documento salvo com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
    <div>
        <h2 class="h5 mb-1">Documentos cadastrados</h2>
        <p class="text-muted small mb-0"><?= count($documentos) ?> documento(s) encontrado(s).</p>
    </div>
    <a class="btn btn-brand" href="create.php"><i class="fa-solid fa-plus"></i> Novo documento</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-sm-8 col-lg-6">
        <input type="search" class="form-control" name="q" value="<?= h($busca) ?>" placeholder="Buscar por placa, tipo, número ou descrição">
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
                    <th scope="col">Tipo</th>
                    <th scope="col">Número</th>
                    <th scope="col">Descrição</th>
                    <th scope="col">Emissão</th>
                    <th scope="col">Validade</th>
                    <th scope="col">Situação</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$documentos): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Nenhum documento encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($documentos as $documento): ?>
                        <?php [$classeValidade, $rotuloValidade] = validade($documento['data_validade'] ?: null); ?>
                        <tr>
                            <td><strong><?= h((string) $documento['placa']) ?></strong></td>
                            <td class="small text-capitalize"><?= h(ucfirst(str_replace('_', ' ', (string) ($documento['tipo'] ?? 'outro')))) ?></td>
                            <td class="small"><?= h((string) ($documento['numero'] ?? '')) ?: '—' ?></td>
                            <td class="small text-muted"><?= h((string) ($documento['descricao'] ?? '')) ?: '—' ?></td>
                            <td class="small text-muted text-nowrap"><?= dataBr($documento['data_emissao'] ?: null) ?></td>
                            <td class="small text-nowrap"><?= dataBr($documento['data_validade'] ?: null) ?></td>
                            <td><span class="badge rounded-pill <?= $classeValidade ?>"><?= h($rotuloValidade) ?></span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $documento['id_doc'] ?>"><i class="fa-solid fa-eye"></i> Ver</a>
                                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int) $documento['id_doc'] ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
