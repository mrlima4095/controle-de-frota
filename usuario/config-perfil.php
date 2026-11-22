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

<?php if (isset($_GET['ok'])): ?>
    <p class="alerta">Dados atualizados com sucesso.</p>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <p class="alerta is-erro"><?= h($erro) ?></p>
<?php endif; ?>

<div class="page-head">
    <div>
        <h2>Dados da conta</h2>
        <p>Perfil de acesso: <span class="badge is-blue"><?= h($conta['perfil']) ?></span></p>
    </div>
</div>

<form method="post" class="form-card">
    <div class="form-grid">
        <label class="campo full">
            Nome completo
            <input type="text" name="nome" value="<?= h($nome) ?>" maxlength="100" autocomplete="name" required>
        </label>

        <label class="campo full">
            E-mail
            <input type="email" name="email" value="<?= h($email) ?>" maxlength="100" autocomplete="email" required>
        </label>

        <label class="campo">
            Senha atual
            <input type="password" name="senha_atual" autocomplete="current-password" placeholder="Necessária apenas para trocar a senha">
        </label>

        <label class="campo">
            Nova senha
            <input type="password" name="senha_nova" autocomplete="new-password" minlength="6" placeholder="Mínimo de 6 caracteres">
        </label>

        <label class="campo full">
            Confirmar nova senha
            <input type="password" name="senha_confirmar" autocomplete="new-password" placeholder="Repita a nova senha">
        </label>
    </div>

    <div class="form-acoes">
        <button type="submit" class="btn btn-primary">Salvar alterações</button>
        <a class="btn btn-light" href="/usuario/index.php">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
