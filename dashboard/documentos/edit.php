<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Editar documento';
$pageSubtitle = 'Atualize os dados do documento.';
$activeMenu = 'documentos';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM documento WHERE id_doc = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$documento = $stmt->fetch();

if (!$documento) {
    header('Location: /dashboard/documentos/index.php');
    exit;
}

$tipos = ['crlv', 'licenciamento', 'seguro', 'ipva', 'vistoria', 'outro'];

$veiculos = $pdo->query('SELECT id_vei, placa, modelo FROM veiculo ORDER BY placa ASC')->fetchAll();

$dados = [
    'id_vei' => (string) ($documento['id_vei'] ?? ''),
    'tipo' => (string) ($documento['tipo'] ?? 'outro'),
    'numero' => (string) ($documento['numero'] ?? ''),
    'descricao' => (string) ($documento['descricao'] ?? ''),
    'data_emissao' => (string) ($documento['data_emissao'] ?? ''),
    'data_validade' => (string) ($documento['data_validade'] ?? ''),
    'arquivo' => (string) ($documento['arquivo'] ?? ''),
    'observacoes' => (string) ($documento['observacoes'] ?? ''),
];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($dados) as $campo) {
        $dados[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }

    if (!in_array($dados['tipo'], $tipos, true)) {
        $dados['tipo'] = 'outro';
    }

    if ($dados['id_vei'] === '' || !ctype_digit($dados['id_vei'])) {
        $erro = 'Selecione o veículo do documento.';
    } elseif ($dados['data_emissao'] !== '' && !strtotime($dados['data_emissao'])) {
        $erro = 'Informe uma data de emissão válida.';
    } elseif ($dados['data_validade'] !== '' && !strtotime($dados['data_validade'])) {
        $erro = 'Informe uma data de validade válida.';
    } elseif ($dados['data_emissao'] !== '' && $dados['data_validade'] !== ''
        && strtotime($dados['data_validade']) < strtotime($dados['data_emissao'])) {
        $erro = 'A data de validade não pode ser anterior à data de emissão.';
    } else {
        $sql = 'UPDATE documento SET
                    id_vei = :id_vei, tipo = :tipo, numero = :numero, descricao = :descricao,
                    data_emissao = :data_emissao, data_validade = :data_validade,
                    arquivo = :arquivo, observacoes = :observacoes
                WHERE id_doc = :id';

        try {
            $pdo->prepare($sql)->execute([
                ':id_vei' => (int) $dados['id_vei'],
                ':tipo' => $dados['tipo'],
                ':numero' => $dados['numero'] ?: null,
                ':descricao' => $dados['descricao'] ?: null,
                ':data_emissao' => $dados['data_emissao'] ?: null,
                ':data_validade' => $dados['data_validade'] ?: null,
                ':arquivo' => $dados['arquivo'] ?: null,
                ':observacoes' => $dados['observacoes'] ?: null,
                ':id' => $id,
            ]);
        } catch (PDOException $exception) {
            $erro = 'Não foi possível salvar o documento. Tente novamente.';
        }

        if ($erro === '') {
            header('Location: /dashboard/documentos/view.php?id=' . $id . '&ok=1');
            exit;
        }
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<?php if ($erro !== ''): ?>
    <div class="alert alert-danger" role="alert"><?= h($erro) ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between gap-2">
                <h2 class="h6 mb-0">Editando documento #<?= $id ?></h2>
                <a class="btn btn-sm btn-outline-secondary" href="view.php?id=<?= $id ?>"><i class="fa-solid fa-eye"></i> Ver detalhes</a>
            </div>
            <div class="card-body p-3 p-sm-4">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="id_vei" class="form-label fw-semibold small">Veículo *</label>
                            <select class="form-select" id="id_vei" name="id_vei" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($veiculos as $veiculo): ?>
                                    <option value="<?= (int) $veiculo['id_vei'] ?>"<?= $dados['id_vei'] === (string) $veiculo['id_vei'] ? ' selected' : '' ?>><?= h($veiculo['placa']) ?> — <?= h((string) ($veiculo['modelo'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="tipo" class="form-label fw-semibold small">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <?php foreach ($tipos as $tipo): ?>
                                    <option value="<?= $tipo ?>"<?= $dados['tipo'] === $tipo ? ' selected' : '' ?>><?= ucfirst($tipo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="numero" class="form-label fw-semibold small">Número</label>
                            <input type="text" class="form-control" id="numero" name="numero" value="<?= h($dados['numero']) ?>" maxlength="100">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="descricao" class="form-label fw-semibold small">Descrição</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" value="<?= h($dados['descricao']) ?>" maxlength="200">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="data_emissao" class="form-label fw-semibold small">Data de emissão</label>
                            <input type="date" class="form-control" id="data_emissao" name="data_emissao" value="<?= h($dados['data_emissao']) ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="data_validade" class="form-label fw-semibold small">Data de validade</label>
                            <input type="date" class="form-control" id="data_validade" name="data_validade" value="<?= h($dados['data_validade']) ?>">
                        </div>

                        <div class="col-12">
                            <label for="arquivo" class="form-label fw-semibold small">Arquivo (caminho ou endereço)</label>
                            <input type="text" class="form-control" id="arquivo" name="arquivo" value="<?= h($dados['arquivo']) ?>" maxlength="255" placeholder="documentos/crlv-abc1234.pdf">
                        </div>
                        <div class="col-12">
                            <label for="observacoes" class="form-label fw-semibold small">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= h($dados['observacoes']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                        <button type="submit" class="btn btn-brand">Salvar alterações</button>
                        <a class="btn btn-outline-secondary" href="index.php">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
