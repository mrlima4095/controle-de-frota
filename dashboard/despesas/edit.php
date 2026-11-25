<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Editar despesa';
$pageSubtitle = 'Atualize os dados do lançamento.';
$activeMenu = 'despesas';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function rotuloCategoria(string $categoria): string
{
    return match ($categoria) {
        'combustivel' => 'Combustível',
        'manutencao' => 'Manutenção',
        'pneu' => 'Pneu',
        'multa' => 'Multa',
        'seguro' => 'Seguro',
        'pedagio' => 'Pedágio',
        'salario' => 'Salário',
        default => 'Outro',
    };
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM despesa WHERE id_des = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$despesa = $stmt->fetch();

if (!$despesa) {
    header('Location: /dashboard/despesas/index.php');
    exit;
}

$categorias = ['combustivel', 'manutencao', 'pneu', 'multa', 'seguro', 'pedagio', 'salario', 'outro'];

$veiculos = $pdo->query('SELECT id_vei, placa FROM veiculo ORDER BY placa ASC')->fetchAll();
$fornecedores = $pdo->query('SELECT id_forn, nome FROM fornecedor ORDER BY nome ASC')->fetchAll();

$dados = [
    'descricao' => (string) $despesa['descricao'],
    'categoria' => (string) $despesa['categoria'],
    'valor' => (string) $despesa['valor'],
    'data_despesa' => (string) $despesa['data_despesa'],
    'id_vei' => (string) ($despesa['id_vei'] ?? ''),
    'id_forn' => (string) ($despesa['id_forn'] ?? ''),
    'observacoes' => (string) ($despesa['observacoes'] ?? ''),
];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($dados) as $campo) {
        $dados[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }

    if (!in_array($dados['categoria'], $categorias, true)) {
        $dados['categoria'] = 'outro';
    }

    $valor = (float) str_replace(',', '.', $dados['valor']);
    $dataInformada = DateTime::createFromFormat('!Y-m-d', $dados['data_despesa']);

    if ($dados['descricao'] === '') {
        $erro = 'Informe a descrição da despesa.';
    } elseif ($valor <= 0) {
        $erro = 'Informe um valor maior que zero.';
    } elseif (!$dataInformada || $dataInformada->format('Y-m-d') !== $dados['data_despesa']) {
        $erro = 'Informe uma data válida para a despesa.';
    } else {
        $idVeiculo = ctype_digit($dados['id_vei']) ? (int) $dados['id_vei'] : null;
        $idFornecedor = ctype_digit($dados['id_forn']) ? (int) $dados['id_forn'] : null;

        $sql = 'UPDATE despesa SET
                    descricao = :descricao, categoria = :categoria, valor = :valor,
                    data_despesa = :data_despesa, id_vei = :id_vei, id_forn = :id_forn,
                    observacoes = :observacoes
                WHERE id_des = :id';

        try {
            $pdo->prepare($sql)->execute([
                ':descricao' => $dados['descricao'],
                ':categoria' => $dados['categoria'],
                ':valor' => $valor,
                ':data_despesa' => $dados['data_despesa'],
                ':id_vei' => $idVeiculo,
                ':id_forn' => $idFornecedor,
                ':observacoes' => $dados['observacoes'] !== '' ? $dados['observacoes'] : null,
                ':id' => $id,
            ]);
        } catch (PDOException $exception) {
            $erro = 'Não foi possível salvar a despesa. Tente novamente.';
        }

        if ($erro === '') {
            header('Location: /dashboard/despesas/view.php?id=' . $id . '&ok=1');
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
                <h2 class="h6 mb-0">Editando <?= h($dados['descricao']) ?></h2>
                <a class="btn btn-sm btn-outline-secondary" href="view.php?id=<?= $id ?>"><i class="fa-solid fa-eye"></i> Ver detalhes</a>
            </div>
            <div class="card-body p-3 p-sm-4">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label for="descricao" class="form-label fw-semibold small">Descrição *</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" value="<?= h($dados['descricao']) ?>" maxlength="200" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="categoria" class="form-label fw-semibold small">Categoria</label>
                            <select class="form-select" id="categoria" name="categoria">
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria ?>"<?= $dados['categoria'] === $categoria ? ' selected' : '' ?>><?= h(rotuloCategoria($categoria)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="valor" class="form-label fw-semibold small">Valor (R$) *</label>
                            <input type="number" class="form-control" id="valor" name="valor" value="<?= h($dados['valor']) ?>" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="data_despesa" class="form-label fw-semibold small">Data da despesa *</label>
                            <input type="date" class="form-control" id="data_despesa" name="data_despesa" value="<?= h($dados['data_despesa']) ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="id_vei" class="form-label fw-semibold small">Veículo</label>
                            <select class="form-select" id="id_vei" name="id_vei">
                                <option value="">Nenhum</option>
                                <?php foreach ($veiculos as $veiculo): ?>
                                    <option value="<?= (int) $veiculo['id_vei'] ?>"<?= $dados['id_vei'] === (string) $veiculo['id_vei'] ? ' selected' : '' ?>><?= h((string) $veiculo['placa']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="id_forn" class="form-label fw-semibold small">Fornecedor</label>
                            <select class="form-select" id="id_forn" name="id_forn">
                                <option value="">Nenhum</option>
                                <?php foreach ($fornecedores as $fornecedor): ?>
                                    <option value="<?= (int) $fornecedor['id_forn'] ?>"<?= $dados['id_forn'] === (string) $fornecedor['id_forn'] ? ' selected' : '' ?>><?= h((string) $fornecedor['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="observacoes" class="form-label fw-semibold small">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3" maxlength="1000" placeholder="Informações adicionais (opcional)"><?= h($dados['observacoes']) ?></textarea>
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
