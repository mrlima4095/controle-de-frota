<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Excluir conta';
$pageSubtitle = 'Esta ação é permanente e não pode ser desfeita.';
$activeMenu = '';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$stmt = $pdo->prepare('SELECT nome, senha FROM usuario WHERE id_usr = :id LIMIT 1');
$stmt->execute([':id' => $_SESSION['id_usr']]);
$conta = $stmt->fetch();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    $confirmado = isset($_POST['confirmar']) && $_POST['confirmar'] === '1';

    if (!$confirmado) {
        $erro = 'Confirme que deseja excluir sua conta.';
    } elseif ($senha === '') {
        $erro = 'Informe sua senha para confirmar a exclusão.';
    } elseif (!password_verify($senha, $conta['senha'])) {
        $erro = 'Senha incorreta.';
    } else {
        try {
            $pdo->beginTransaction();

            // A tabela sessao referencia usuario, por isso é removida primeiro.
            $pdo->prepare('DELETE FROM sessao WHERE id_usr = :id')->execute([':id' => $_SESSION['id_usr']]);
            $pdo->prepare('DELETE FROM usuario WHERE id_usr = :id')->execute([':id' => $_SESSION['id_usr']]);

            $pdo->commit();
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erro = 'Não foi possível excluir a conta. Tente novamente mais tarde.';
        }

        if ($erro === '') {
            // Encerra a sessão local da mesma forma que o logout.
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();

            header('Location: /login.php');
            exit;
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <?php if ($erro !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= h($erro) ?></div>
        <?php endif; ?>

        <div class="alert alert-danger" role="alert">
            <strong>Atenção:</strong> a conta de <strong><?= h($conta['nome']) ?></strong> e todos os seus dados serão removidos
            permanentemente. Você será desconectado ao concluir a exclusão.
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h6 mb-0">Confirmar exclusão</h2>
            </div>
            <div class="card-body p-3 p-sm-4">
                <p class="text-muted small mb-4">Informe sua senha e confirme a operação para continuar.</p>

                <form method="post">
                    <div class="mb-3">
                        <label for="senha" class="form-label fw-semibold small">Senha da conta</label>
                        <input type="password" class="form-control" id="senha" name="senha" autocomplete="current-password" placeholder="Digite sua senha atual" required>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="confirmar" value="1" id="confirmar" required>
                        <label class="form-check-label" for="confirmar">Quero excluir minha conta permanentemente</label>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <button type="submit" class="btn btn-danger">Excluir minha conta</button>
                        <a class="btn btn-outline-secondary" href="/usuario/index.php">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
