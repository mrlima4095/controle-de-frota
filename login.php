<?php
    $submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Acesse sua conta DriveHub.">
        <link rel="stylesheet" href="auth.css">
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
                    <?php if ($submitted): ?>
                        <p class="notice">Login enviado com sucesso. A autenticação real será conectada em breve.</p>
                    <?php endif; ?>
                    <form method="post">
                        <label>E-mail<input type="email" name="email" placeholder="voce@empresa.com" required></label>
                        <label>Senha<input type="password" name="senha" placeholder="Sua senha" required></label>
                        <button type="submit">Entrar na plataforma</button>
                    </form>
                    <p class="form-footer">Ainda não tem uma conta? <a href="cadastro-pessoa.php">Cadastre-se</a></p>
                </div>
            </section>
        </main>
    </body>
</html>
