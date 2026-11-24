<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Nova manutenção';
$pageSubtitle = 'Registre um serviço de manutenção realizado na frota.';
$activeMenu = 'manutencao';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function dataValida(string $valor): bool
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1;
}

$tipos = ['preventiva', 'corretiva', 'revisao'];
$veiculos = $pdo->query('SELECT id_vei, placa, modelo FROM veiculo ORDER BY placa ASC')->fetchAll();

$dados = [
    'id_vei' => '',
    'tipo' => 'preventiva',
    'data_man' => date('Y-m-d'),
    'oficina' => '',
    'servico' => '',
    'custo' => '0',
    'quilometragem' => '',
    'observacoes' => '',
    'proxima_data' => '',
];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($dados) as $campo) {
        $dados[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }

    if (!in_array($dados['tipo'], $tipos, true)) {
        $dados['tipo'] = 'preventiva';
    }
    if ($dados['custo'] === '') {
        $dados['custo'] = '0';
    }

    if ($dados['id_vei'] === '' || !ctype_digit($dados['id_vei'])) {
        $erro = 'Selecione o veículo da manutenção.';
    } elseif (!dataValida($dados['data_man'])) {
        $erro = 'Informe a data da manutenção.';
    } elseif (!is_numeric($dados['custo']) || (float) $dados['custo'] < 0) {
        $erro = 'Informe um custo válido para a manutenção.';
    } elseif ($dados['quilometragem'] !== '' && (!is_numeric($dados['quilometragem']) || (int) $dados['quilometragem'] < 0)) {
        $erro = 'Informe uma quilometragem válida.';
    } elseif ($dados['proxima_data'] !== '' && !dataValida($dados['proxima_data'])) {
        $erro = 'Informe uma próxima data válida.';
    } else {
        $sql = 'INSERT INTO manutencao
                    (id_vei, tipo, data_man, oficina, servico, custo,
                     quilometragem, observacoes, proxima_data)
                VALUES
                    (:id_vei, :tipo, :data_man, :oficina, :servico, :custo,
                     :quilometragem, :observacoes, :proxima_data)';

        try {
            $pdo->prepare($sql)->execute([
                ':id_vei' => (int) $dados['id_vei'],
                ':tipo' => $dados['tipo'],
                ':data_man' => $dados['data_man'],
                ':oficina' => $dados['oficina'] !== '' ? $dados['oficina'] : null,
                ':servico' => $dados['servico'] !== '' ? $dados['servico'] : null,
                ':custo' => (float) $dados['custo'],
                ':quilometragem' => $dados['quilometragem'] !== '' ? (int) $dados['quilometragem'] : null,
                ':observacoes' => $dados['observacoes'] !== '' ? $dados['observacoes'] : null,
                ':proxima_data' => $dados['proxima_data'] !== '' ? $dados['proxima_data'] : null,
            ]);
        } catch (PDOException $exception) {
            $erro = 'Não foi possível cadastrar a manutenção. Verifique os dados e tente novamente.';
        }

        if ($erro === '') {
            header('Location: /dashboard/manutencao/index.php?ok=1');
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
                <h2 class="h6 mb-0">Dados da manutenção</h2>
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
                            <label for="tipo" class="form-label fw-semibold small">Tipo *</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <?php foreach ($tipos as $tipo): ?>
                                    <option value="<?= $tipo ?>"<?= $dados['tipo'] === $tipo ? ' selected' : '' ?>><?= ucfirst($tipo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="data_man" class="form-label fw-semibold small">Data *</label>
                            <input type="date" class="form-control" id="data_man" name="data_man" value="<?= h($dados['data_man']) ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="oficina" class="form-label fw-semibold small">Oficina</label>
                            <input type="text" class="form-control" id="oficina" name="oficina" value="<?= h($dados['oficina']) ?>" maxlength="200" placeholder="Nome da oficina">
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="custo" class="form-label fw-semibold small">Custo (R$)</label>
                            <input type="number" class="form-control" id="custo" name="custo" value="<?= h($dados['custo']) ?>" min="0" step="0.01" placeholder="0,00">
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="quilometragem" class="form-label fw-semibold small">Quilometragem</label>
                            <input type="number" class="form-control" id="quilometragem" name="quilometragem" value="<?= h($dados['quilometragem']) ?>" min="0">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="servico" class="form-label fw-semibold small">Serviço executado</label>
                            <textarea class="form-control" id="servico" name="servico" rows="3"><?= h($dados['servico']) ?></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="observacoes" class="form-label fw-semibold small">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= h($dados['observacoes']) ?></textarea>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="proxima_data" class="form-label fw-semibold small">Próxima data</label>
                            <input type="date" class="form-control" id="proxima_data" name="proxima_data" value="<?= h($dados['proxima_data']) ?>">
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                        <button type="submit" class="btn btn-brand">Cadastrar manutenção</button>
                        <a class="btn btn-outline-secondary" href="index.php">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
