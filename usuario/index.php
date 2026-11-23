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

<div class="row g-3">
    <div class="col-12 col-xl-6">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white d-flex align-items-center justify-content-between gap-2 py-3">
                <h2 class="h6 mb-0">Dados pessoais</h2>
                <span class="badge text-bg-primary rounded-pill"><?= htmlspecialchars($conta['perfil']) ?></span>
            </header>
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                    <div>
                        <strong class="d-block">Nome</strong>
                        <small class="text-muted">Como você aparece no sistema</small>
                    </div>
                    <span class="text-sm-end"><?= htmlspecialchars($conta['nome']) ?></span>
                </div>
                <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                    <div>
                        <strong class="d-block">E-mail</strong>
                        <small class="text-muted">Usado para identificar sua conta</small>
                    </div>
                    <span class="text-sm-end text-break"><?= htmlspecialchars($conta['email']) ?></span>
                </div>
                <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                    <div>
                        <strong class="d-block">Perfil de acesso</strong>
                        <small class="text-muted">Definido pela administração do sistema</small>
                    </div>
                    <span class="text-sm-end"><span class="badge text-bg-secondary rounded-pill"><?= htmlspecialchars($conta['perfil']) ?></span></span>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-xl-6">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3">
                <h2 class="h6 mb-0">Informações da conta</h2>
            </header>
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                    <div>
                        <strong class="d-block">Conta criada em</strong>
                        <small class="text-muted">Data do cadastro</small>
                    </div>
                    <span class="text-sm-end"><?= dataHoraBr($conta['data_criacao']) ?></span>
                </div>
                <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                    <div>
                        <strong class="d-block">Último acesso</strong>
                        <small class="text-muted">Registro do seu login mais recente</small>
                    </div>
                    <span class="text-sm-end"><?= dataHoraBr($conta['ultima_consessao']) ?></span>
                </div>
                <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                    <div>
                        <strong class="d-block">Status</strong>
                        <small class="text-muted">Sua conta está ativa</small>
                    </div>
                    <span class="text-sm-end"><span class="badge text-bg-success rounded-pill">Ativa</span></span>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12">
        <section class="card border-0 shadow-sm">
            <header class="card-header bg-white py-3">
                <h2 class="h6 mb-0">Ações da conta</h2>
            </header>
            <div class="card-body">
                <div class="row row-cols-1 row-cols-sm-3 g-2">
                    <div class="col"><a class="quick-link" href="/usuario/config-perfil.php"><span class="menu-icon" aria-hidden="true">✏️</span> Editar perfil</a></div>
                    <div class="col"><a class="quick-link" href="/usuario/deletar-conta.php"><span class="menu-icon" aria-hidden="true">🗑️</span> Excluir conta</a></div>
                    <div class="col"><a class="quick-link" href="/dashboard/index.php"><span class="menu-icon" aria-hidden="true">📊</span> Voltar ao painel</a></div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
