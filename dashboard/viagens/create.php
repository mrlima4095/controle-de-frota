<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Nova viagem';
$pageSubtitle = 'Registre um percurso realizado pela frota.';
$activeMenu = 'viagens';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function horaNormal(string $valor): string
{
    return strlen($valor) === 5 ? $valor . ':00' : $valor;
}

$veiculos = $pdo->query('SELECT id_vei, placa, modelo FROM veiculo ORDER BY placa ASC')->fetchAll();
$motoristas = $pdo->query('SELECT id_mot, nome FROM motorista ORDER BY nome ASC')->fetchAll();
$dados = [
    'id_vei' => '', 'id_mot' => '', 'data_per' => '',
    'hora_ini' => '', 'hora_fim' => '', 'origem' => '', 'destino' => '',
    'quilometragem' => '', 'observacoes' => '',
];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($dados) as $campo) {
        $dados[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }

    if ($dados['id_vei'] === '' || !ctype_digit($dados['id_vei'])) {
        $erro = 'Selecione o veículo da viagem.';
    } elseif ($dados['id_mot'] === '' || !ctype_digit($dados['id_mot'])) {
        $erro = 'Selecione o motorista da viagem.';
    } elseif ($dados['data_per'] === '') {
        $erro = 'Informe a data da viagem.';
    } elseif ($dados['hora_ini'] === '' || $dados['hora_fim'] === '') {
        $erro = 'Informe os horários de início e fim.';
    } elseif ($dados['origem'] === '' || $dados['destino'] === '') {
        $erro = 'Informe a origem e o destino da viagem.';
    } elseif (!is_numeric($dados['quilometragem']) || (int) $dados['quilometragem'] < 0) {
        $erro = 'Informe a quilometragem percorrida.';
    } else {
        $sql = 'INSERT INTO percurso
                    (id_vei, id_mot, data_per, hora_ini, hora_fim, origem, destino, quilometragem, observacoes)
                VALUES
                    (:id_vei, :id_mot, :data_per, :hora_ini, :hora_fim, :origem, :destino, :quilometragem, :observacoes)';

        try {
            $pdo->prepare($sql)->execute([
                ':id_vei' => (int) $dados['id_vei'],
                ':id_mot' => (int) $dados['id_mot'],
                ':data_per' => $dados['data_per'],
                ':hora_ini' => horaNormal($dados['hora_ini']),
                ':hora_fim' => horaNormal($dados['hora_fim']),
                ':origem' => $dados['origem'],
                ':destino' => $dados['destino'],
                ':quilometragem' => (int) $dados['quilometragem'],
                ':observacoes' => $dados['observacoes'],
            ]);
        } catch (PDOException $exception) {
            $erro = 'Não foi possível registrar a viagem. Verifique os dados selecionados.';
        }

        if ($erro === '') {
            header('Location: /dashboard/viagens/index.php?ok=1');
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
                <h2 class="h6 mb-0">Dados da viagem</h2>
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
                                        <?= h($veiculo['placa']) ?><?= $veiculo['modelo'] ? ' · ' . h($veiculo['modelo']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="id_mot" class="form-label fw-semibold small">Motorista *</label>
                            <select class="form-select" id="id_mot" name="id_mot" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($motoristas as $motorista): ?>
                                    <option value="<?= (int) $motorista['id_mot'] ?>"<?= $dados['id_mot'] === (string) $motorista['id_mot'] ? ' selected' : '' ?>><?= h($motorista['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="data_per" class="form-label fw-semibold small">Data da viagem *</label>
                            <input type="date" class="form-control" id="data_per" name="data_per" value="<?= h($dados['data_per']) ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="hora_ini" class="form-label fw-semibold small">Hora de início *</label>
                            <input type="time" class="form-control" id="hora_ini" name="hora_ini" value="<?= h($dados['hora_ini']) ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="hora_fim" class="form-label fw-semibold small">Hora de fim *</label>
                            <input type="time" class="form-control" id="hora_fim" name="hora_fim" value="<?= h($dados['hora_fim']) ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="quilometragem" class="form-label fw-semibold small">Quilometragem percorrida *</label>
                            <input type="number" class="form-control" id="quilometragem" name="quilometragem" value="<?= h($dados['quilometragem']) ?>" min="0" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="origem" class="form-label fw-semibold small">Origem *</label>
                            <input type="text" class="form-control" id="origem" name="origem" value="<?= h($dados['origem']) ?>" maxlength="200" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="destino" class="form-label fw-semibold small">Destino *</label>
                            <input type="text" class="form-control" id="destino" name="destino" value="<?= h($dados['destino']) ?>" maxlength="200" required>
                        </div>

                        <div class="col-12">
                            <label for="observacoes" class="form-label fw-semibold small">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="2" maxlength="1000"><?= h($dados['observacoes']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                        <button type="submit" class="btn btn-brand">Registrar viagem</button>
                        <a class="btn btn-outline-secondary" href="index.php">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
