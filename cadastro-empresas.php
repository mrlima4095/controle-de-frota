<?php
    $submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Cadastre sua empresa no DriveHub.">
        <link rel="stylesheet" href="auth.css">
        <title>Cadastrar empresa | DriveHub</title>
    </head>
    <body>
        <main class="page">
            <section class="intro">
                <a class="brand" href="index.html"><span class="brand-mark">↗</span>DriveHub</a>
                <div class="intro-copy">
                    <p class="eyebrow">Para empresas</p>
                    <h1>Uma gestão à altura da sua frota.</h1>
                    <p>Centralize a operação e dê à sua equipe mais controle em cada trajeto.</p>
                </div>
                <span class="intro-footer">© 2026 DriveHub</span>
            </section>
            <section class="form-area">
                <div class="form-card">
                    <a class="back" href="index.html">← Voltar para o início</a>
                    <h2>Cadastre sua empresa</h2>
                    <p class="form-description">Conte um pouco sobre a sua empresa para começar.</p>
                    <?php if ($submitted): ?>
                        <p class="notice">Cadastro enviado com sucesso. Esta é uma demonstração e nenhum dado foi armazenado.</p>
                    <?php endif; ?>
                    <form method="post">
                        <label>Nome da empresa<input type="text" name="empresa" placeholder="Nome da empresa" required></label>
                        <div class="fields"><label>CNPJ<input type="text" name="cnpj" placeholder="00.000.000/0001-00" required></label><label>Quantidade de veículos<input type="number" name="veiculos" min="1" placeholder="Ex.: 25" required></label></div>
                        <label>E-mail corporativo<input type="email" name="email" placeholder="contato@empresa.com" required></label>
                        <button type="submit">Cadastrar empresa</button>
                    </form>
                    <p class="form-footer">Já possui uma conta? <a href="login.php">Entrar</a></p>
                </div>
            </section>
        </main>
    </body>
</html>
