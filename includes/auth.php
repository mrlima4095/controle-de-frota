<?php

declare(strict_types=1);


if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


if (empty($_SESSION['id_usr'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/connect.php';


$stmt = $pdo->prepare('SELECT id_usr, nome, email, perfil FROM usuario WHERE id_usr = :id AND ativo = TRUE LIMIT 1');
$stmt->execute([':id' => $_SESSION['id_usr']]);
$usuarioLogado = $stmt->fetch();

if (!$usuarioLogado) {
    $_SESSION = [];
    session_destroy();
    header('Location: /login.php');
    exit;
}


function podeGerenciarUsuarios(string $perfil): bool
{
    return in_array($perfil, ['admin', 'gerente', 'rh'], true);
}

function podeExcluirDocumentos(string $perfil): bool
{
    return in_array($perfil, ['admin', 'gerente', 'rh'], true);
}
