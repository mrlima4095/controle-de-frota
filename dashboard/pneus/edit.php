<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Editar pneu';
$pageSubtitle = 'Atualize os dados do pneu.';
$activeMenu = 'pneus';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function inteiroOuZero(mixed $valor): int
{
    return max(0, is_numeric($valor) ? (int) $valor : 0);
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM pneu WHERE id_pneu = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$pneu = $stmt->fetch();

if (!$pneu) {
    header('Location: /dashboard/pneus/index.php');
    exit;
}

$posicoes = ['dianteiro_esquerdo', 'dianteiro_direito', 'traseiro_esquerdo', 'traseiro_direito', 'estepe', 'outro'];
$statusLista = ['em_uso', 'reserva', 'desgastado', 'descartado'];

$veiculos = $pdo->query('SELECT id_vei, placa, modelo FROM veiculo ORDER BY placa ASC')->fetchAll();

$dados = [
    'id_vei' => (string) ($pneu['id_vei'] ?? ''),
    'posicao' => (string) ($pneu['posicao'] ?? 'outro'),
    'marca' => (string) ($pneu['marca'] ?? ''),
    'modelo' => (string) ($pneu['modelo'] ?? ''),
    'dimensao' => (string) ($pneu['dimensao'] ?? ''),
    'numero_serie' => (string) ($pneu['numero_serie'] ?? ''),
    'data_instalacao' => (string) ($pneu['data_instalacao'] ?? ''),
    'km_instalacao' => (string) ($pneu['km_instalacao'] ?? '0'),
    'km_atual' => (string) ($pneu['km_atual'] ?? '0'),
    'vida_util_km' => (string) ($pneu['vida_util_km'] ?? ''),
    'status' => (string) ($pneu['status'] ?? 'em_uso'),
    'observacoes' => (string) ($pneu['observacoes'] ?? ''),
];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($dados) as $campo) {
        $dados[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }

    $dados['numero_serie'] = strtoupper($dados['numero_serie']);

    if (!in_array($dados['posicao'], $posicoes, true)) {
        $dados['posicao'] = 'outro';
    }
    if (!in_array($dados['status'], $statusLista, true)) {
        $dados['status'] = 'em_uso';
    }

    if ($dados['id_vei'] === '' || !ctype_digit($dados['id_vei'])) {
        $erro = 'Selecione o veículo do pneu.';
    } elseif ($dados['data_instalacao'] !== '' && !strtotime($dados['data_instalacao'])) {
        $erro = 'Informe uma data de instalação válida.';
    } else {
        $sql = 'UPDATE pneu SET
                    id_vei = :id_vei, posicao = :posicao, marca = :marca, modelo = :modelo,
                    dimensao = :dimensao, numero_serie = :numero_serie,
                    data_instalacao = :data_instalacao, km_instalacao = :km_instalacao,
                    km_atual = :km_atual, vida_util_km = :vida_util_km,
                    status = :status, observacoes = :observacoes
                WHERE id_pneu = :id';

        try {
            $pdo->prepare($sql)->execute([
                ':id_vei' => (int) $dados['id_vei'],
                ':posicao' => $dados['posicao'],
                ':marca' => $dados['marca'] ?: null,
                ':modelo' => $dados['modelo'] ?: null,
                ':dimensao' => $dados['dimensao'] ?: null,
                ':numero_serie' => $dados['numero_serie'] ?: null,
                ':data_instalacao' => $dados['data_instalacao'] ?: null,
                ':km_instalacao' => inteiroOuZero($dados['km_instalacao']),
                ':km_atual' => inteiroOuZero($dados['km_atual']),
                ':vida_util_km' => inteiroOuZero($dados['vida_util_km']) ?: null,
                ':status' => $dados['status'],
                ':observacoes' => $dados['observacoes'] ?: null,
                ':id' => $id,
            ]);
        } catch (PDOException $exception) {
            $erro = 'Não foi possível salvar o pneu. Tente novamente.';
        }

        if ($erro === '') {
            header('Location: /dashboard/pneus/view.php?id=' . $id . '&ok=1');
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
                <h2 class="h6 mb-0">Editando pneu #<?= $id ?></h2>
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
                            <label for="posicao" class="form-label fw-semibold small">Posição</label>
                            <select class="form-select" id="posicao" name="posicao">
                                <?php foreach ($posicoes as $posicao): ?>
                                    <option value="<?= $posicao ?>"<?= $dados['posicao'] === $posicao ? ' selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $posicao)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="marca" class="form-label fw-semibold small">Marca</label>
                            <input type="text" class="form-control" id="marca" name="marca" value="<?= h($dados['marca']) ?>" maxlength="50">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="modelo" class="form-label fw-semibold small">Modelo</label>
                            <input type="text" class="form-control" id="modelo" name="modelo" value="<?= h($dados['modelo']) ?>" maxlength="50">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="dimensao" class="form-label fw-semibold small">Dimensão</label>
                            <input type="text" class="form-control" id="dimensao" name="dimensao" value="<?= h($dados['dimensao']) ?>" maxlength="30" placeholder="205/55 R16">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="numero_serie" class="form-label fw-semibold small">Nº de série</label>
                            <input type="text" class="form-control" id="numero_serie" name="numero_serie" value="<?= h($dados['numero_serie']) ?>" maxlength="50">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="data_instalacao" class="form-label fw-semibold small">Data de instalação</label>
                            <input type="date" class="form-control" id="data_instalacao" name="data_instalacao" value="<?= h($dados['data_instalacao']) ?>">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="km_instalacao" class="form-label fw-semibold small">KM na instalação</label>
                            <input type="number" class="form-control" id="km_instalacao" name="km_instalacao" value="<?= h($dados['km_instalacao']) ?>" min="0">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="km_atual" class="form-label fw-semibold small">KM atual</label>
                            <input type="number" class="form-control" id="km_atual" name="km_atual" value="<?= h($dados['km_atual']) ?>" min="0">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="vida_util_km" class="form-label fw-semibold small">Vida útil (km)</label>
                            <input type="number" class="form-control" id="vida_util_km" name="vida_util_km" value="<?= h($dados['vida_util_km']) ?>" min="0">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="status" class="form-label fw-semibold small">Status</label>
                            <select class="form-select" id="status" name="status">
                                <?php foreach ($statusLista as $status): ?>
                                    <option value="<?= $status ?>"<?= $dados['status'] === $status ? ' selected' : '' ?>><?= ucfirst($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-8">
                            <label for="observacoes" class="form-label fw-semibold small">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="2"><?= h($dados['observacoes']) ?></textarea>
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
