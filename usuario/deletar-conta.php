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

<?php if ($erro !== ''): ?>
    <p class="alerta is-erro"><?= h($erro) ?></p>
<?php endif; ?>

<p class="alerta is-erro">
    <strong>Atenção:</strong> a conta de <strong><?= h($conta['nome']) ?></strong> e todos os seus dados serão removidos
    permanentemente. Você será desconectado ao concluir a exclusão.
</p>

<div class="page-head">
    <div>
        <h2>Confirmar exclusão</h2>
        <p>Informe sua senha e confirme a operação para continuar.</p>
    </div>
</div>

<form method="post" class="form-card">
    <div class="form-grid">
        <label class="campo full">
            Senha da conta
            <input type="password" name="senha" autocomplete="current-password" placeholder="Digite sua senha atual" required>
        </label>

        <label class="campo full">
            <span>Confirmação</span>
            <span><input type="checkbox" name="confirmar" value="1" required> Quero excluir minha conta permanentemente</span>
        </label>
    </div>

    <div class="form-acoes">
        <button type="submit" class="btn btn-danger">Excluir minha conta</button>
        <a class="btn btn-light" href="/usuario/index.php">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
