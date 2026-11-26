<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Detalhes do documento';
$pageSubtitle = 'Informações completas do documento e sua validade.';
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

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT d.*, v.placa, v.modelo AS veiculo_modelo
     FROM documento d
     INNER JOIN veiculo v ON v.id_vei = d.id_vei
     WHERE d.id_doc = :id LIMIT 1'
);
$stmt->execute([':id' => $id]);
$documento = $stmt->fetch();

if (!$documento) {
    header('Location: /dashboard/documentos/index.php');
    exit;
}

$dias = diasParaVencimento($documento['data_validade'] ?: null);

[$badgeValidade, $situacao] = $dias === null
    ? ['text-bg-secondary', 'Sem validade']
    : match (true) {
        $dias < 0 => ['text-bg-danger', 'Vencida'],
        $dias <= 30 => ['text-bg-warning', 'Vence em breve'],
        default => ['text-bg-success', 'Válida'],
    };

$tipo = ucfirst(str_replace('_', ' ', (string) ($documento['tipo'] ?? 'outro')));
$arquivo = (string) ($documento['arquivo'] ?? '');

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Documento atualizado com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 mb-1"><i class="fa-solid fa-file-lines"></i> <?= h($tipo) ?> #<?= $id ?> <span class="badge rounded-pill <?= $badgeValidade ?>"><?= h($situacao) ?></span></h2>
        <p class="text-muted small mb-0"><?= h($documento['placa']) ?> · <?= h((string) ($documento['numero'] ?? '')) ?: 'Sem número informado' ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="index.php"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
        <a class="btn btn-brand" href="edit.php?id=<?= $id ?>"><i class="fa-solid fa-pen"></i> Editar</a>
        <?php if (podeExcluirDocumentos($usuarioLogado['perfil'])): ?>
            <form class="d-inline" method="post" action="excluir.php" onsubmit="return confirm('Excluir este documento?');">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-outline-danger"><i class="fa-solid fa-trash"></i> Excluir</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-calendar"></i> Validade</small>
            <strong class="fs-6 d-block"><?= dataBr($documento['data_validade'] ?: null) ?></strong>
            <small class="text-muted"><?= h($situacao) ?></small>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-hourglass-half"></i> Dias para vencimento</small>
            <strong class="fs-4"><?= $dias === null ? '—' : ($dias < 0 ? abs($dias) . ' vencido(s)' : $dias) ?></strong>
            <?php if ($dias !== null): ?><small class="text-muted"><?= $dias < 0 ? 'dias após o vencimento' : 'dias restantes' ?></small><?php endif; ?>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-file-lines"></i> Tipo</small>
            <strong class="fs-5 d-block text-truncate"><?= h($tipo) ?></strong>
            <small class="text-muted text-truncate d-block"><?= h((string) ($documento['numero'] ?? '')) ?: '—' ?></small>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <small class="text-muted d-block"><i class="fa-solid fa-car"></i> Veículo</small>
            <strong class="fs-5 d-block text-truncate"><?= h($documento['placa']) ?></strong>
            <small class="text-muted text-truncate d-block"><?= h((string) ($documento['veiculo_modelo'] ?? '')) ?: '—' ?></small>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Ficha do documento</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhas = [
                    ['Veículo', $documento['placa']],
                    ['Tipo', $tipo],
                    ['Número', $documento['numero']],
                    ['Descrição', $documento['descricao']],
                    ['Data de emissão', dataBr($documento['data_emissao'] ?: null)],
                    ['Data de validade', dataBr($documento['data_validade'] ?: null)],
                    ['Arquivo', $arquivo],
                ];
                foreach ($linhas as [$rotulo, $valor]): ?>
                    <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                        <strong class="small"><?= h($rotulo) ?></strong>
                        <span class="small text-sm-end text-break"><?= h((string) ($valor ?? '')) ?: '—' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="col-12 col-lg-5">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Situação e observações</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhasSituacao = [
                    ['Situação', $situacao],
                    ['Dias para vencimento', $dias === null ? '—' : (string) $dias],
                    ['Cadastrado em', dataBr($documento['data_criacao'] ?: null)],
                    ['Observações', $documento['observacoes']],
                ];
                foreach ($linhasSituacao as [$rotulo, $valor]): ?>
                    <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                        <strong class="small"><?= h($rotulo) ?></strong>
                        <span class="small text-sm-end text-break"><?= h((string) ($valor ?? '')) ?: '—' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
