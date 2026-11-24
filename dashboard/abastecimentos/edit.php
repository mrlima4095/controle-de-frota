<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Editar abastecimento';
$pageSubtitle = 'Atualize os dados do abastecimento registrado.';
$activeMenu = 'abastecimentos';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function horaNormal(mixed $valor): string
{
    $hora = trim((string) ($valor ?? ''));

    return strlen($hora) === 5 ? $hora . ':00' : $hora;
}

function dataValida(string $valor): bool
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1;
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT a.*, v.placa, v.modelo
     FROM abastecimento a
     INNER JOIN veiculo v ON v.id_vei = a.id_vei
     WHERE a.id_aba = :id LIMIT 1'
);
$stmt->execute([':id' => $id]);
$registro = $stmt->fetch();

if (!$registro) {
    header('Location: /dashboard/abastecimentos/index.php');
    exit;
}

$combustiveis = ['flex', 'gasolina', 'diesel', 'eletrico'];
$veiculos = $pdo->query('SELECT id_vei, placa, modelo FROM veiculo ORDER BY placa ASC')->fetchAll();

$dados = [
    'id_vei' => (string) $registro['id_vei'],
    'data_aba' => (string) $registro['data_aba'],
    'hora_aba' => substr((string) $registro['hora_aba'], 0, 5),
    'posto' => (string) ($registro['posto'] ?? ''),
    'litros' => (string) $registro['litros'],
    'valor' => (string) $registro['valor'],
    'quilometragem' => (string) $registro['quilometragem'],
    'tipo_combustivel' => (string) ($registro['tipo_combustivel'] ?? 'flex'),
    'observacoes' => (string) ($registro['observacoes'] ?? ''),
];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($dados) as $campo) {
        $dados[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }

    if (!in_array($dados['tipo_combustivel'], $combustiveis, true)) {
        $dados['tipo_combustivel'] = 'flex';
    }

    if ($dados['id_vei'] === '' || !ctype_digit($dados['id_vei'])) {
        $erro = 'Selecione o veículo abastecido.';
    } elseif (!dataValida($dados['data_aba'])) {
        $erro = 'Informe a data do abastecimento.';
    } elseif ($dados['hora_aba'] === '') {
        $erro = 'Informe a hora do abastecimento.';
    } elseif (!is_numeric($dados['litros']) || (float) $dados['litros'] <= 0) {
        $erro = 'Informe a quantidade de litros abastecida.';
    } elseif (!is_numeric($dados['valor']) || (float) $dados['valor'] <= 0) {
        $erro = 'Informe o valor total do abastecimento.';
    } elseif (!is_numeric($dados['quilometragem']) || (int) $dados['quilometragem'] < 0) {
        $erro = 'Informe a quilometragem do veículo no abastecimento.';
    } else {
        $sql = 'UPDATE abastecimento SET
                    id_vei = :id_vei, data_aba = :data_aba, hora_aba = :hora_aba,
                    posto = :posto, litros = :litros, valor = :valor,
                    quilometragem = :quilometragem, tipo_combustivel = :tipo_combustivel,
                    observacoes = :observacoes
                WHERE id_aba = :id';

        try {
            $pdo->prepare($sql)->execute([
                ':id_vei' => (int) $dados['id_vei'],
                ':data_aba' => $dados['data_aba'],
                ':hora_aba' => horaNormal($dados['hora_aba']),
                ':posto' => $dados['posto'] !== '' ? $dados['posto'] : null,
                ':litros' => (float) $dados['litros'],
                ':valor' => (float) $dados['valor'],
                ':quilometragem' => (int) $dados['quilometragem'],
                ':tipo_combustivel' => $dados['tipo_combustivel'],
                ':observacoes' => $dados['observacoes'] !== '' ? $dados['observacoes'] : null,
                ':id' => $id,
            ]);
        } catch (PDOException $exception) {
            $erro = 'Não foi possível salvar o abastecimento. Tente novamente.';
        }

        if ($erro === '') {
            header('Location: /dashboard/abastecimentos/view.php?id=' . $id . '&ok=1');
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
                <h2 class="h6 mb-0">Editando abastecimento #<?= $id ?></h2>
                <a class="btn btn-sm btn-outline-secondary" href="view.php?id=<?= $id ?>">👁️ Ver detalhes</a>
            </div>
            <div class="card-body p-3 p-sm-4">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="id_vei" class="form-label fw-semibold small">Veículo *</label>
                            <select class="form-select" id="id_vei" name="id_vei" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($veiculos as $veiculo): ?>
                                    <option value="<?= (int) $veiculo['id_vei'] ?>"<?= $dados['id_vei'] === (string) $veiculo['id_vei'] ? ' selected' : '' ?>>
                                        <?= h($veiculo['placa']) ?> — <?= h((string) ($veiculo['modelo'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="data_aba" class="form-label fw-semibold small">Data *</label>
                            <input type="date" class="form-control" id="data_aba" name="data_aba" value="<?= h($dados['data_aba']) ?>" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="hora_aba" class="form-label fw-semibold small">Hora *</label>
                            <input type="time" class="form-control" id="hora_aba" name="hora_aba" value="<?= h($dados['hora_aba']) ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="posto" class="form-label fw-semibold small">Posto</label>
                            <input type="text" class="form-control" id="posto" name="posto" value="<?= h($dados['posto']) ?>" maxlength="200" placeholder="Nome do posto">
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="litros" class="form-label fw-semibold small">Litros *</label>
                            <input type="number" class="form-control" id="litros" name="litros" value="<?= h($dados['litros']) ?>" min="0.01" step="0.01" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="valor" class="form-label fw-semibold small">Valor (R$) *</label>
                            <input type="number" class="form-control" id="valor" name="valor" value="<?= h($dados['valor']) ?>" min="0.01" step="0.01" required>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="quilometragem" class="form-label fw-semibold small">Quilometragem *</label>
                            <input type="number" class="form-control" id="quilometragem" name="quilometragem" value="<?= h($dados['quilometragem']) ?>" min="0" required>
                        </div>
                        <div class="col-6 col-md-4">
                            <label for="tipo_combustivel" class="form-label fw-semibold small">Tipo de combustível</label>
                            <select class="form-select" id="tipo_combustivel" name="tipo_combustivel">
                                <?php foreach ($combustiveis as $combustivel): ?>
                                    <option value="<?= $combustivel ?>"<?= $dados['tipo_combustivel'] === $combustivel ? ' selected' : '' ?>><?= ucfirst($combustivel) ?></option>
                                <?php endforeach; ?>
                            </select>
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
