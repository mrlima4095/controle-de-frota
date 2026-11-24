<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Editar usuário';
$pageSubtitle = 'Atualize os dados e o acesso da conta.';
$activeMenu = 'usuarios';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
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

$perfis = ['admin', 'gerente', 'rh', 'operador', 'visualizador'];
$ehPropriaConta = $id === (int) $usuarioLogado['id_usr'];

$nome = $usuario['nome'];
$email = $usuario['email'];
$cpf = $usuario['cpf'] ?? '';
$perfil = $usuario['perfil'];
$ativo = (bool) $usuario['ativo'];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $cpf = trim((string) ($_POST['cpf'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $perfil = (string) ($_POST['perfil'] ?? $usuario['perfil']);
    $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

    if (!in_array($perfil, $perfis, true)) {
        $perfil = $usuario['perfil'];
    }

    
    if ($ehPropriaConta) {
        $ativo = true;
        $perfil = $usuario['perfil'];
    }

    if ($nome === '') {
        $erro = 'Informe o nome do usuário.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif ($cpf === '') {
        $erro = 'Informe o CPF do usuário.';
    } elseif (strlen($cpf) > 14) {
        $erro = 'Informe um CPF com até 14 caracteres.';
    } elseif ($senha !== '' && strlen($senha) < 6) {
        $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
    } else {
        $check = $pdo->prepare('SELECT id_usr FROM usuario WHERE (email = :email OR cpf = :cpf) AND id_usr <> :id LIMIT 1');
        $check->execute([':email' => $email, ':cpf' => $cpf, ':id' => $id]);

        if ($check->fetch()) {
            $erro = 'Este e-mail ou CPF já está cadastrado em outro usuário.';
        } else {
            if ($senha !== '') {
                $sql = 'UPDATE usuario SET nome = :nome, email = :email, cpf = :cpf, senha = :senha, perfil = :perfil, ativo = :ativo WHERE id_usr = :id';
                $parametros = [
                    ':nome' => $nome,
                    ':email' => $email,
                    ':cpf' => $cpf,
                    ':senha' => password_hash($senha, PASSWORD_DEFAULT),
                    ':perfil' => $perfil,
                    ':ativo' => $ativo ? 1 : 0,
                    ':id' => $id,
                ];
            } else {
                $sql = 'UPDATE usuario SET nome = :nome, email = :email, cpf = :cpf, perfil = :perfil, ativo = :ativo WHERE id_usr = :id';
                $parametros = [
                    ':nome' => $nome,
                    ':email' => $email,
                    ':cpf' => $cpf,
                    ':perfil' => $perfil,
                    ':ativo' => $ativo ? 1 : 0,
                    ':id' => $id,
                ];
            }

            try {
                $pdo->prepare($sql)->execute($parametros);
            } catch (PDOException $exception) {
                $erro = 'Não foi possível salvar o usuário. Tente novamente.';
            }

            if ($erro === '') {
                header('Location: /dashboard/usuarios/view.php?id=' . $id . '&ok=1');
                exit;
            }
        }
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<?php if ($erro !== ''): ?>
    <div class="alert alert-danger" role="alert"><?= h($erro) ?></div>
<?php endif; ?>

<?php if ($ehPropriaConta): ?>
    <div class="alert alert-info" role="alert">
       Você está editando a sua própria conta. Por segurança, não é possível alterar seu perfil ou desativar seu acesso aqui.
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between gap-2">
                <h2 class="h6 mb-0">Editando <?= h($usuario['nome']) ?></h2>
                <a class="btn btn-sm btn-outline-secondary" href="view.php?id=<?= $id ?>">👁️ Ver detalhes</a>
            </div>
            <div class="card-body p-3 p-sm-4">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="nome" class="form-label fw-semibold small">Nome completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= h($nome) ?>" maxlength="100" autocomplete="name" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label fw-semibold small">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= h($email) ?>" maxlength="100" autocomplete="email" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="cpf" class="form-label fw-semibold small">CPF *</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" value="<?= h($cpf) ?>" maxlength="14" placeholder="000.000.000-00" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="senha" class="form-label fw-semibold small">Nova senha (opcional)</label>
                            <input type="password" class="form-control" id="senha" name="senha" autocomplete="new-password" minlength="6" placeholder="Deixe em branco para manter">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="perfil" class="form-label fw-semibold small">Perfil de acesso</label>
                            <select class="form-select" id="perfil" name="perfil"<?= $ehPropriaConta ? ' disabled' : '' ?> required>
                                <?php foreach ($perfis as $opcao): ?>
                                    <option value="<?= $opcao ?>"<?= $perfil === $opcao ? ' selected' : '' ?>><?= ucfirst($opcao) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"<?= $ativo ? ' checked' : '' ?><?= $ehPropriaConta ? ' disabled' : '' ?>>
                                <label class="form-check-label" for="ativo">Conta ativa (permite login)</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                        <button type="submit" class="btn btn-brand">Salvar alterações</button>
                        <a class="btn btn-outline-secondary" href="index.php">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
