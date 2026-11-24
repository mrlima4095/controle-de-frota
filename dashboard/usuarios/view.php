<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Detalhes do usuário';
$pageSubtitle = 'Informações da conta e do acesso.';
$activeMenu = 'usuarios';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function dataHoraCurta(?string $valor): string
{
    return $valor ? date('d/m/Y H:i', (int) strtotime($valor)) : '—';
}

if (!podeGerenciarUsuarios($usuarioLogado['perfil'])) {
    http_response_code(403);
    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="alert alert-danger" role="alert">
        Você não tem permissão para gerenciar usuários. Somente gerente, RH e administradores podem acessar esta área.
    </div>
    <a class="btn btn-outline-secondary" href="/dashboard/index.php">Voltar ao painel</a>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id_usr, nome, email, cpf, perfil, ativo, data_criacao, ultima_consessao FROM usuario WHERE id_usr = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: /dashboard/usuarios/index.php');
    exit;
}

function codigoUsuario(int $id): string
{
    return 'USR' . str_pad((string) $id, 10, '0', STR_PAD_LEFT);
}

$stmtMotorista = $pdo->prepare('SELECT id_mot FROM motorista WHERE email = :email OR cpf = :codigo LIMIT 1');
$stmtMotorista->execute([
    ':email' => $usuario['email'],
    ':codigo' => codigoUsuario((int) $usuario['id_usr']),
]);
$motoristaId = (int) $stmtMotorista->fetchColumn();

$classePerfil = match ($usuario['perfil']) {
    'admin' => 'text-bg-dark',
    'gerente' => 'text-bg-primary',
    'rh' => 'text-bg-info',
    'operador' => 'text-bg-secondary',
    default => 'text-bg-light text-dark',
};

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Usuário atualizado com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 mb-1"><?= h($usuario['nome']) ?></h2>
        <p class="text-muted small mb-2"><?= h($usuario['email']) ?></p>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge rounded-pill <?= $classePerfil ?>"><?= h($usuario['perfil']) ?></span>
            <span class="badge rounded-pill <?= $usuario['ativo'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?></span>
            <?php if ($motoristaId): ?>
                <span class="badge rounded-pill text-bg-info">🧑 Motorista</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="index.php">← Voltar</a>
        <?php if ($motoristaId): ?>
            <a class="btn btn-outline-info" href="/dashboard/motoristas/view.php?id=<?= $motoristaId ?>">🧑 Ver motorista</a>
        <?php else: ?>
            <a class="btn btn-outline-info" href="/dashboard/motoristas/importar.php?id=<?= $id ?>">🧑 Tornar motorista</a>
        <?php endif; ?>
        <a class="btn btn-brand" href="edit.php?id=<?= $id ?>">✏️ Editar</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Dados da conta</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhas = [
                    ['ID', '#' . (int) $usuario['id_usr']],
                    ['Nome', $usuario['nome']],
                    ['E-mail', $usuario['email']],
                    ['CPF', $usuario['cpf'] ?: '—'],
                    ['Perfil', ucfirst($usuario['perfil'])],
                    ['Situação', $usuario['ativo'] ? 'Ativa' : 'Inativa'],
                ];
                foreach ($linhas as [$rotulo, $valor]): ?>
                    <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                        <strong class="small"><?= h($rotulo) ?></strong>
                        <span class="small text-sm-end text-break"><?= h((string) ($valor ?? '')) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="col-12 col-lg-5">
        <section class="card h-100 border-0 shadow-sm">
            <header class="card-header bg-white py-3"><h2 class="h6 mb-0">Atividade</h2></header>
            <div class="list-group list-group-flush">
                <?php
                $linhasAtividade = [
                    ['Criado em', dataHoraCurta($usuario['data_criacao'])],
                    ['Último acesso', dataHoraCurta($usuario['ultima_consessao'])],
                ];
                foreach ($linhasAtividade as [$rotulo, $valor]): ?>
                    <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                        <strong class="small"><?= h($rotulo) ?></strong>
                        <span class="small text-sm-end"><?= h((string) ($valor ?? '')) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body">
                <h3 class="h6">Permissões do perfil</h3>
                <?php if (podeGerenciarUsuarios($usuario['perfil'])): ?>
                    <p class="small text-muted mb-0">✅ Pode cadastrar e gerenciar usuários do sistema.</p>
                <?php else: ?>
                    <p class="small text-muted mb-0">Acesso restrito às áreas operacionais do painel.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
