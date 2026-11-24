<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Motoristas';
$pageSubtitle = 'Condutores cadastrados e situação da CNH.';
$activeMenu = 'motoristas';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function badgeCnh(?string $validade): string
{
    if (!$validade) {
        return '<span class="badge rounded-pill text-bg-secondary">Sem CNH</span>';
    }

    $timestamp = (int) strtotime($validade);
    if ($timestamp < time()) {
        return '<span class="badge rounded-pill text-bg-danger">Vencida</span>';
    }
    if ($timestamp <= strtotime('+60 days')) {
        return '<span class="badge rounded-pill text-bg-warning">Vence em breve</span>';
    }
    return '<span class="badge rounded-pill text-bg-success">Válida</span>';
}

$busca = trim($_GET['q'] ?? '');

$sql = 'SELECT id_mot, nome, cpf, cnh, validade_cnh, telefone, uf, cidade, ativo FROM motorista';
$parametros = [];

if ($busca !== '') {
    $sql .= ' WHERE nome LIKE :q OR cpf LIKE :q OR cnh LIKE :q';
    $parametros[':q'] = '%' . $busca . '%';
}

$sql .= ' ORDER BY nome ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$motoristas = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">
        <?= $_GET['ok'] === 'importado' ? 'Usuário importado como motorista com sucesso.' : 'Motorista salvo com sucesso.' ?>
    </div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
    <div>
        <h2 class="h5 mb-1">Condutores cadastrados</h2>
        <p class="text-muted small mb-0"><?= count($motoristas) ?> motorista(s) encontrado(s).</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="importar.php">🔄 Importar usuário</a>
        <a class="btn btn-brand" href="create.php">➕ Novo motorista</a>
    </div>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-sm-8 col-lg-6">
        <input type="search" class="form-control" name="q" value="<?= h($busca) ?>" placeholder="Buscar por nome, CPF ou CNH">
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
                    <th scope="col">Nome</th>
                    <th scope="col">CPF</th>
                    <th scope="col">CNH</th>
                    <th scope="col">Validade</th>
                    <th scope="col">Contato</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$motoristas): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Nenhum motorista encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($motoristas as $motorista): ?>
                        <tr>
                            <td><strong><?= h($motorista['nome']) ?></strong></td>
                            <td class="small text-muted"><?= h($motorista['cpf']) ?></td>
                            <td class="small"><?= h($motorista['cnh'] ?? '—') ?></td>
                            <td>
                                <?= badgeCnh($motorista['validade_cnh']) ?>
                                <small class="d-block text-muted"><?= $motorista['validade_cnh'] ? date('d/m/Y', (int) strtotime($motorista['validade_cnh'])) : '' ?></small>
                            </td>
                            <td class="small text-muted">
                                <?= h($motorista['telefone'] ?? '—') ?>
                                <?php if ($motorista['uf']): ?><small class="d-block"><?= h($motorista['cidade'] ?? '') ?> - <?= h($motorista['uf']) ?></small><?php endif; ?>
                            </td>
                            <td><span class="badge rounded-pill <?= $motorista['ativo'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $motorista['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $motorista['id_mot'] ?>">👁️ Ver</a>
                                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int) $motorista['id_mot'] ?>">✏️ Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
