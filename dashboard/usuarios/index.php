<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Usuários';
$pageSubtitle = 'Contas com acesso ao sistema.';
$activeMenu = 'usuarios';

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

$busca = trim($_GET['q'] ?? '');

$sql = 'SELECT id_usr, nome, email, cpf, perfil, ativo, data_criacao, ultima_consessao FROM usuario';
$parametros = [];
if ($busca !== '') {
    $sql .= ' WHERE nome LIKE :q OR email LIKE :q OR cpf LIKE :q OR perfil LIKE :q';
    $parametros[':q'] = '%' . $busca . '%';
}
$sql .= ' ORDER BY nome ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$usuarios = $stmt->fetchAll();

function dataHoraCurta(?string $valor): string
{
    return $valor ? date('d/m/Y H:i', (int) strtotime($valor)) : '—';
}

function classePerfil(string $perfil): string
{
    return match ($perfil) {
        'admin' => 'text-bg-dark',
        'gerente' => 'text-bg-primary',
        'rh' => 'text-bg-info',
        'operador' => 'text-bg-secondary',
        default => 'text-bg-light text-dark',
    };
}

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" role="alert">Usuário salvo com sucesso.</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h5 mb-1">Contas cadastradas</h2>
        <p class="text-muted small mb-0">Total de <?= count($usuarios) ?> usuário(s) no sistema.</p>
    </div>
    <a class="btn btn-brand" href="/dashboard/usuarios/create.php"><i class="fa-solid fa-plus"></i> Novo usuário</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-12 col-sm-8 col-lg-6">
        <input type="search" class="form-control" name="q" value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome, e-mail, CPF ou perfil">
    </div>
    <div class="col-12 col-sm-auto">
        <button type="submit" class="btn btn-outline-secondary w-100"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">Nome</th>
                    <th scope="col">E-mail</th>
                    <th scope="col">CPF</th>
                    <th scope="col">Perfil</th>
                    <th scope="col">Status</th>
                    <th scope="col">Criado em</th>
                    <th scope="col">Último acesso</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$usuarios): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Nenhum usuário cadastrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($usuario['nome']) ?></strong>
                                <small class="d-block text-muted">#<?= (int) $usuario['id_usr'] ?></small>
                            </td>
                            <td class="small text-break"><?= htmlspecialchars($usuario['email']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($usuario['cpf'] ?? '—') ?></td>
                            <td><span class="badge rounded-pill <?= classePerfil($usuario['perfil']) ?>"><?= htmlspecialchars($usuario['perfil']) ?></span></td>
                            <td>
                                <span class="badge rounded-pill <?= $usuario['ativo'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="small text-muted text-nowrap"><?= dataHoraCurta($usuario['data_criacao']) ?></td>
                            <td class="small text-muted text-nowrap"><?= dataHoraCurta($usuario['ultima_consessao']) ?></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int) $usuario['id_usr'] ?>"><i class="fa-solid fa-eye"></i> Ver</a>
                                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int) $usuario['id_usr'] ?>"><i class="fa-solid fa-pen"></i> Editar</a>
                                <a class="btn btn-sm btn-outline-info" href="/dashboard/motoristas/importar.php?id=<?= (int) $usuario['id_usr'] ?>"><i class="fa-solid fa-user"></i> Motorista</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
