<?php

declare(strict_types=1);

// Garante que exista uma sessão ativa antes de qualquer verificação.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Sem usuário na sessão, volta para o login.
if (empty($_SESSION['id_usr'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/connect.php';

// Recarrega o usuário do banco: garante que ele ainda existe e está ativo.
$stmt = $pdo->prepare('SELECT id_usr, nome, email, perfil FROM usuario WHERE id_usr = :id AND ativo = TRUE LIMIT 1');
$stmt->execute([':id' => $_SESSION['id_usr']]);
$usuarioLogado = $stmt->fetch();

if (!$usuarioLogado) {
    $_SESSION = [];
    session_destroy();
    header('Location: /login.php');
    exit;
}

// Perfis com permissão para criar e gerenciar usuários.
function podeGerenciarUsuarios(string $perfil): bool
{
    return in_array($perfil, ['admin', 'gerente', 'rh'], true);
}
