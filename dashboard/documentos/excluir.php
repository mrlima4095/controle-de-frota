<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

if (!podeExcluirDocumentos($usuarioLogado['perfil'])) {
    http_response_code(403);
    $pageTitle = 'Acesso negado';
    $activeMenu = 'documentos';
    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="alert alert-danger" role="alert">Você não tem permissão para excluir documentos. Somente gerente, RH e administradores podem excluir.</div>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método não permitido.');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM documento WHERE id_doc = :id');
    $stmt->execute([':id' => $id]);
}

header('Location: /dashboard/documentos/index.php?excluido=1');
exit;
