<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Novo motorista';
$pageSubtitle = 'Cadastre um condutor da frota.';
$activeMenu = 'motoristas';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$dados = [
    'nome' => '', 'cpf' => '', 'cnh' => '', 'validade_cnh' => '',
    'telefone' => '', 'email' => '', 'uf' => '', 'cidade' => '', 'ativo' => 1,
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
        $check = $pdo->prepare('SELECT id_mot FROM motorista WHERE cpf = :cpf LIMIT 1');
        $check->execute([':cpf' => $dados['cpf']]);

        if ($check->fetch()) {
            $erro = 'Este CPF já está cadastrado.';
        } else {
            $sql = 'INSERT INTO motorista
                        (nome, cpf, cnh, validade_cnh, telefone, email, uf, cidade, ativo)
                    VALUES
                        (:nome, :cpf, :cnh, :validade_cnh, :telefone, :email, :uf, :cidade, :ativo)';

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
                ]);
            } catch (PDOException $exception) {
                $erro = 'Não foi possível cadastrar o motorista. Tente novamente.';
            }

            if ($erro === '') {
                header('Location: /dashboard/motoristas/index.php?ok=1');
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
            <div class="card-header bg-white py-3">
                <h2 class="h6 mb-0">Dados do motorista</h2>
            </div>
            <div class="card-body p-3 p-sm-4">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="nome" class="form-label fw-semibold small">Nome completo *</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= h($dados['nome']) ?>" maxlength="150" autocomplete="name" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="cpf" class="form-label fw-semibold small">CPF *</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" value="<?= h($dados['cpf']) ?>" maxlength="14" placeholder="000.000.000-00" required>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="cnh" class="form-label fw-semibold small">CNH</label>
                            <input type="text" class="form-control" id="cnh" name="cnh" value="<?= h($dados['cnh']) ?>" maxlength="20">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="validade_cnh" class="form-label fw-semibold small">Validade da CNH</label>
                            <input type="date" class="form-control" id="validade_cnh" name="validade_cnh" value="<?= h($dados['validade_cnh']) ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="telefone" class="form-label fw-semibold small">Telefone</label>
                            <input type="tel" class="form-control" id="telefone" name="telefone" value="<?= h($dados['telefone']) ?>" maxlength="20" placeholder="(00) 00000-0000">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label fw-semibold small">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= h($dados['email']) ?>" maxlength="100" autocomplete="email">
                        </div>
                        <div class="col-12 col-md-2">
                            <label for="uf" class="form-label fw-semibold small">UF</label>
                            <input type="text" class="form-control" id="uf" name="uf" value="<?= h($dados['uf']) ?>" maxlength="2" placeholder="SP">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="cidade" class="form-label fw-semibold small">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" value="<?= h($dados['cidade']) ?>" maxlength="100">
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"<?= $dados['ativo'] ? ' checked' : '' ?>>
                                <label class="form-check-label" for="ativo">Motorista ativo</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                        <button type="submit" class="btn btn-brand">Cadastrar motorista</button>
                        <a class="btn btn-outline-secondary" href="index.php">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
