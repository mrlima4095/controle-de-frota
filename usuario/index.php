<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Minha conta';
$pageSubtitle = 'Confira os dados da sua conta e gerencie suas preferências.';
$activeMenu = '';

// auth.php já carrega o básico; buscamos também as datas da conta.
$stmt = $pdo->prepare(
    'SELECT nome, email, perfil, data_criacao, ultima_consessao
     FROM usuario WHERE id_usr = :id LIMIT 1'
);
$stmt->execute([':id' => $_SESSION['id_usr']]);
$conta = $stmt->fetch();

function dataHoraBr(?string $valor): string
{
    if (!$valor) {
        return '—';
    }

    return date('d/m/Y H:i', (int) strtotime($valor));
}

require __DIR__ . '/../includes/header.php';
?>

<div class="grid-paineis">
    <section class="painel">
        <header class="painel-head">
            <h2>Dados pessoais</h2>
            <span class="badge is-blue"><?= htmlspecialchars($conta['perfil']) ?></span>
        </header>
        <div class="painel-body">
            <div class="lista-item">
                <div>
                    <strong>Nome</strong>
                    <small>Como você aparece no sistema</small>
                </div>
                <span><?= htmlspecialchars($conta['nome']) ?></span>
            </div>
            <div class="lista-item">
                <div>
                    <strong>E-mail</strong>
                    <small>Usado para identificar sua conta</small>
                </div>
                <span><?= htmlspecialchars($conta['email']) ?></span>
            </div>
            <div class="lista-item">
                <div>
                    <strong>Perfil de acesso</strong>
                    <small>Definido pela administração do sistema</small>
                </div>
                <span class="badge"><?= htmlspecialchars($conta['perfil']) ?></span>
            </div>
        </div>
    </section>

    <section class="painel">
        <header class="painel-head">
            <h2>Informações da conta</h2>
        </header>
        <div class="painel-body">
            <div class="lista-item">
                <div>
                    <strong>Conta criada em</strong>
                    <small>Data do cadastro</small>
                </div>
                <span><?= dataHoraBr($conta['data_criacao']) ?></span>
            </div>
            <div class="lista-item">
                <div>
                    <strong>Último acesso</strong>
                    <small>Registro do seu login mais recente</small>
                </div>
                <span><?= dataHoraBr($conta['ultima_consessao']) ?></span>
            </div>
            <div class="lista-item">
                <div>
                    <strong>Status</strong>
                    <small>Sua conta está ativa</small>
                </div>
                <span class="badge is-green">Ativa</span>
            </div>
        </div>
    </section>

    <section class="painel">
        <header class="painel-head">
            <h2>Ações da conta</h2>
        </header>
        <div class="painel-body">
            <div class="quick-links">
                <a class="quick-link" href="/usuario/config-perfil.php"><span class="menu-icon">✎</span> Editar perfil</a>
                <a class="quick-link" href="/usuario/deletar-conta.php"><span class="menu-icon">⚠</span> Excluir conta</a>
                <a class="quick-link" href="/dashboard/index.php"><span class="menu-icon">◫</span> Voltar ao painel</a>
            </div>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
