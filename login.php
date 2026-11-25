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
        <title>Entrar | DriveHub</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link rel="stylesheet" href="/static/css/app.css">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" defer></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha384-DsXFqEUf3HnCU8om0zbXN58DxV7Bo8/z7AbHBGd2XxkeNpdLrygNiGFr/03W0Xmt" crossorigin="anonymous" defer></script>
    </head>
    <body>
        <div class="container-fluid min-vh-100">
            <div class="row min-vh-100 g-0">
                <div class="col-lg-6 auth-intro d-none d-lg-flex flex-column p-5">
                    <a class="brand" href="index.html"><span class="brand-mark"><i class="fa-solid fa-location-arrow" aria-hidden="true"></i></span>DriveHub</a>
                    <div class="my-auto py-5">
                        <p class="eyebrow">Controle de frota</p>
                        <h1 class="display-5 fw-bolder mt-3 mb-4">Sua operação em um só lugar.</h1>
                        <p class="fs-6">Acompanhe o que importa para manter sua frota sempre no caminho certo.</p>
                    </div>
                    <span class="small">© 2026 DriveHub</span>
                </div>

                <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center p-4 p-sm-5">
                    <div class="auth-form">
                        <a class="small fw-bold text-brand" href="index.html">← Voltar para o início</a>
                        <h2 class="h2 fw-bolder mt-4">Boas-vindas de volta</h2>
                        <p class="text-muted mb-4">Entre para acessar o painel da sua operação.</p>

                        <?php if ($erro !== ''): ?>
                            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erro) ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold small">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="voce@empresa.com" autocomplete="email" required>
                            </div>
                            <div class="mb-4">
                                <label for="senha" class="form-label fw-semibold small">Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha" placeholder="Sua senha" autocomplete="current-password" required>
                            </div>
                            <button type="submit" class="btn btn-brand w-100 py-2">Entrar na plataforma</button>
                        </form>

                        <p class="text-center text-muted small mt-4 mb-0">
                            Precisa de acesso? Solicite ao gerente ou RH da sua empresa.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
