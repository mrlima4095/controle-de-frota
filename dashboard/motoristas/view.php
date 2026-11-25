<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Detalhes do motorista';
$pageSubtitle = 'Informações completas do condutor.';
$activeMenu = 'motoristas';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function dataBr(?string $valor): string
{
    return $valor ? date('d/m/Y', (int) strtotime($valor)) : '—';
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM motorista WHERE id_mot = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$motorista = $stmt->fetch();

if (!$motorista) {
    header('Location: /dashboard/motoristas/index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM percurso WHERE id_mot = :id');
$stmt->execute([':id' => $id]);
$totalViagens = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM multa WHERE id_mot = :id');
$stmt->execute([':id' => $id]);
$totalMultas = (int) $stmt->fetchColumn();

$badgeCnh = '<span class="badge rounded-pill text-bg-secondary">Sem CNH</span>';
if ($motorista['validade_cnh']) {
    $timestamp = (int) strtotime($motorista['validade_cnh']);
    if ($timestamp < time()) {
        $badgeCnh = '<span class="badge rounded-pill text-bg-danger">Vencida em ' . dataBr($motorista['validade_cnh']) . '</span>';
    } elseif ($timestamp <= strtotime('+60 days')) {
        $badgeCnh = '<span class="badge rounded-pill text-bg-warning">Vence em ' . dataBr($motorista['validade_cnh']) . '</span>';
    } else {
        $badgeCnh = '<span class="badge rounded-pill text-bg-success">Válida até ' . dataBr($motorista['validade_cnh']) . '</span>';
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Motorista atualizado com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 mb-1"><?= h($motorista['nome']) ?></h2>
        <p class="text-muted small mb-2">CPF <?= h($motorista['cpf']) ?></p>
        <?= $badgeCnh ?>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="index.php">← Voltar</a>
        <a class="btn btn-brand" href="edit.php?id=<?= $id ?>"><i class="fa-solid fa-pen"></i> Editar</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-road"></i> Viagens</small>
            <strong class="fs-4"><?= $totalViagens ?></strong>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-triangle-exclamation"></i> Multas</small>
            <strong class="fs-4"><?= $totalMultas ?></strong>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Dados pessoais</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhas = [
                    ['Nome', $motorista['nome']],
                    ['CPF', $motorista['cpf']],
                    ['CNH', $motorista['cnh']],
                    ['Validade da CNH', dataBr($motorista['validade_cnh'])],
                    ['Telefone', $motorista['telefone']],
                    ['E-mail', $motorista['email']],
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
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Localização e situação</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhasSituacao = [
                    ['Cidade', $motorista['cidade']],
                    ['UF', $motorista['uf']],
                    ['Situação', $motorista['ativo'] ? 'Ativo' : 'Inativo'],
                    ['Cadastrado em', dataBr($motorista['data_criacao'])],
                ];
                foreach ($linhasSituacao as [$rotulo, $valor]): ?>
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
