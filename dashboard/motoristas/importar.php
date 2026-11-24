<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'Importar motorista';
$pageSubtitle = 'Transforme um usuário do sistema em condutor da frota.';
$activeMenu = 'motoristas';

function h(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function codigoInterno(int $id): string
{
    return 'USR' . str_pad((string) $id, 10, '0', STR_PAD_LEFT);
}

$sqlDisponiveis = 'SELECT u.id_usr, u.nome, u.email, u.ativo
                   FROM usuario u
                   WHERE NOT EXISTS (
                       SELECT 1 FROM motorista m
                       WHERE m.email = u.email
                          OR m.cpf = CONCAT(\'USR\', LPAD(u.id_usr, 10, \'0\'))
                   )
                   ORDER BY u.nome ASC';
$disponiveis = $pdo->query($sqlDisponiveis)->fetchAll();

$idUsuario = (int) ($_POST['id_usr'] ?? $_GET['id'] ?? 0);

function buscarUsuario(PDO $pdo, int $id): array|false
{
    $stmt = $pdo->prepare('SELECT id_usr, nome, email, cpf, ativo FROM usuario WHERE id_usr = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function buscarMotorista(PDO $pdo, array $usuario): array|false
{
    $stmt = $pdo->prepare('SELECT id_mot, nome, email FROM motorista WHERE email = :email OR cpf = :codigo LIMIT 1');
    $stmt->execute([
        ':email' => $usuario['email'],
        ':codigo' => codigoInterno((int) $usuario['id_usr']),
    ]);
    return $stmt->fetch();
}

$usuario = $idUsuario > 0 ? buscarUsuario($pdo, $idUsuario) : false;
$motoristaExistente = $usuario ? buscarMotorista($pdo, $usuario) : false;

$dados = [
    'cpf' => '', 'cnh' => '', 'validade_cnh' => '',
    'telefone' => '', 'uf' => '', 'cidade' => '', 'ativo' => 1,
];
if ($usuario) {
    $dados['cpf'] = $usuario['cpf'] ?? '';
}
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUsuario = (int) ($_POST['id_usr'] ?? 0);
    $usuario = buscarUsuario($pdo, $idUsuario);

    foreach (array_keys($dados) as $campo) {
        if ($campo === 'ativo') {
            $dados[$campo] = isset($_POST['ativo']) && $_POST['ativo'] === '1' ? 1 : 0;
        } else {
            $dados[$campo] = trim((string) ($_POST[$campo] ?? ''));
        }
    }

    $dados['uf'] = strtoupper($dados['uf']);

    if ($usuario && ($usuario['cpf'] ?? '') !== '') {
        $dados['cpf'] = $usuario['cpf'];
    }

    if (!$usuario) {
        $erro = 'Selecione um usuário válido.';
    } elseif (strlen($dados['cpf']) > 14) {
        $erro = 'Informe um CPF com até 14 caracteres.';
    } else {
        $cpfFinal = $dados['cpf'] !== '' ? $dados['cpf'] : codigoInterno($idUsuario);
        $motoristaExistente = buscarMotorista($pdo, $usuario);

        if (!$motoristaExistente) {
            $checkCpf = $pdo->prepare('SELECT id_mot FROM motorista WHERE cpf = :cpf LIMIT 1');
            $checkCpf->execute([':cpf' => $cpfFinal]);
            if ($checkCpf->fetch()) {
                $erro = 'Este CPF já está cadastrado para outro motorista.';
            }
        }

        if ($erro === '' && $motoristaExistente) {
            $erro = 'Este usuário já possui um cadastro de motorista.';
        } elseif ($erro === '') {
            $sql = 'INSERT INTO motorista
                        (nome, cpf, cnh, validade_cnh, telefone, email, uf, cidade, ativo)
                    VALUES
                        (:nome, :cpf, :cnh, :validade_cnh, :telefone, :email, :uf, :cidade, :ativo)';

            try {
                $pdo->prepare($sql)->execute([
                    ':nome' => $usuario['nome'],
                    ':cpf' => $cpfFinal,
                    ':cnh' => $dados['cnh'] !== '' ? $dados['cnh'] : null,
                    ':validade_cnh' => $dados['validade_cnh'] !== '' ? $dados['validade_cnh'] : null,
                    ':telefone' => $dados['telefone'],
                    ':email' => $usuario['email'],
                    ':uf' => $dados['uf'],
                    ':cidade' => $dados['cidade'],
                    ':ativo' => $dados['ativo'],
                ]);
            } catch (PDOException $exception) {
                $erro = 'Não foi possível importar o motorista. Tente novamente.';
            }

            if ($erro === '') {
                header('Location: /dashboard/motoristas/index.php?ok=importado');
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

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
    <div>
        <h2 class="h5 mb-1">Importar usuário como motorista</h2>
        <p class="text-muted small mb-0">O nome e o e-mail vêm da conta do sistema. Você só precisa completar os dados do condutor.</p>
    </div>
    <a class="btn btn-outline-secondary" href="index.php">← Voltar aos motoristas</a>
</div>

<?php if ($motoristaExistente): ?>
    <div class="alert alert-warning d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2" role="alert">
        <span>✅ <strong><?= h($motoristaExistente['nome']) ?></strong> já está cadastrado como motorista.</span>
        <a class="btn btn-sm btn-outline-primary" href="edit.php?id=<?= (int) $motoristaExistente['id_mot'] ?>">✏️ Editar cadastro</a>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h3 class="h6 mb-0">Dados do condutor</h3>
            </div>
            <div class="card-body p-3 p-sm-4">
                <?php if ($usuario && !$motoristaExistente): ?>
                    <div class="alert alert-light border d-flex flex-column flex-sm-row justify-content-between gap-2 small">
                        <span><strong><?= h($usuario['nome']) ?></strong><br><span class="text-muted"><?= h($usuario['email']) ?></span></span>
                        <a class="btn btn-sm btn-outline-secondary align-self-start" href="importar.php">Trocar usuário</a>
                    </div>
                    <form method="post">
                        <input type="hidden" name="id_usr" value="<?= (int) $usuario['id_usr'] ?>">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="cpf" class="form-label fw-semibold small">CPF</label>
                                <input type="text" class="form-control" id="cpf" name="cpf" value="<?= h($dados['cpf']) ?>" maxlength="14" placeholder="000.000.000-00"<?= ($usuario['cpf'] ?? '') !== '' ? ' readonly' : '' ?>>
                                <small class="form-text text-muted"><?= ($usuario['cpf'] ?? '') !== '' ? 'CPF da conta do usuário.' : 'Se deixar em branco, o sistema usa um código interno. Você pode editar depois.' ?></small>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="cnh" class="form-label fw-semibold small">CNH</label>
                                <input type="text" class="form-control" id="cnh" name="cnh" value="<?= h($dados['cnh']) ?>" maxlength="20">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="validade_cnh" class="form-label fw-semibold small">Validade da CNH</label>
                                <input type="date" class="form-control" id="validade_cnh" name="validade_cnh" value="<?= h($dados['validade_cnh']) ?>">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="telefone" class="form-label fw-semibold small">Telefone</label>
                                <input type="tel" class="form-control" id="telefone" name="telefone" value="<?= h($dados['telefone']) ?>" maxlength="20" placeholder="(00) 00000-0000">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="uf" class="form-label fw-semibold small">UF</label>
                                <input type="text" class="form-control" id="uf" name="uf" value="<?= h($dados['uf']) ?>" maxlength="2" placeholder="SP">
                            </div>
                            <div class="col-12 col-md-8">
                                <label for="cidade" class="form-label fw-semibold small">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade" value="<?= h($dados['cidade']) ?>" maxlength="100">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"<?= $dados['ativo'] ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="ativo">Motorista ativo</label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                            <button type="submit" class="btn btn-brand">Importar como motorista</button>
                            <a class="btn btn-outline-secondary" href="create.php">Cadastrar sem ser usuário</a>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="text-muted small mb-0">Selecione um usuário na lista ao lado para começar.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between gap-2">
                <h3 class="h6 mb-0">Usuários disponíveis</h3>
                <span class="badge rounded-pill text-bg-light text-dark"><?= count($disponiveis) ?></span>
            </div>
            <div class="list-group list-group-flush">
                <?php if (!$disponiveis): ?>
                    <div class="list-group-item text-muted small">Todos os usuários já são motoristas.</div>
                <?php else: ?>
                    <?php foreach ($disponiveis as $opcao): ?>
                        <div class="list-group-item d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
                            <div class="small">
                                <strong><?= h($opcao['nome']) ?></strong>
                                <span class="d-block text-muted"><?= h($opcao['email']) ?></span>
                                <span class="badge rounded-pill <?= $opcao['ativo'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $opcao['ativo'] ? 'Ativo' : 'Inativo' ?></span>
                            </div>
                            <a class="btn btn-sm btn-outline-primary" href="importar.php?id=<?= (int) $opcao['id_usr'] ?>">Importar</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
