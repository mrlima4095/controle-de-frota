<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Nova despesa';
$pageSubtitle = 'Registre um novo lançamento de despesa.';
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

$categorias = ['combustivel', 'manutencao', 'pneu', 'multa', 'seguro', 'pedagio', 'salario', 'outro'];

$veiculos = $pdo->query('SELECT id_vei, placa FROM veiculo ORDER BY placa ASC')->fetchAll();
$fornecedores = $pdo->query('SELECT id_forn, nome FROM fornecedor ORDER BY nome ASC')->fetchAll();

$dados = [
    'descricao' => '',
    'categoria' => 'outro',
    'valor' => '',
    'data_despesa' => date('Y-m-d'),
    'id_vei' => '',
    'id_forn' => '',
    'observacoes' => '',
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

        $sql = 'INSERT INTO despesa
                    (descricao, categoria, valor, data_despesa, id_vei, id_forn, observacoes)
                VALUES
                    (:descricao, :categoria, :valor, :data_despesa, :id_vei, :id_forn, :observacoes)';

        try {
            $pdo->prepare($sql)->execute([
                ':descricao' => $dados['descricao'],
                ':categoria' => $dados['categoria'],
                ':valor' => $valor,
                ':data_despesa' => $dados['data_despesa'],
                ':id_vei' => $idVeiculo,
                ':id_forn' => $idFornecedor,
                ':observacoes' => $dados['observacoes'] !== '' ? $dados['observacoes'] : null,
            ]);
        } catch (PDOException $exception) {
            $erro = 'Não foi possível cadastrar a despesa. Verifique os vínculos e tente novamente.';
        }

        if ($erro === '') {
            header('Location: /dashboard/despesas/index.php?ok=1');
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
                <h2 class="h6 mb-0">Dados da despesa</h2>
            </div>
            <div class="card-body p-3 p-sm-4">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label for="descricao" class="form-label fw-semibold small">Descrição *</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" value="<?= h($dados['descricao']) ?>" maxlength="200" placeholder="Ex.: Troca de óleo" required>
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
                            <input type="number" class="form-control" id="valor" name="valor" value="<?= h($dados['valor']) ?>" step="0.01" min="0.01" placeholder="0,00" required>
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
                                    <option value="<?= (int) $veiculo['id_vei'] ?>"<?= (string) $dados['id_vei'] === (string) $veiculo['id_vei'] ? ' selected' : '' ?>><?= h((string) $veiculo['placa']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="id_forn" class="form-label fw-semibold small">Fornecedor</label>
                            <select class="form-select" id="id_forn" name="id_forn">
                                <option value="">Nenhum</option>
                                <?php foreach ($fornecedores as $fornecedor): ?>
                                    <option value="<?= (int) $fornecedor['id_forn'] ?>"<?= (string) $dados['id_forn'] === (string) $fornecedor['id_forn'] ? ' selected' : '' ?>><?= h((string) $fornecedor['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="observacoes" class="form-label fw-semibold small">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3" maxlength="1000" placeholder="Informações adicionais (opcional)"><?= h($dados['observacoes']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                        <button type="submit" class="btn btn-brand">Cadastrar despesa</button>
                        <a class="btn btn-outline-secondary" href="index.php">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
