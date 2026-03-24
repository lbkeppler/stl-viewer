<?php
// ============================================
// Header / Navbar global do sistema
// ============================================
require_once __DIR__ . '/../includes/auth.php';
$isAdmin      = isAdmin();
$userName     = getCurrentUserName();
$currentFile  = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'STL Viewer') ?> — STL Viewer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>
<div class="app-wrapper">

    <nav class="navbar">
        <a href="<?= BASE_URL ?>/<?= $isAdmin ? 'admin/index.php' : 'dashboard.php' ?>" class="navbar-brand">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5"/>
                <path d="M2 12l10 5 10-5"/>
            </svg>
            STL Viewer
        </a>

        <ul class="navbar-nav">
            <?php if ($isAdmin): ?>
                <li><a href="<?= BASE_URL ?>/admin/index.php"
                       class="<?= $currentFile === 'index.php' && strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'active' : '' ?>">
                    Dashboard
                </a></li>
                <li><a href="<?= BASE_URL ?>/admin/users.php"
                       class="<?= $currentFile === 'users.php' ? 'active' : '' ?>">
                    Usuários
                </a></li>
                <li><a href="<?= BASE_URL ?>/admin/projects.php"
                       class="<?= $currentFile === 'projects.php' ? 'active' : '' ?>">
                    Projetos
                </a></li>
            <?php else: ?>
                <li><a href="<?= BASE_URL ?>/dashboard.php"
                       class="<?= $currentFile === 'dashboard.php' ? 'active' : '' ?>">
                    Meus Projetos
                </a></li>
            <?php endif; ?>
        </ul>

        <div class="navbar-user">
            <span><?= htmlspecialchars($userName) ?></span>
            <span class="user-badge <?= $isAdmin ? 'admin' : '' ?>">
                <?= $isAdmin ? 'Admin' : 'Usuário' ?>
            </span>
            <a href="<?= BASE_URL ?>/logout.php" class="btn-logout">Sair</a>
        </div>
    </nav>
