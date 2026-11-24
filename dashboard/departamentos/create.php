<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Novo departamento';
$pageSubtitle = 'Cadastre uma área para organizar os veículos.';
$activeMenu = 'departamentos';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$dados = ['nome' => '', 'responsavel' => '', 'ativo' => 1];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome'] = trim((string) ($_POST['nome'] ?? ''));
    $dados['responsavel'] = trim((string) ($_POST['responsavel'] ?? ''));
    $dados['ativo'] = isset($_POST['ativo']) && $_POST['ativo'] === '1' ? 1 : 0;

    if ($dados['nome'] === '') {
        $erro = 'Informe o nome do departamento.';
    } else {
        $check = $pdo->prepare('SELECT id_departamento FROM departamento WHERE nome = :nome LIMIT 1');
        $check->execute([':nome' => $dados['nome']]);

        if ($check->fetch()) {
            $erro = 'Este departamento já está cadastrado.';
        } else {
            $sql = 'INSERT INTO departamento (nome, responsavel, ativo) VALUES (:nome, :responsavel, :ativo)';

            try {
                $pdo->prepare($sql)->execute([
                    ':nome' => $dados['nome'],
                    ':responsavel' => $dados['responsavel'] !== '' ? $dados['responsavel'] : null,
                    ':ativo' => $dados['ativo'],
                ]);
            } catch (PDOException $exception) {
                $erro = 'Não foi possível cadastrar o departamento. Tente novamente.';
            }

            if ($erro === '') {
                header('Location: /dashboard/departamentos/index.php?ok=1');
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

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h6 mb-0">Dados do departamento</h2>
            </div>
            <div class="card-body p-3 p-sm-4">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <label for="nome" class="form-label fw-semibold small">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= h($dados['nome']) ?>" maxlength="100" placeholder="Ex.: Operações" required>
                        </div>
                        <div class="col-12 col-md-5">
                            <label for="responsavel" class="form-label fw-semibold small">Responsável</label>
                            <input type="text" class="form-control" id="responsavel" name="responsavel" value="<?= h($dados['responsavel']) ?>" maxlength="100">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"<?= $dados['ativo'] ? ' checked' : '' ?>>
                                <label class="form-check-label" for="ativo">Departamento ativo</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                        <button type="submit" class="btn btn-brand">Cadastrar departamento</button>
                        <a class="btn btn-outline-secondary" href="index.php">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
