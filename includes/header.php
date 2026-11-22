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

$primeiraLetra = strtoupper(substr($usuarioLogado['nome'], 0, 1));
?>
<!doctype html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($pageTitle) ?> | DriveHub</title>
        <link rel="stylesheet" href="/static/css/global.css">
        <link rel="stylesheet" href="/static/css/layout.css">
        <link rel="stylesheet" href="/static/css/dashboard.css">
        <link rel="stylesheet" href="/static/css/tabelas.css">
        <link rel="stylesheet" href="/static/css/formularios.css">
        <link rel="stylesheet" href="/static/css/responsivo.css">
    </head>
    <body>
        <div class="app">
            <aside class="sidebar">
                <a class="brand" href="/dashboard/index.php">
                    <span class="brand-mark">↗</span>
                    <span>DriveHub</span>
                </a>

                <nav class="menu" aria-label="Menu principal">
                    <?php foreach ($menuGrupos as $grupo => $itens): ?>
                        <p class="menu-group"><?= htmlspecialchars($grupo) ?></p>
                        <?php foreach ($itens as [$slug, $label, $href, $icone]): ?>
                            <a class="menu-link<?= $activeMenu === $slug ? ' is-active' : '' ?>" href="<?= $href ?>">
                                <span class="menu-icon" aria-hidden="true"><?= $icone ?></span>
                                <span><?= htmlspecialchars($label) ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </nav>

                <a class="menu-link menu-logout" href="/logout.php">
                    <span class="menu-icon" aria-hidden="true">⏻</span>
                    <span>Sair</span>
                </a>
            </aside>

            <main class="main">
                <header class="topbar">
                    <div>
                        <h1><?= htmlspecialchars($pageTitle) ?></h1>
                        <?php if ($pageSubtitle !== ''): ?>
                            <p class="topbar-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
                        <?php endif; ?>
                    </div>
                    <a class="user-chip" href="/usuario/index.php" title="Minha conta">
                        <span class="avatar" aria-hidden="true"><?= htmlspecialchars($primeiraLetra) ?></span>
                        <span class="user-info">
                            <strong><?= htmlspecialchars($usuarioLogado['nome']) ?></strong>
                            <small><?= htmlspecialchars($usuarioLogado['perfil']) ?></small>
                        </span>
                    </a>
                </header>

                <div class="content">
