<?php
    session_start();

    $erro = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once __DIR__ . '/includes/connect.php';

        $nome = trim($_POST['nome'] ?? '');
        $sobrenome = trim($_POST['sobrenome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if ($nome === '' || $email === '' || $senha === '') {
            $erro = 'Preencha nome, e-mail e senha.';
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
                $nomeCompleto = $sobrenome !== '' ? "$nome $sobrenome" : $nome;

                $sql = 'INSERT INTO usuario (nome, email, senha, perfil) VALUES (:nome, :email, :senha, :perfil)';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nome' => $nomeCompleto,
                    ':email' => $email,
                    ':senha' => password_hash($senha, PASSWORD_DEFAULT),
                    ':perfil' => 'operador',
                ]);

                header('Location: login.php');
                exit;
            }
        }
    }
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Crie sua conta pessoal no DriveHub.">
        <link rel="stylesheet" href="static/css/auth.css">
        <title>Criar conta | DriveHub</title>
    </head>
    <body>
        <main class="page">
            <section class="intro">
                <a class="brand" href="index.html"><span class="brand-mark">↗</span>DriveHub</a>
                <div class="intro-copy">
                    <p class="eyebrow">Conta pessoal</p>
                    <h1>Comece a organizar sua rotina.</h1>
                    <p>Crie sua conta e tenha uma visão mais clara da frota que você acompanha.</p>
                </div>
                <span class="intro-footer">© 2026 DriveHub</span>
            </section>
            <section class="form-area">
                <div class="form-card">
                    <a class="back" href="index.html">← Voltar para o início</a>
                    <h2>Crie sua conta</h2>
                    <p class="form-description">Preencha os dados para iniciar sua experiência no DriveHub.</p>
                    <?php if ($erro !== ''): ?>
                        <p class="notice"><?= htmlspecialchars($erro) ?></p>
                    <?php endif; ?>
                    <form method="post">
                        <div class="fields"><label>Nome<input type="text" name="nome" placeholder="Seu nome" required></label><label>Sobrenome<input type="text" name="sobrenome" placeholder="Seu sobrenome"></label></div>
                        <label>E-mail<input type="email" name="email" placeholder="voce@email.com" required></label>
                        <label>Senha<input type="password" name="senha" placeholder="Crie uma senha (mín. 6 caracteres)" required></label>
                        <button type="submit">Criar minha conta</button>
                    </form>
                    <p class="form-footer">Já possui uma conta? <a href="login.php">Entrar</a></p>
                </div>
            </section>
        </main>
    </body>
</html>
