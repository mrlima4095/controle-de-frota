<?php
    session_start();

    $erro = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once __DIR__ . '/includes/connect.php';

        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if ($email === '' || $senha === '') {
            $erro = 'Preencha e-mail e senha.';
        } else {
            $sql = 'SELECT id_usr, nome, senha FROM usuario WHERE email = :email AND ativo = TRUE LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                session_regenerate_id(true);
                $_SESSION['id_usr'] = (int) $usuario['id_usr'];
                $_SESSION['nome'] = $usuario['nome'];

                $pdo->prepare('UPDATE usuario SET ultima_consessao = NOW() WHERE id_usr = :id')
                    ->execute([':id' => $usuario['id_usr']]);

                header('Location: dashboard/index.php');
                exit;
            }

            $erro = 'E-mail ou senha inválidos.';
        }
    }
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Acesse sua conta DriveHub.">
        <link rel="stylesheet" href="static/css/auth.css">
        <title>Entrar | DriveHub</title>
    </head>
    <body>
        <main class="page">
            <section class="intro">
                <a class="brand" href="index.html"><span class="brand-mark">↗</span>DriveHub</a>
                <div class="intro-copy">
                    <p class="eyebrow">Controle de frota</p>
                    <h1>Sua operação em um só lugar.</h1>
                    <p>Acompanhe o que importa para manter sua frota sempre no caminho certo.</p>
                </div>
                <span class="intro-footer">© 2026 DriveHub</span>
            </section>
            <section class="form-area">
                <div class="form-card">
                    <a class="back" href="index.html">← Voltar para o início</a>
                    <h2>Boas-vindas de volta</h2>
                    <p class="form-description">Entre para acessar o painel da sua operação.</p>
                    <?php if ($erro !== ''): ?>
                        <p class="notice"><?= htmlspecialchars($erro) ?></p>
                    <?php endif; ?>
                    <form method="post">
                        <label>E-mail<input type="email" name="email" placeholder="voce@empresa.com" required></label>
                        <label>Senha<input type="password" name="senha" placeholder="Sua senha" required></label>
                        <button type="submit">Entrar na plataforma</button>
                    </form>
                    <p class="form-footer">Ainda não tem uma conta? <a href="registro.php">Cadastre-se</a></p>
                </div>
            </section>
        </main>
    </body>
</html>
