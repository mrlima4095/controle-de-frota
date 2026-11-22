<?php

declare(strict_types=1);

// O header pode ser incluído direto, então garantimos o usuário logado aqui.
if (!isset($usuarioLogado)) {
    require_once __DIR__ . '/auth.php';
}

$pageTitle = $pageTitle ?? 'Visão geral';
$pageSubtitle = $pageSubtitle ?? '';
$activeMenu = $activeMenu ?? 'visao';

// Menu lateral agrupado por área do sistema.
$menuGrupos = [
    'Operação' => [
        ['visao', 'Visão geral', '/dashboard/index.php', '◫'],
        ['veiculos', 'Veículos', '/dashboard/veiculos/index.php', '▤'],
        ['motoristas', 'Motoristas', '/dashboard/motoristas/index.php', '◍'],
        ['viagens', 'Viagens', '/dashboard/viagens/index.php', '➔'],
        ['abastecimentos', 'Abastecimentos', '/dashboard/abastecimentos/index.php', '◈'],
        ['manutencao', 'Manutenção', '/dashboard/manutencao/index.php', '⚙'],
        ['pneus', 'Pneus', '/dashboard/pneus/index.php', '◎'],
    ],
    'Gestão' => [
        ['despesas', 'Despesas', '/dashboard/despesas/index.php', '▦'],
        ['fornecedores', 'Fornecedores', '/dashboard/fornecedores/index.php', '⌂'],
        ['documentos', 'Documentos', '/dashboard/documentos/index.php', '❐'],
        ['sinistros', 'Sinistros', '/dashboard/sinistros/index.php', '⚠'],
        ['relatorios', 'Relatórios', '/dashboard/relatorios/index.php', '▥'],
        ['usuarios', 'Usuários', '/dashboard/usuarios/index.php', '☗'],
    ],
];

// Reaproveitado no menu fixo (desktop) e no offcanvas (mobile).
function renderizarMenu(array $grupos, string $activeMenu): void
{
    foreach ($grupos as $grupo => $itens): ?>
        <p class="menu-group mb-1"><?= htmlspecialchars((string) $grupo) ?></p>
        <?php foreach ($itens as [$slug, $label, $href, $icone]): ?>
            <a class="nav-link menu-link<?= $activeMenu === $slug ? ' active' : '' ?>" href="<?= $href ?>">
                <span class="menu-icon" aria-hidden="true"><?= $icone ?></span>
                <span><?= htmlspecialchars($label) ?></span>
            </a>
        <?php endforeach; ?>
    <?php endforeach; ?>
    <a class="nav-link menu-link menu-logout mt-3" href="/logout.php">
        <span class="menu-icon" aria-hidden="true">⏻</span>
        <span>Sair</span>
    </a>
    <?php
}

$primeiraLetra = strtoupper(substr($usuarioLogado['nome'], 0, 1));
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($pageTitle) ?> | DriveHub</title>
        <link rel="stylesheet" href="/static/vendor/bootstrap-5.3.3/css/bootstrap.min.css">
        <link rel="stylesheet" href="/static/css/app.css">
        <script src="/static/vendor/bootstrap-5.3.3/js/bootstrap.bundle.min.js" defer></script>
    </head>
    <body>
        <div class="app-shell">
            <aside class="app-sidebar d-none d-lg-flex flex-column" aria-label="Menu lateral">
                <a class="brand" href="/dashboard/index.php">
                    <span class="brand-mark">↗</span>
                    <span>DriveHub</span>
                </a>

                <nav class="nav flex-column flex-grow-1 overflow-auto py-2">
                    <?php renderizarMenu($menuGrupos, $activeMenu); ?>
                </nav>
            </aside>

            <div class="offcanvas offcanvas-start app-offcanvas" tabindex="-1" id="menuLateral" aria-labelledby="menuLateralLabel">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title brand" id="menuLateralLabel">
                        <span class="brand-mark">↗</span>
                        <span>DriveHub</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar menu"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <nav class="nav flex-column py-2">
                        <?php renderizarMenu($menuGrupos, $activeMenu); ?>
                    </nav>
                </div>
            </div>

            <div class="app-content">
                <header class="app-topbar sticky-top d-flex align-items-center justify-content-between gap-3 px-3 px-lg-4 py-3">
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <button class="btn btn-outline-secondary d-lg-none flex-shrink-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-controls="menuLateral" aria-label="Abrir menu">☰</button>
                        <div class="min-w-0">
                            <h1 class="h4 mb-0 text-truncate"><?= htmlspecialchars($pageTitle) ?></h1>
                            <?php if ($pageSubtitle !== ''): ?>
                                <p class="text-muted small mb-0 d-none d-sm-block text-truncate"><?= htmlspecialchars($pageSubtitle) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a class="user-chip flex-shrink-0" href="/usuario/index.php" title="Minha conta">
                        <span class="avatar" aria-hidden="true"><?= htmlspecialchars($primeiraLetra) ?></span>
                        <span class="d-none d-sm-flex flex-column lh-sm">
                            <strong class="small"><?= htmlspecialchars($usuarioLogado['nome']) ?></strong>
                            <small class="text-muted text-capitalize"><?= htmlspecialchars($usuarioLogado['perfil']) ?></small>
                        </span>
                    </a>
                </header>

                <main class="container-fluid px-3 px-lg-4 py-4">
