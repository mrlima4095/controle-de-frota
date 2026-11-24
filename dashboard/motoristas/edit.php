<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Editar motorista';
$pageSubtitle = 'Atualize os dados do condutor.';
$activeMenu = 'motoristas';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM motorista WHERE id_mot = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$motorista = $stmt->fetch();

if (!$motorista) {
    header('Location: /dashboard/motoristas/index.php');
    exit;
}

$dados = [
    'nome' => $motorista['nome'],
    'cpf' => $motorista['cpf'],
    'cnh' => $motorista['cnh'],
    'validade_cnh' => $motorista['validade_cnh'],
    'telefone' => $motorista['telefone'],
    'email' => $motorista['email'],
    'uf' => $motorista['uf'],
    'cidade' => $motorista['cidade'],
    'ativo' => $motorista['ativo'],
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
        $erro = 'Informe o nome do motorista.';
    } elseif ($dados['cpf'] === '') {
        $erro = 'Informe o CPF do motorista.';
    } elseif ($dados['email'] !== '' && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } else {
        $check = $pdo->prepare('SELECT id_mot FROM motorista WHERE cpf = :cpf AND id_mot <> :id LIMIT 1');
        $check->execute([':cpf' => $dados['cpf'], ':id' => $id]);

        if ($check->fetch()) {
            $erro = 'Este CPF já está cadastrado em outro motorista.';
        } else {
            $sql = 'UPDATE motorista SET
                        nome = :nome, cpf = :cpf, cnh = :cnh, validade_cnh = :validade_cnh,
                        telefone = :telefone, email = :email, uf = :uf, cidade = :cidade,
                        ativo = :ativo
                    WHERE id_mot = :id';

            try {
                $pdo->prepare($sql)->execute([
                    ':nome' => $dados['nome'],
                    ':cpf' => $dados['cpf'],
                    ':cnh' => $dados['cnh'] !== '' ? $dados['cnh'] : null,
                    ':validade_cnh' => $dados['validade_cnh'] !== '' ? $dados['validade_cnh'] : null,
                    ':telefone' => $dados['telefone'],
                    ':email' => $dados['email'],
                    ':uf' => $dados['uf'],
                    ':cidade' => $dados['cidade'],
                    ':ativo' => $dados['ativo'],
                    ':id' => $id,
                ]);
            } catch (PDOException $exception) {
                $erro = 'Não foi possível salvar o motorista. Tente novamente.';
            }

            if ($erro === '') {
                header('Location: /dashboard/motoristas/view.php?id=' . $id . '&ok=1');
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
                <h2 class="h6 mb-0">Editando <?= h($dados['nome']) ?></h2>
                <a class="btn btn-sm btn-outline-secondary" href="view.php?id=<?= $id ?>">👁️ Ver detalhes</a>
            </div>
            <div class="card-body p-3 p-sm-4">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="nome" class="form-label fw-semibold small">Nome completo *</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= h($dados['nome']) ?>" maxlength="150" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="cpf" class="form-label fw-semibold small">CPF *</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" value="<?= h((string) $dados['cpf']) ?>" maxlength="14" required>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="cnh" class="form-label fw-semibold small">CNH</label>
                            <input type="text" class="form-control" id="cnh" name="cnh" value="<?= h((string) $dados['cnh']) ?>" maxlength="20">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="validade_cnh" class="form-label fw-semibold small">Validade da CNH</label>
                            <input type="date" class="form-control" id="validade_cnh" name="validade_cnh" value="<?= h((string) $dados['validade_cnh']) ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="telefone" class="form-label fw-semibold small">Telefone</label>
                            <input type="tel" class="form-control" id="telefone" name="telefone" value="<?= h((string) $dados['telefone']) ?>" maxlength="20">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label fw-semibold small">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= h((string) $dados['email']) ?>" maxlength="100">
                        </div>
                        <div class="col-12 col-md-2">
                            <label for="uf" class="form-label fw-semibold small">UF</label>
                            <input type="text" class="form-control" id="uf" name="uf" value="<?= h((string) $dados['uf']) ?>" maxlength="2">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="cidade" class="form-label fw-semibold small">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" value="<?= h((string) $dados['cidade']) ?>" maxlength="100">
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"<?= $dados['ativo'] ? ' checked' : '' ?>>
                                <label class="form-check-label" for="ativo">Motorista ativo</label>
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
