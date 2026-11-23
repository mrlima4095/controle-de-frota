<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Novo usuário';
$pageSubtitle = 'Crie uma conta para liberar o acesso ao sistema.';
$activeMenu = 'usuarios';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

if (!podeGerenciarUsuarios($usuarioLogado['perfil'])) {
    http_response_code(403);
    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="alert alert-danger" role="alert">
        Você não tem permissão para cadastrar usuários. Somente gerente, RH e administradores podem acessar esta área.
    </div>
    <a class="btn btn-outline-secondary" href="/dashboard/index.php">Voltar ao painel</a>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    exit;
}

$perfis = ['admin', 'gerente', 'rh', 'operador', 'visualizador'];

$nome = '';
$email = '';
$perfil = 'operador';
$ativo = true;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $perfil = $_POST['perfil'] ?? 'operador';
    $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

    if (!in_array($perfil, $perfis, true)) {
        $perfil = 'operador';
    }

    if ($nome === '') {
        $erro = 'Informe o nome do usuário.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        $check = $pdo->prepare('SELECT id_usr FROM usuario WHERE email = :email LIMIT 1');
        $check->execute([':email' => $email]);

        if ($check->fetch()) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            $sql = 'INSERT INTO usuario (nome, email, senha, perfil, ativo) VALUES (:nome, :email, :senha, :perfil, :ativo)';

            try {
                $pdo->prepare($sql)->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':senha' => password_hash($senha, PASSWORD_DEFAULT),
                    ':perfil' => $perfil,
                    ':ativo' => $ativo ? 1 : 0,
                ]);
            } catch (PDOException $exception) {
                $erro = 'Não foi possível cadastrar o usuário. Tente novamente.';
            }

            if ($erro === '') {
                header('Location: /dashboard/usuarios/index.php?ok=1');
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
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h6 mb-0">Dados do novo usuário</h2>
            </div>
            <div class="card-body p-3 p-sm-4">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="nome" class="form-label fw-semibold small">Nome completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= h($nome) ?>" maxlength="100" autocomplete="name" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label fw-semibold small">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= h($email) ?>" maxlength="100" autocomplete="email" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="senha" class="form-label fw-semibold small">Senha inicial</label>
                            <input type="password" class="form-control" id="senha" name="senha" autocomplete="new-password" minlength="6" placeholder="Mínimo de 6 caracteres" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="perfil" class="form-label fw-semibold small">Perfil de acesso</label>
                            <select class="form-select" id="perfil" name="perfil" required>
                                <?php foreach ($perfis as $opcao): ?>
                                    <option value="<?= $opcao ?>"<?= $perfil === $opcao ? ' selected' : '' ?>><?= ucfirst($opcao) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"<?= $ativo ? ' checked' : '' ?>>
                                <label class="form-check-label" for="ativo">Conta ativa (permite login imediato)</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                        <button type="submit" class="btn btn-brand">Cadastrar usuário</button>
                        <a class="btn btn-outline-secondary" href="/dashboard/usuarios/index.php">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
