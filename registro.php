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
        <title>Criar conta | DriveHub</title>
        <link rel="stylesheet" href="/static/vendor/bootstrap-5.3.3/css/bootstrap.min.css">
        <link rel="stylesheet" href="/static/css/app.css">
        <script src="/static/vendor/bootstrap-5.3.3/js/bootstrap.bundle.min.js" defer></script>
    </head>
    <body>
        <div class="container-fluid min-vh-100">
            <div class="row min-vh-100 g-0">
                <div class="col-lg-6 auth-intro d-none d-lg-flex flex-column p-5">
                    <a class="brand" href="index.html"><span class="brand-mark">↗</span>DriveHub</a>
                    <div class="my-auto py-5">
                        <p class="eyebrow">Conta pessoal</p>
                        <h1 class="display-5 fw-bolder mt-3 mb-4">Comece a organizar sua rotina.</h1>
                        <p class="fs-6">Crie sua conta e tenha uma visão mais clara da frota que você acompanha.</p>
                    </div>
                    <span class="small">© 2026 DriveHub</span>
                </div>

                <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center p-4 p-sm-5">
                    <div class="auth-form">
                        <a class="small fw-bold text-brand" href="index.html">← Voltar para o início</a>
                        <h2 class="h2 fw-bolder mt-4">Crie sua conta</h2>
                        <p class="text-muted mb-4">Preencha os dados para iniciar sua experiência no DriveHub.</p>

                        <?php if ($erro !== ''): ?>
                            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erro) ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <label for="nome" class="form-label fw-semibold small">Nome</label>
                                    <input type="text" class="form-control" id="nome" name="nome" placeholder="Seu nome" autocomplete="given-name" required>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="sobrenome" class="form-label fw-semibold small">Sobrenome</label>
                                    <input type="text" class="form-control" id="sobrenome" name="sobrenome" placeholder="Seu sobrenome" autocomplete="family-name">
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label for="email" class="form-label fw-semibold small">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="voce@email.com" autocomplete="email" required>
                            </div>

                            <div class="mb-4">
                                <label for="senha" class="form-label fw-semibold small">Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha" placeholder="Crie uma senha (mín. 6 caracteres)" autocomplete="new-password" minlength="6" required>
                            </div>

                            <button type="submit" class="btn btn-brand w-100 py-2">Criar minha conta</button>
                        </form>

                        <p class="text-center text-muted small mt-4 mb-0">
                            Já possui uma conta? <a class="fw-bold text-brand" href="login.php">Entrar</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
