<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Novo abastecimento';
$pageSubtitle = 'Registre o combustível fornecido a um veículo da frota.';
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

$combustiveis = ['flex', 'gasolina', 'diesel', 'eletrico'];
$veiculos = $pdo->query('SELECT id_vei, placa, modelo FROM veiculo ORDER BY placa ASC')->fetchAll();

$dados = [
    'id_vei' => '',
    'data_aba' => date('Y-m-d'),
    'hora_aba' => date('H:i'),
    'posto' => '',
    'litros' => '',
    'valor' => '',
    'quilometragem' => '',
    'tipo_combustivel' => 'flex',
    'observacoes' => '',
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
        $sql = 'INSERT INTO abastecimento
                    (id_vei, data_aba, hora_aba, posto, litros, valor,
                     quilometragem, tipo_combustivel, observacoes)
                VALUES
                    (:id_vei, :data_aba, :hora_aba, :posto, :litros, :valor,
                     :quilometragem, :tipo_combustivel, :observacoes)';

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
            ]);
        } catch (PDOException $exception) {
            $erro = 'Não foi possível cadastrar o abastecimento. Verifique os dados e tente novamente.';
        }

        if ($erro === '') {
            header('Location: /dashboard/abastecimentos/index.php?ok=1');
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
            <div class="card-header bg-white py-3">
                <h2 class="h6 mb-0">Dados do abastecimento</h2>
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
                            <input type="number" class="form-control" id="litros" name="litros" value="<?= h($dados['litros']) ?>" min="0.01" step="0.01" placeholder="0,00" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="valor" class="form-label fw-semibold small">Valor (R$) *</label>
                            <input type="number" class="form-control" id="valor" name="valor" value="<?= h($dados['valor']) ?>" min="0.01" step="0.01" placeholder="0,00" required>
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
                        <button type="submit" class="btn btn-brand">Cadastrar abastecimento</button>
                        <a class="btn btn-outline-secondary" href="index.php">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
