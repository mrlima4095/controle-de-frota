<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Configurações do perfil';
$pageSubtitle = 'Atualize seus dados pessoais e a senha de acesso.';
$activeMenu = '';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

// A senha atual é necessária para validar uma eventual troca de senha.
$stmt = $pdo->prepare('SELECT nome, email, perfil, senha FROM usuario WHERE id_usr = :id LIMIT 1');
$stmt->execute([':id' => $_SESSION['id_usr']]);
$conta = $stmt->fetch();

$nome = $conta['nome'];
$email = $conta['email'];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $senhaNova = $_POST['senha_nova'] ?? '';
    $senhaConfirmar = $_POST['senha_confirmar'] ?? '';

    if ($nome === '') {
        $erro = 'Informe seu nome.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } else {
        $check = $pdo->prepare('SELECT id_usr FROM usuario WHERE email = :email AND id_usr <> :id LIMIT 1');
        $check->execute([':email' => $email, ':id' => $_SESSION['id_usr']]);

        if ($check->fetch()) {
            $erro = 'Este e-mail já está em uso por outra conta.';
        } elseif ($senhaNova !== '' || $senhaConfirmar !== '' || $senhaAtual !== '') {
            if ($senhaNova === '') {
                $erro = 'Informe a nova senha que deseja usar.';
            } elseif (strlen($senhaNova) < 6) {
                $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
            } elseif ($senhaNova !== $senhaConfirmar) {
                $erro = 'A confirmação da nova senha não confere.';
            } elseif (!password_verify($senhaAtual, $conta['senha'])) {
                $erro = 'A senha atual está incorreta.';
            }
        }
    }

    if ($erro === '') {
        $sql = 'UPDATE usuario SET nome = :nome, email = :email';
        $parametros = [
            ':nome' => $nome,
            ':email' => $email,
            ':id' => $_SESSION['id_usr'],
        ];

        if ($senhaNova !== '') {
            $sql .= ', senha = :senha';
            $parametros[':senha'] = password_hash($senhaNova, PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE id_usr = :id';

        try {
            $pdo->prepare($sql)->execute($parametros);
        } catch (PDOException $exception) {
            $erro = 'Não foi possível salvar as alterações. Tente novamente.';
        }

        if ($erro === '') {
            // Mantém o cabeçalho e a sessão sincronizados com o novo nome.
            $_SESSION['nome'] = $nome;
            $usuarioLogado['nome'] = $nome;
            $conta['nome'] = $nome;
            $conta['email'] = $email;

            header('Location: /usuario/config-perfil.php?ok=1');
            exit;
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <?php if (isset($_GET['ok'])): ?>
            <div class="alert alert-success" role="alert">Dados atualizados com sucesso.</div>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= h($erro) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h2 class="h6 mb-0">Dados da conta</h2>
                <span class="text-muted small">Perfil de acesso: <span class="badge text-bg-primary rounded-pill"><?= h($conta['perfil']) ?></span></span>
            </div>
            <div class="card-body p-3 p-sm-4">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="nome" class="form-label fw-semibold small">Nome completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= h($nome) ?>" maxlength="100" autocomplete="name" required>
                        </div>

                        <div class="col-12">
                            <label for="email" class="form-label fw-semibold small">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= h($email) ?>" maxlength="100" autocomplete="email" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="senha_atual" class="form-label fw-semibold small">Senha atual</label>
                            <input type="password" class="form-control" id="senha_atual" name="senha_atual" autocomplete="current-password" placeholder="Necessária apenas para trocar a senha">
                            <div class="form-text">Preencha somente se quiser alterar sua senha.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="senha_nova" class="form-label fw-semibold small">Nova senha</label>
                            <input type="password" class="form-control" id="senha_nova" name="senha_nova" autocomplete="new-password" minlength="6" placeholder="Mínimo de 6 caracteres">
                        </div>

                        <div class="col-12">
                            <label for="senha_confirmar" class="form-label fw-semibold small">Confirmar nova senha</label>
                            <input type="password" class="form-control" id="senha_confirmar" name="senha_confirmar" autocomplete="new-password" placeholder="Repita a nova senha">
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                        <button type="submit" class="btn btn-brand">Salvar alterações</button>
                        <a class="btn btn-outline-secondary" href="/usuario/index.php">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
