<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Editar veículo';
$pageSubtitle = 'Atualize os dados do veículo.';
$activeMenu = 'veiculos';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function inteiroOuZero(mixed $valor): int
{
    return is_numeric($valor) ? (int) $valor : 0;
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM veiculo WHERE id_vei = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$veiculo = $stmt->fetch();

if (!$veiculo) {
    header('Location: /dashboard/veiculos/index.php');
    exit;
}

$tipos = ['carro', 'moto', 'caminhao', 'van', 'onibus'];
$combustiveis = ['flex', 'gasolina', 'diesel', 'eletrico'];
$statusLista = ['disponivel', 'em_uso', 'manutencao', 'inativo'];

$departamentos = $pdo->query('SELECT id_departamento, nome FROM departamento ORDER BY nome ASC')->fetchAll();

$dados = [
    'placa' => $veiculo['placa'],
    'marca' => $veiculo['marca'],
    'modelo' => $veiculo['modelo'],
    'ano_fabricacao' => $veiculo['ano_fabricacao'],
    'ano_modelo' => $veiculo['ano_modelo'],
    'cor' => $veiculo['cor'],
    'tipo' => $veiculo['tipo'],
    'chassi' => $veiculo['chassi'],
    'combustivel' => $veiculo['combustivel'],
    'capacidade' => $veiculo['capacidade'],
    'quilometragem_inicial' => $veiculo['quilometragem_inicial'],
    'quilometragemAtual' => $veiculo['quilometragemAtual'],
    'status' => $veiculo['status'],
    'id_dep' => $veiculo['id_dep'],
    'novo_departamento' => '',
    'ativo' => $veiculo['ativo'],
];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($dados) as $campo) {
        if ($campo === 'ativo') {
            $dados[$campo] = isset($_POST['ativo']) && $_POST['ativo'] === '1' ? 1 : 0;
        } else {
            $dados[$campo] = trim((string) ($_POST[$campo] ?? ''));
        }
    }

    $dados['placa'] = strtoupper($dados['placa']);
    $dados['chassi'] = strtoupper($dados['chassi']);

    if (!in_array($dados['tipo'], $tipos, true)) {
        $dados['tipo'] = 'carro';
    }
    if (!in_array($dados['combustivel'], $combustiveis, true)) {
        $dados['combustivel'] = 'flex';
    }
    if (!in_array($dados['status'], $statusLista, true)) {
        $dados['status'] = 'disponivel';
    }

    if ($dados['novo_departamento'] !== '') {
        $nomeDepartamento = trim($dados['novo_departamento']);
        $buscaDep = $pdo->prepare('SELECT id_departamento FROM departamento WHERE nome = :nome LIMIT 1');
        $buscaDep->execute([':nome' => $nomeDepartamento]);
        $depExistente = $buscaDep->fetch();

        if ($depExistente) {
            $dados['id_dep'] = (string) $depExistente['id_departamento'];
        } else {
            try {
                $pdo->prepare('INSERT INTO departamento (nome, ativo) VALUES (:nome, 1)')->execute([':nome' => $nomeDepartamento]);
                $novoId = (int) $pdo->lastInsertId();
                $dados['id_dep'] = (string) $novoId;
                $departamentos[] = ['id_departamento' => $novoId, 'nome' => $nomeDepartamento];
            } catch (PDOException $exception) {
                $erro = 'Não foi possível criar o departamento. Tente novamente.';
            }
        }
    }

    if ($erro === '' && $dados['placa'] === '') {
        $erro = 'Informe a placa do veículo.';
    } elseif ($erro === '' && ($dados['id_dep'] === '' || !ctype_digit((string) $dados['id_dep']))) {
        $erro = 'Selecione o departamento do veículo.';
    } elseif ($erro === '') {
        $check = $pdo->prepare('SELECT id_vei FROM veiculo WHERE placa = :placa AND id_vei <> :id LIMIT 1');
        $check->execute([':placa' => $dados['placa'], ':id' => $id]);

        if ($check->fetch()) {
            $erro = 'Esta placa já está cadastrada em outro veículo.';
        } else {
            $sql = 'UPDATE veiculo SET
                        placa = :placa, marca = :marca, modelo = :modelo,
                        ano_fabricacao = :ano_fabricacao, ano_modelo = :ano_modelo, cor = :cor,
                        tipo = :tipo, chassi = :chassi, combustivel = :combustivel,
                        capacidade = :capacidade, quilometragem_inicial = :quilometragem_inicial,
                        quilometragemAtual = :quilometragemAtual, status = :status,
                        id_dep = :id_dep, ativo = :ativo
                    WHERE id_vei = :id';

            try {
                $pdo->prepare($sql)->execute([
                    ':placa' => $dados['placa'],
                    ':marca' => $dados['marca'],
                    ':modelo' => $dados['modelo'],
                    ':ano_fabricacao' => inteiroOuZero($dados['ano_fabricacao']) ?: null,
                    ':ano_modelo' => inteiroOuZero($dados['ano_modelo']) ?: null,
                    ':cor' => $dados['cor'],
                    ':tipo' => $dados['tipo'],
                    ':chassi' => $dados['chassi'],
                    ':combustivel' => $dados['combustivel'],
                    ':capacidade' => inteiroOuZero($dados['capacidade']) ?: null,
                    ':quilometragem_inicial' => inteiroOuZero($dados['quilometragem_inicial']),
                    ':quilometragemAtual' => inteiroOuZero($dados['quilometragemAtual']),
                    ':status' => $dados['status'],
                    ':id_dep' => (int) $dados['id_dep'],
                    ':ativo' => $dados['ativo'],
                    ':id' => $id,
                ]);
            } catch (PDOException $exception) {
                $erro = 'Não foi possível salvar o veículo. Tente novamente.';
            }

            if ($erro === '') {
                header('Location: /dashboard/veiculos/view.php?id=' . $id . '&ok=1');
                exit;
            }
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
                <h2 class="h6 mb-0">Editando <?= h($dados['placa']) ?></h2>
                <a class="btn btn-sm btn-outline-secondary" href="view.php?id=<?= $id ?>"><i class="fa-solid fa-eye"></i> Ver detalhes</a>
            </div>
            <div class="card-body p-3 p-sm-4">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="placa" class="form-label fw-semibold small">Placa *</label>
                            <input type="text" class="form-control" id="placa" name="placa" value="<?= h($dados['placa']) ?>" maxlength="8" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="marca" class="form-label fw-semibold small">Marca</label>
                            <input type="text" class="form-control" id="marca" name="marca" value="<?= h($dados['marca']) ?>" maxlength="50">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="modelo" class="form-label fw-semibold small">Modelo</label>
                            <input type="text" class="form-control" id="modelo" name="modelo" value="<?= h($dados['modelo']) ?>" maxlength="50">
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="ano_fabricacao" class="form-label fw-semibold small">Ano fabricação</label>
                            <input type="number" class="form-control" id="ano_fabricacao" name="ano_fabricacao" value="<?= h((string) $dados['ano_fabricacao']) ?>" min="1900" max="2100">
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="ano_modelo" class="form-label fw-semibold small">Ano modelo</label>
                            <input type="number" class="form-control" id="ano_modelo" name="ano_modelo" value="<?= h((string) $dados['ano_modelo']) ?>" min="1900" max="2100">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="cor" class="form-label fw-semibold small">Cor</label>
                            <input type="text" class="form-control" id="cor" name="cor" value="<?= h((string) $dados['cor']) ?>" maxlength="30">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="chassi" class="form-label fw-semibold small">Chassi</label>
                            <input type="text" class="form-control" id="chassi" name="chassi" value="<?= h((string) $dados['chassi']) ?>" maxlength="17">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="tipo" class="form-label fw-semibold small">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <?php foreach ($tipos as $tipo): ?>
                                    <option value="<?= $tipo ?>"<?= $dados['tipo'] === $tipo ? ' selected' : '' ?>><?= ucfirst($tipo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="combustivel" class="form-label fw-semibold small">Combustível</label>
                            <select class="form-select" id="combustivel" name="combustivel">
                                <?php foreach ($combustiveis as $combustivel): ?>
                                    <option value="<?= $combustivel ?>"<?= $dados['combustivel'] === $combustivel ? ' selected' : '' ?>><?= ucfirst($combustivel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="status" class="form-label fw-semibold small">Status</label>
                            <select class="form-select" id="status" name="status">
                                <?php foreach ($statusLista as $status): ?>
                                    <option value="<?= $status ?>"<?= $dados['status'] === $status ? ' selected' : '' ?>><?= ucfirst($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="capacidade" class="form-label fw-semibold small">Capacidade</label>
                            <input type="number" class="form-control" id="capacidade" name="capacidade" value="<?= h((string) $dados['capacidade']) ?>" min="0">
                        </div>
                        <div class="col-6 col-md-4">
                            <label for="quilometragem_inicial" class="form-label fw-semibold small">KM inicial</label>
                            <input type="number" class="form-control" id="quilometragem_inicial" name="quilometragem_inicial" value="<?= h((string) $dados['quilometragem_inicial']) ?>" min="0">
                        </div>
                        <div class="col-12 col-md-5">
                            <label for="quilometragemAtual" class="form-label fw-semibold small">KM atual</label>
                            <input type="number" class="form-control" id="quilometragemAtual" name="quilometragemAtual" value="<?= h((string) $dados['quilometragemAtual']) ?>" min="0">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="id_dep" class="form-label fw-semibold small">Departamento *</label>
                            <select class="form-select" id="id_dep" name="id_dep">
                                <option value="">Selecione...</option>
                                <?php foreach ($departamentos as $dep): ?>
                                    <option value="<?= (int) $dep['id_departamento'] ?>"<?= (string) $dados['id_dep'] === (string) $dep['id_departamento'] ? ' selected' : '' ?>><?= h($dep['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="novo_departamento" class="form-label fw-semibold small">Ou digite um novo departamento</label>
                            <input type="text" class="form-control" id="novo_departamento" name="novo_departamento" value="<?= h($dados['novo_departamento']) ?>" maxlength="100" placeholder="Ex.: Operações">
                            <small class="form-text text-muted">Ele é criado automaticamente junto com o veículo. <a href="/dashboard/departamentos/index.php">Gerenciar departamentos</a></small>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"<?= $dados['ativo'] ? ' checked' : '' ?>>
                                <label class="form-check-label" for="ativo">Veículo ativo</label>
                            </div>
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
