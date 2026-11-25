<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Editar multa';
$pageSubtitle = 'Atualize os dados da infração registrada.';
$activeMenu = 'sinistros';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function moeda(float $v): string
{
    return 'R$ ' . number_format($v, 2, ',', '.');
}

function horaNormal(string $valor): string
{
    return strlen($valor) === 5 ? $valor . ':00' : $valor;
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM multa WHERE id_mul = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$multa = $stmt->fetch();

if (!$multa) {
    header('Location: /dashboard/sinistros/index.php');
    exit;
}

$statusLista = ['pendente', 'paga', 'cancelada'];

$veiculos = $pdo->query('SELECT id_vei, placa, modelo FROM veiculo ORDER BY placa ASC')->fetchAll();
$motoristas = $pdo->query('SELECT id_mot, nome FROM motorista ORDER BY nome ASC')->fetchAll();

$dados = [
    'id_vei' => (string) $multa['id_vei'],
    'id_mot' => (string) ($multa['id_mot'] ?? ''),
    'data_infra' => (string) $multa['data_infra'],
    'hora_infra' => substr((string) ($multa['hora_infra'] ?? ''), 0, 5),
    'local' => (string) ($multa['local'] ?? ''),
    'infracao' => (string) $multa['infracao'],
    'valor_multa' => (string) $multa['valor_multa'],
    'pontos' => (string) ($multa['pontos'] ?? 0),
    'status' => (string) $multa['status'],
    'data_pagamento' => (string) ($multa['data_pagamento'] ?? ''),
    'observacoes' => (string) ($multa['observacoes'] ?? ''),
];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($dados) as $campo) {
        $dados[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }

    if (!in_array($dados['status'], $statusLista, true)) {
        $dados['status'] = 'pendente';
    }

    if ($dados['id_vei'] === '' || !ctype_digit($dados['id_vei'])) {
        $erro = 'Selecione o veículo da infração.';
    } elseif ($dados['id_mot'] !== '' && !ctype_digit($dados['id_mot'])) {
        $erro = 'Motorista inválido.';
    } elseif ($dados['data_infra'] === '') {
        $erro = 'Informe a data da infração.';
    } elseif ($dados['infracao'] === '') {
        $erro = 'Informe a infração cometida.';
    } elseif ($dados['valor_multa'] === '' || !is_numeric($dados['valor_multa']) || (float) $dados['valor_multa'] < 0) {
        $erro = 'Informe um valor válido para a multa.';
    } elseif ($dados['pontos'] === '' || !is_numeric($dados['pontos']) || (int) $dados['pontos'] < 0) {
        $erro = 'Informe uma quantidade válida de pontos.';
    } else {
        if ($dados['status'] === 'paga' && $dados['data_pagamento'] === '') {
            $dados['data_pagamento'] = date('Y-m-d');
        }

        $sql = 'UPDATE multa SET
                    id_vei = :id_vei, id_mot = :id_mot, data_infra = :data_infra,
                    hora_infra = :hora_infra, `local` = :local, infracao = :infracao,
                    valor_multa = :valor_multa, pontos = :pontos, status = :status,
                    data_pagamento = :data_pagamento, observacoes = :observacoes
                WHERE id_mul = :id';

        try {
            $pdo->prepare($sql)->execute([
                ':id_vei' => (int) $dados['id_vei'],
                ':id_mot' => $dados['id_mot'] !== '' ? (int) $dados['id_mot'] : null,
                ':data_infra' => $dados['data_infra'],
                ':hora_infra' => $dados['hora_infra'] !== '' ? horaNormal($dados['hora_infra']) : null,
                ':local' => $dados['local'] !== '' ? $dados['local'] : null,
                ':infracao' => $dados['infracao'],
                ':valor_multa' => number_format((float) $dados['valor_multa'], 2, '.', ''),
                ':pontos' => (int) $dados['pontos'],
                ':status' => $dados['status'],
                ':data_pagamento' => $dados['data_pagamento'] !== '' ? $dados['data_pagamento'] : null,
                ':observacoes' => $dados['observacoes'] !== '' ? $dados['observacoes'] : null,
                ':id' => $id,
            ]);
        } catch (PDOException $exception) {
            $erro = 'Não foi possível salvar a multa. Tente novamente.';
        }

        if ($erro === '') {
            header('Location: /dashboard/sinistros/view.php?id=' . $id . '&ok=1');
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
                <h2 class="h6 mb-0">Editando multa #<?= $id ?></h2>
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
                                    <option value="<?= (int) $veiculo['id_vei'] ?>"<?= $dados['id_vei'] !== '' && (int) $dados['id_vei'] === (int) $veiculo['id_vei'] ? ' selected' : '' ?>>
                                        <?= h($veiculo['placa']) ?> · <?= h((string) ($veiculo['modelo'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="id_mot" class="form-label fw-semibold small">Motorista (opcional)</label>
                            <select class="form-select" id="id_mot" name="id_mot">
                                <option value="">Não informado</option>
                                <?php foreach ($motoristas as $motorista): ?>
                                    <option value="<?= (int) $motorista['id_mot'] ?>"<?= $dados['id_mot'] !== '' && (int) $dados['id_mot'] === (int) $motorista['id_mot'] ? ' selected' : '' ?>>
                                        <?= h($motorista['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-md-4">
                            <label for="data_infra" class="form-label fw-semibold small">Data da infração *</label>
                            <input type="date" class="form-control" id="data_infra" name="data_infra" value="<?= h($dados['data_infra']) ?>" required>
                        </div>
                        <div class="col-6 col-md-4">
                            <label for="hora_infra" class="form-label fw-semibold small">Hora da infração</label>
                            <input type="time" class="form-control" id="hora_infra" name="hora_infra" value="<?= h($dados['hora_infra']) ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="status" class="form-label fw-semibold small">Status</label>
                            <select class="form-select" id="status" name="status">
                                <?php foreach ($statusLista as $opcao): ?>
                                    <option value="<?= $opcao ?>"<?= $dados['status'] === $opcao ? ' selected' : '' ?>><?= ucfirst($opcao) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="infracao" class="form-label fw-semibold small">Infração *</label>
                            <input type="text" class="form-control" id="infracao" name="infracao" value="<?= h($dados['infracao']) ?>" maxlength="200" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="local" class="form-label fw-semibold small">Local</label>
                            <input type="text" class="form-control" id="local" name="local" value="<?= h($dados['local']) ?>" maxlength="200">
                        </div>

                        <div class="col-6 col-md-4">
                            <label for="valor_multa" class="form-label fw-semibold small">Valor da multa *</label>
                            <input type="number" class="form-control" id="valor_multa" name="valor_multa" value="<?= h($dados['valor_multa']) ?>" min="0" step="0.01" required>
                        </div>
                        <div class="col-6 col-md-4">
                            <label for="pontos" class="form-label fw-semibold small">Pontos</label>
                            <input type="number" class="form-control" id="pontos" name="pontos" value="<?= h($dados['pontos']) ?>" min="0" step="1">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="data_pagamento" class="form-label fw-semibold small">Data de pagamento</label>
                            <input type="date" class="form-control" id="data_pagamento" name="data_pagamento" value="<?= h($dados['data_pagamento']) ?>">
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
