<?php
    $submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Crie sua conta pessoal no DriveHub.">
        <link rel="stylesheet" href="auth.css">
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
                    <?php if ($submitted): ?>
                        <p class="notice">Cadastro enviado com sucesso. Os dados ainda não são armazenados nesta demonstração.</p>
                    <?php endif; ?>
                    <form method="post">
                        <div class="fields"><label>Nome<input type="text" name="nome" placeholder="Seu nome" required></label><label>Sobrenome<input type="text" name="sobrenome" placeholder="Seu sobrenome" required></label></div>
                        <label>E-mail<input type="email" name="email" placeholder="voce@email.com" required></label>
                        <label>Senha<input type="password" name="senha" placeholder="Crie uma senha" required></label>
                        <button type="submit">Criar minha conta</button>
                    </form>
                    <p class="form-footer">Já possui uma conta? <a href="login.php">Entrar</a></p>
                </div>
            </section>
        </main>
    </body>
</html>
