<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Editar fornecedor';
$pageSubtitle = 'Atualize os dados cadastrais do fornecedor.';
$activeMenu = 'fornecedores';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM fornecedor WHERE id_forn = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$fornecedor = $stmt->fetch();

if (!$fornecedor) {
    header('Location: /dashboard/fornecedores/index.php');
    exit;
}

$dados = [
    'nome' => (string) $fornecedor['nome'],
    'cnpj_cpf' => (string) ($fornecedor['cnpj_cpf'] ?? ''),
    'telefone' => (string) ($fornecedor['telefone'] ?? ''),
    'email' => (string) ($fornecedor['email'] ?? ''),
    'categoria' => (string) ($fornecedor['categoria'] ?? ''),
    'uf' => (string) ($fornecedor['uf'] ?? ''),
    'cidade' => (string) ($fornecedor['cidade'] ?? ''),
    'ativo' => $fornecedor['ativo'],
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

    $dados['uf'] = strtoupper($dados['uf']);

    if ($dados['nome'] === '') {
        $erro = 'Informe o nome do fornecedor.';
    } elseif ($dados['email'] !== '' && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif ($dados['cnpj_cpf'] !== '') {
        $check = $pdo->prepare('SELECT id_forn FROM fornecedor WHERE cnpj_cpf = :documento AND id_forn <> :id LIMIT 1');
        $check->execute([':documento' => $dados['cnpj_cpf'], ':id' => $id]);

        if ($check->fetch()) {
            $erro = 'Este CNPJ/CPF já está cadastrado em outro fornecedor.';
        }
    }

    if ($erro === '') {
        $sql = 'UPDATE fornecedor SET
                    nome = :nome, cnpj_cpf = :cnpj_cpf, telefone = :telefone,
                    email = :email, categoria = :categoria, uf = :uf,
                    cidade = :cidade, ativo = :ativo
                WHERE id_forn = :id';

        try {
            $pdo->prepare($sql)->execute([
                ':nome' => $dados['nome'],
                ':cnpj_cpf' => $dados['cnpj_cpf'] !== '' ? $dados['cnpj_cpf'] : null,
                ':telefone' => $dados['telefone'] !== '' ? $dados['telefone'] : null,
                ':email' => $dados['email'] !== '' ? $dados['email'] : null,
                ':categoria' => $dados['categoria'] !== '' ? $dados['categoria'] : null,
                ':uf' => $dados['uf'] !== '' ? $dados['uf'] : null,
                ':cidade' => $dados['cidade'] !== '' ? $dados['cidade'] : null,
                ':ativo' => $dados['ativo'],
                ':id' => $id,
            ]);
        } catch (PDOException $exception) {
            $erro = 'Não foi possível salvar o fornecedor. Tente novamente.';
        }

        if ($erro === '') {
            header('Location: /dashboard/fornecedores/view.php?id=' . $id . '&ok=1');
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
                <h2 class="h6 mb-0">Editando <?= h((string) $dados['nome']) ?></h2>
                <a class="btn btn-sm btn-outline-secondary" href="view.php?id=<?= $id ?>"><i class="fa-solid fa-eye"></i> Ver detalhes</a>
            </div>
            <div class="card-body p-3 p-sm-4">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="nome" class="form-label fw-semibold small">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= h((string) $dados['nome']) ?>" maxlength="150" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="cnpj_cpf" class="form-label fw-semibold small">CNPJ/CPF</label>
                            <input type="text" class="form-control" id="cnpj_cpf" name="cnpj_cpf" value="<?= h((string) $dados['cnpj_cpf']) ?>" maxlength="18" placeholder="00.000.000/0000-00">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="telefone" class="form-label fw-semibold small">Telefone</label>
                            <input type="tel" class="form-control" id="telefone" name="telefone" value="<?= h((string) $dados['telefone']) ?>" maxlength="20" placeholder="(00) 0000-0000">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="email" class="form-label fw-semibold small">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= h((string) $dados['email']) ?>" maxlength="100" placeholder="contato@empresa.com">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="categoria" class="form-label fw-semibold small">Categoria</label>
                            <input type="text" class="form-control" id="categoria" name="categoria" value="<?= h((string) $dados['categoria']) ?>" maxlength="100" placeholder="Ex.: Combustível, Manutenção">
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="uf" class="form-label fw-semibold small">UF</label>
                            <input type="text" class="form-control" id="uf" name="uf" value="<?= h((string) $dados['uf']) ?>" maxlength="2" placeholder="SP">
                        </div>
                        <div class="col-6 col-md-4">
                            <label for="cidade" class="form-label fw-semibold small">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" value="<?= h((string) $dados['cidade']) ?>" maxlength="100" placeholder="São Paulo">
                        </div>
                        <div class="col-12 col-md-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"<?= $dados['ativo'] ? ' checked' : '' ?>>
                                <label class="form-check-label" for="ativo">Fornecedor ativo</label>
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
