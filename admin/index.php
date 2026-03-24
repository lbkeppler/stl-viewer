<?php
// ============================================
// Dashboard do Administrador
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();

// Estatísticas gerais
$totalUsers    = $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalProjects = $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$totalFiles    = $db->query("SELECT COUNT(*) FROM model_files")->fetchColumn();
$totalSize     = $db->query("SELECT COALESCE(SUM(file_size),0) FROM model_files")->fetchColumn();

// Últimos projetos criados
$recentProjects = $db->query("
    SELECT p.*, u.name AS creator_name, COUNT(sf.id) AS file_count
    FROM projects p
    JOIN users u ON u.id = p.created_by
    LEFT JOIN model_files sf ON sf.project_id = p.id
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 6
")->fetchAll();

// Últimos usuários
$recentUsers = $db->query("
    SELECT * FROM users WHERE role = 'user'
    ORDER BY created_at DESC LIMIT 5
")->fetchAll();

$pageTitle = 'Dashboard';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                Dashboard
            </h1>
            <p>Visão geral do sistema</p>
        </div>
        <div class="d-flex gap-1">
            <a href="<?= BASE_URL ?>/admin/users.php?action=new" class="btn btn-secondary btn-sm">
                + Novo Usuário
            </a>
            <a href="<?= BASE_URL ?>/admin/projects.php?action=new" class="btn btn-primary btn-sm">
                + Novo Projeto
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="stat-info">
                <p>Usuários</p>
                <h3><?= $totalUsers ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
            </div>
            <div class="stat-info">
                <p>Projetos</p>
                <h3><?= $totalProjects ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                    <polyline points="13 2 13 9 20 9"/>
                </svg>
            </div>
            <div class="stat-info">
                <p>Arquivos STL</p>
                <h3><?= $totalFiles ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon red">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                </svg>
            </div>
            <div class="stat-info">
                <p>Armazenamento</p>
                <h3 style="font-size:1.2rem;"><?= formatBytes((int)$totalSize) ?></h3>
            </div>
        </div>
    </div>

    <!-- Grid: Projetos recentes + Usuários recentes -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;align-items:start;">

        <!-- Projetos Recentes -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                    Projetos Recentes
                </h3>
                <a href="<?= BASE_URL ?>/admin/projects.php" class="btn btn-secondary btn-sm">
                    Ver todos
                </a>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($recentProjects)): ?>
                    <p class="text-muted text-center" style="padding:1.5rem;">
                        Nenhum projeto cadastrado.
                    </p>
                <?php else: ?>
                    <div style="display:flex;flex-direction:column;">
                        <?php foreach ($recentProjects as $p): ?>
                            <div style="display:flex;align-items:center;justify-content:space-between;
                                        padding:11px 16px;border-bottom:1px solid #f1f5f9;">
                                <div>
                                    <div style="font-size:0.875rem;font-weight:600;color:#1e293b;">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </div>
                                    <div style="font-size:0.76rem;color:#94a3b8;">
                                        <?= $p['file_count'] ?> arquivo(s) •
                                        por <?= htmlspecialchars($p['creator_name']) ?>
                                    </div>
                                </div>
                                <div style="display:flex;gap:6px;align-items:center;">
                                    <span class="badge <?= $p['active'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $p['active'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                    <a href="<?= BASE_URL ?>/admin/projects.php?action=edit&id=<?= $p['id'] ?>"
                                       class="btn btn-secondary btn-sm">Editar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Usuários Recentes -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                    </svg>
                    Últimos Usuários
                </h3>
                <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-secondary btn-sm">
                    Ver todos
                </a>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($recentUsers)): ?>
                    <p class="text-muted text-center" style="padding:1.5rem;">
                        Nenhum usuário cadastrado.
                    </p>
                <?php else: ?>
                    <div style="display:flex;flex-direction:column;">
                        <?php foreach ($recentUsers as $u): ?>
                            <div style="display:flex;align-items:center;justify-content:space-between;
                                        padding:11px 16px;border-bottom:1px solid #f1f5f9;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:34px;height:34px;background:#dbeafe;
                                                border-radius:50%;display:flex;align-items:center;
                                                justify-content:center;color:#2563eb;
                                                font-weight:700;font-size:0.9rem;flex-shrink:0;">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-size:0.875rem;font-weight:600;color:#1e293b;">
                                            <?= htmlspecialchars($u['name']) ?>
                                        </div>
                                        <div style="font-size:0.76rem;color:#94a3b8;">
                                            <?= htmlspecialchars($u['email']) ?>
                                        </div>
                                    </div>
                                </div>
                                <span class="badge <?= $u['active'] ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $u['active'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /grid -->

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
