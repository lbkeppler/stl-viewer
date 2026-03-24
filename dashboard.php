<?php
// ============================================
// Dashboard do Usuário Final
// ============================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

// Admin não acessa o dashboard de usuário
if (isAdmin()) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$userId   = getCurrentUserId();
$projects = getProjectsForUser($userId);
$pageTitle = 'Meus Projetos';
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Meus Projetos
            </h1>
            <p>Selecione um projeto para visualizar os arquivos STL</p>
        </div>
    </div>

    <?php if (empty($projects)): ?>
        <div class="card">
            <div class="card-body text-center" style="padding: 3rem;">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none"
                     stroke="#cbd5e1" stroke-width="1.5" style="margin:0 auto 1rem;display:block;">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
                <h3 style="color:#64748b;font-size:1rem;margin-bottom:6px;">
                    Nenhum projeto disponível
                </h3>
                <p class="text-muted" style="font-size:0.875rem;">
                    Aguarde o administrador liberar o acesso a um projeto.
                </p>
            </div>
        </div>

    <?php else: ?>
        <div class="projects-grid">
            <?php foreach ($projects as $project): ?>
                <div class="project-card"
                     onclick="openProject(<?= $project['id'] ?>)"
                     style="cursor:pointer;">

                    <div class="project-card-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>

                    <h3><?= htmlspecialchars($project['name']) ?></h3>

                    <p>
                        <?= $project['description']
                            ? htmlspecialchars($project['description'])
                            : '<span class="text-muted">Sem descrição</span>' ?>
                    </p>

                    <div class="project-card-meta">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2">
                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                            <polyline points="13 2 13 9 20 9"/>
                        </svg>
                        <?= $project['file_count'] ?> arquivo(s) STL
                    </div>

                    <button class="btn btn-primary btn-sm" style="margin-top:4px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        Abrir Projeto
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<!-- Modal: Selecionar arquivo STL do projeto -->
<div class="modal-overlay" id="modal-files">
    <div class="modal">
        <div class="modal-header">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                    <polyline points="13 2 13 9 20 9"/>
                </svg>
                <span id="modal-project-name">Arquivos do Projeto</span>
            </h3>
            <button class="btn-close-modal" onclick="closeModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div id="modal-files-list">
                <p class="text-muted text-center">Carregando...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Fechar</button>
        </div>
    </div>
</div>

<script>
function openProject(projectId) {
    document.getElementById('modal-files').classList.add('open');

    fetch('<?= BASE_URL ?>/api/get_files.php?project_id=' + projectId)
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('modal-files-list');
            document.getElementById('modal-project-name').textContent = data.project_name || 'Arquivos';

            if (!data.files || data.files.length === 0) {
                container.innerHTML = `
                    <div class="text-center" style="padding:1.5rem 0;">
                        <p class="text-muted">Nenhum arquivo STL disponível neste projeto.</p>
                    </div>`;
                return;
            }

            let html = '<div style="display:flex;flex-direction:column;gap:10px;">';
            data.files.forEach(file => {
                html += `
                    <div style="display:flex;align-items:center;justify-content:space-between;
                                padding:12px 14px;border:1px solid #e2e8f0;border-radius:8px;
                                background:#f8fafc;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;background:#dbeafe;border-radius:6px;
                                        display:flex;align-items:center;justify-content:center;
                                        color:#2563eb;flex-shrink:0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2">
                                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                    <path d="M2 17l10 5 10-5"/>
                                    <path d="M2 12l10 5 10-5"/>
                                </svg>
                            </div>
                            <div>
                                <div style="font-size:0.88rem;font-weight:600;color:#1e293b;">
                                    ${escapeHtml(file.original_name)}
                                </div>
                                <div style="font-size:0.76rem;color:#94a3b8;">${file.file_size}</div>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/viewer.php?file=${file.id}"
                           target="_blank"
                           class="btn btn-primary btn-sm">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Visualizar
                        </a>
                    </div>`;
            });
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(() => {
            document.getElementById('modal-files-list').innerHTML =
                '<p class="text-muted text-center">Erro ao carregar arquivos.</p>';
        });
}

function closeModal() {
    document.getElementById('modal-files').classList.remove('open');
    document.getElementById('modal-files-list').innerHTML =
        '<p class="text-muted text-center">Carregando...</p>';
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

// Fecha ao clicar fora do modal
document.getElementById('modal-files').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
