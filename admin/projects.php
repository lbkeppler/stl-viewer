<?php
// ============================================
// Admin — Gerenciar Projetos
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$action  = $_GET['action'] ?? 'list';
$editId  = (int)($_GET['id'] ?? 0);
$message = '';
$error   = '';

// ---- Processar POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // Criar projeto
    if ($postAction === 'create') {
        $name        = trim($_POST['name']        ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$name) {
            $error = 'O nome do projeto é obrigatório.';
        } else {
            $projectId = createProject($name, $description, getCurrentUserId());
            $message   = 'Projeto criado com sucesso!';
            $action    = 'list';
        }
    }

    // Atualizar projeto
    if ($postAction === 'update') {
        $id          = (int)($_POST['id']          ?? 0);
        $name        = trim($_POST['name']         ?? '');
        $description = trim($_POST['description']  ?? '');
        $active      = isset($_POST['active']) ? 1 : 0;

        if (!$name) {
            $error  = 'O nome do projeto é obrigatório.';
            $action = 'edit';
            $editId = $id;
        } else {
            updateProject($id, $name, $description, $active);
            $message = 'Projeto atualizado com sucesso!';
            $action  = 'list';
        }
    }

    // Deletar projeto
    if ($postAction === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        deleteProject($id);
        $message = 'Projeto e arquivos removidos com sucesso.';
        $action  = 'list';
    }

    // Upload de arquivo STL
    if ($postAction === 'upload') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        if (!$projectId) {
            $error = 'Projeto inválido.';
        } elseif (empty($_FILES['stl_file']['name'])) {
            $error = 'Selecione um arquivo STL.';
        } else {
            $result = uploadModel($projectId, $_FILES['stl_file'], getCurrentUserId());
            if ($result['success']) {
                $message = 'Arquivo STL enviado com sucesso!';
            } else {
                $error = $result['message'];
            }
        }
        $action = 'edit';
        $editId = $projectId;
    }

    // Remover arquivo STL
    if ($postAction === 'delete_file') {
        $fileId    = (int)($_POST['file_id']    ?? 0);
        $projectId = (int)($_POST['project_id'] ?? 0);
        deleteSTLFile($fileId);
        $message = 'Arquivo removido.';
        $action  = 'edit';
        $editId  = $projectId;
    }

    // Gerenciar permissões
    if ($postAction === 'grant') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $userId    = (int)($_POST['user_id']    ?? 0);
        if ($projectId && $userId) {
            grantPermission($projectId, $userId);
            $message = 'Acesso concedido.';
        }
        $action = 'edit';
        $editId = $projectId;
    }

    if ($postAction === 'revoke') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $userId    = (int)($_POST['user_id']    ?? 0);
        if ($projectId && $userId) {
            revokePermission($projectId, $userId);
            $message = 'Acesso revogado.';
        }
        $action = 'edit';
        $editId = $projectId;
    }
}

$projects   = getAllProjects();
$editProject = ($action === 'edit' && $editId) ? getProjectById($editId) : null;
$editFiles   = ($editProject) ? getFilesByProject($editId) : [];
$editPerms   = ($editProject) ? getUsersWithPermission($editId) : [];
$allUsers    = getAllUsers();

// Filtra apenas usuários (não admin) para permissões
$regularUsers = array_filter($allUsers, fn($u) => $u['role'] === 'user');

// IDs com permissão já concedida
$permUserIds = array_column($editPerms, 'id');

$pageTitle = 'Projetos';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
                <?= $action === 'edit' ? 'Editar Projeto' : 'Projetos' ?>
            </h1>
            <p>
                <?= $action === 'edit'
                    ? htmlspecialchars($editProject['name'] ?? '')
                    : 'Gerencie projetos, arquivos STL e permissões de acesso' ?>
            </p>
        </div>
        <div class="d-flex gap-1">
            <?php if ($action === 'edit'): ?>
                <a href="<?= BASE_URL ?>/admin/projects.php" class="btn btn-secondary">
                    ← Voltar
                </a>
            <?php else: ?>
                <button class="btn btn-primary" onclick="openModal('modal-create-project')">
                    + Novo Projeto
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">✕ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
    <!-- ==============================
         LISTAGEM DE PROJETOS
         ============================== -->
    <div class="card">
        <div class="card-header">
            <h3>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5">
                    <line x1="8" y1="6" x2="21" y2="6"/>
                    <line x1="8" y1="12" x2="21" y2="12"/>
                    <line x1="8" y1="18" x2="21" y2="18"/>
                    <line x1="3" y1="6" x2="3.01" y2="6"/>
                    <line x1="3" y1="12" x2="3.01" y2="12"/>
                    <line x1="3" y1="18" x2="3.01" y2="18"/>
                </svg>
                Lista de Projetos (<?= count($projects) ?>)
            </h3>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Projeto</th>
                        <th>Arquivos</th>
                        <th>Criado por</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted"
                                style="padding:2rem;">
                                Nenhum projeto cadastrado ainda.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($projects as $p): ?>
                            <tr>
                                <td style="color:#94a3b8;"><?= $p['id'] ?></td>
                                <td>
                                    <div style="font-weight:600;color:#1e293b;">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </div>
                                    <?php if ($p['description']): ?>
                                        <div style="font-size:0.78rem;color:#94a3b8;
                                                    max-width:280px;white-space:nowrap;
                                                    overflow:hidden;text-overflow:ellipsis;">
                                            <?= htmlspecialchars($p['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?= $p['file_count'] ?> STL
                                    </span>
                                </td>
                                <td style="color:#64748b;">
                                    <?= htmlspecialchars($p['creator_name']) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $p['active'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $p['active'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td style="color:#94a3b8;font-size:0.82rem;">
                                    <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <a href="<?= BASE_URL ?>/admin/projects.php?action=edit&id=<?= $p['id'] ?>"
                                           class="btn btn-primary btn-sm">
                                            Gerenciar
                                        </a>
                                        <form method="POST" style="display:inline;"
                                              onsubmit="return confirm('Remover projeto e todos os arquivos?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                Remover
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php elseif ($action === 'edit' && $editProject): ?>
    <!-- ==============================
         EDITAR PROJETO — 3 seções
         ============================== -->

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;align-items:start;">

        <!-- ---- Coluna Esquerda ---- -->
        <div style="display:flex;flex-direction:column;gap:1.2rem;">

            <!-- Dados do Projeto -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Dados do Projeto
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id"
                               value="<?= $editProject['id'] ?>">

                        <div class="form-group">
                            <label>Nome do Projeto *</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= htmlspecialchars($editProject['name']) ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Descrição</label>
                            <textarea name="description" class="form-control"
                                      rows="3"><?= htmlspecialchars($editProject['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label style="display:flex;align-items:center;
                                          gap:8px;cursor:pointer;text-transform:none;
                                          letter-spacing:0;font-size:0.875rem;">
                                <input type="checkbox" name="active" value="1"
                                       style="width:auto;"
                                       <?= $editProject['active'] ? 'checked' : '' ?>>
                                Projeto ativo (visível para usuários)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Salvar Alterações
                        </button>
                    </form>
                </div>
            </div>

            <!-- Upload de Arquivo STL -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        Upload de Arquivo STL
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="upload-form">
                        <input type="hidden" name="action" value="upload">
                        <input type="hidden" name="project_id"
                               value="<?= $editProject['id'] ?>">

                        <div class="upload-zone" id="upload-zone"
                             onclick="document.getElementById('stl-file-input').click()">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="1.5"
                                 style="margin:0 auto 10px;display:block;">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                <path d="M2 17l10 5 10-5"/>
                                <path d="M2 12l10 5 10-5"/>
                            </svg>
                            <p>
                                <strong>Clique para selecionar</strong> ou arraste o arquivo
                            </p>
                            <p style="margin-top:4px;font-size:0.78rem;">
                                Apenas .STL ou .3MF — Máximo 100MB
                            </p>
                            <p id="file-selected-name"
                               style="margin-top:8px;color:#2563eb;font-weight:600;
                                      font-size:0.875rem;display:none;"></p>
                            <input type="file" name="stl_file" id="stl-file-input"
                                   accept=".stl,.3mf" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100 mt-2"
                                id="btn-upload" disabled>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5">
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            </svg>
                            Enviar Arquivo
                        </button>
                    </form>
                </div>
            </div>

        </div><!-- /col esquerda -->

        <!-- ---- Coluna Direita ---- -->
        <div style="display:flex;flex-direction:column;gap:1.2rem;">

            <!-- Arquivos STL do Projeto -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5">
                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                            <polyline points="13 2 13 9 20 9"/>
                        </svg>
                        Arquivos STL
                        <span class="badge badge-info" style="margin-left:4px;">
                            <?= count($editFiles) ?>
                        </span>
                    </h3>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($editFiles)): ?>
                        <p class="text-muted text-center" style="padding:1.5rem;">
                            Nenhum arquivo enviado ainda.
                        </p>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;">
                            <?php foreach ($editFiles as $f): ?>
                                <div style="display:flex;align-items:center;
                                            justify-content:space-between;
                                            padding:11px 14px;
                                            border-bottom:1px solid #f1f5f9;">
                                    <div style="display:flex;align-items:center;gap:9px;
                                                min-width:0;flex:1;">
                                        <div style="width:32px;height:32px;background:#dbeafe;
                                                    border-radius:6px;display:flex;align-items:center;
                                                    justify-content:center;color:#2563eb;flex-shrink:0;">
                                            <svg width="16" height="16" viewBox="0 0 24 24"
                                                 fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                                <path d="M2 17l10 5 10-5"/>
                                                <path d="M2 12l10 5 10-5"/>
                                            </svg>
                                        </div>
                                        <div style="min-width:0;">
                                            <div style="font-size:0.84rem;font-weight:600;
                                                        color:#1e293b;white-space:nowrap;
                                                        overflow:hidden;text-overflow:ellipsis;
                                                        max-width:200px;"
                                                 title="<?= htmlspecialchars($f['original_name']) ?>">
                                                <?= htmlspecialchars($f['original_name']) ?>
                                            </div>
                                            <div style="font-size:0.74rem;color:#94a3b8;">
                                                <?= formatBytes((int)$f['file_size']) ?> •
                                                <?= date('d/m/Y H:i', strtotime($f['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display:flex;gap:5px;flex-shrink:0;">
                                        <a href="<?= BASE_URL ?>/viewer.php?file=<?= $f['id'] ?>"
                                           target="_blank"
                                           class="btn btn-primary btn-sm">
                                            Ver
                                        </a>
                                        <form method="POST" style="display:inline;"
                                              onsubmit="return confirm('Remover este arquivo?')">
                                            <input type="hidden" name="action" value="delete_file">
                                            <input type="hidden" name="file_id" value="<?= $f['id'] ?>">
                                            <input type="hidden" name="project_id"
                                                   value="<?= $editProject['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                ✕
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Permissões de Acesso -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        Permissões de Acesso
                        <span class="badge badge-info" style="margin-left:4px;">
                            <?= count($editPerms) ?>
                        </span>
                    </h3>
                </div>
                <div class="card-body">

                    <?php if (!empty($regularUsers)): ?>
                        <!-- Conceder acesso -->
                        <form method="POST" class="d-flex gap-1 mb-2"
                              style="align-items:flex-end;">
                            <input type="hidden" name="action" value="grant">
                            <input type="hidden" name="project_id"
                                   value="<?= $editProject['id'] ?>">
                            <div class="form-group flex-1" style="margin-bottom:0;">
                                <label>Conceder acesso a</label>
                                <select name="user_id" class="form-control" required>
                                    <option value="">— Selecione —</option>
                                    <?php foreach ($regularUsers as $u): ?>
                                        <?php if (!in_array($u['id'], $permUserIds)): ?>
                                            <option value="<?= $u['id'] ?>">
                                                <?= htmlspecialchars($u['name']) ?>
                                                (<?= htmlspecialchars($u['email']) ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success"
                                    style="margin-bottom:0;">
                                + Conceder
                            </button>
                        </form>
                    <?php endif; ?>

                    <hr class="divider">

                    <!-- Usuários com acesso -->
                    <?php if (empty($editPerms)): ?>
                        <p class="text-muted" style="font-size:0.875rem;text-align:center;
                                                      padding:0.5rem 0;">
                            Nenhum usuário tem acesso a este projeto.
                        </p>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:7px;">
                            <?php foreach ($editPerms as $pu): ?>
                                <div style="display:flex;align-items:center;
                                            justify-content:space-between;
                                            padding:9px 12px;
                                            background:#f8fafc;
                                            border:1px solid #e2e8f0;
                                            border-radius:8px;">
                                    <div style="display:flex;align-items:center;gap:9px;">
                                        <div style="width:30px;height:30px;background:#dbeafe;
                                                    border-radius:50%;display:flex;
                                                    align-items:center;justify-content:center;
                                                    color:#2563eb;font-weight:700;
                                                    font-size:0.8rem;flex-shrink:0;">
                                            <?= strtoupper(substr($pu['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-size:0.84rem;font-weight:600;
                                                        color:#1e293b;">
                                                <?= htmlspecialchars($pu['name']) ?>
                                            </div>
                                            <div style="font-size:0.74rem;color:#94a3b8;">
                                                <?= htmlspecialchars($pu['email']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('Revogar acesso de <?= htmlspecialchars($pu['name']) ?>?')">
                                        <input type="hidden" name="action" value="revoke">
                                        <input type="hidden" name="project_id"
                                               value="<?= $editProject['id'] ?>">
                                        <input type="hidden" name="user_id"
                                               value="<?= $pu['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            Revogar
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /col direita -->

    </div><!-- /grid -->

    <?php endif; // end action edit ?>

</main>

<!-- Modal: Criar Projeto -->
<div class="modal-overlay <?= ($action === 'new') ? 'open' : '' ?>"
     id="modal-create-project">
    <div class="modal">
        <div class="modal-header">
            <h3>+ Novo Projeto</h3>
            <button class="btn-close-modal"
                    onclick="closeModal('modal-create-project')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label>Nome do Projeto *</label>
                    <input type="text" name="name" class="form-control"
                           placeholder="Ex: Estrutura Mecânica v2" required>
                </div>
                <div class="form-group">
                    <label>Descrição</label>
                    <textarea name="description" class="form-control" rows="3"
                              placeholder="Descreva brevemente este projeto..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        onclick="closeModal('modal-create-project')">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Criar Projeto
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ---- Modal helpers ----
function openModal(id)  { document.getElementById(id).classList.add('open');    }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});

// ---- Upload Zone ----
const fileInput  = document.getElementById('stl-file-input');
const uploadZone = document.getElementById('upload-zone');
const btnUpload  = document.getElementById('btn-upload');
const fileLabel  = document.getElementById('file-selected-name');

if (fileInput) {
    fileInput.addEventListener('change', function () {
        if (this.files.length > 0) {
            const name = this.files[0].name;
            const size = (this.files[0].size / 1024 / 1024).toFixed(2);
            fileLabel.textContent = `✓ ${name} (${size} MB)`;
            fileLabel.style.display = 'block';
            btnUpload.disabled = false;
        }
    });
}

// Drag & Drop
if (uploadZone) {
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    });

    uploadZone.addEventListener('dragleave', function() {
        this.classList.remove('drag-over');
    });

    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file && (file.name.toLowerCase().endsWith('.stl') || file.name.toLowerCase().endsWith('.3mf'))) {
            // Transfere o arquivo para o input
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            const size = (file.size / 1024 / 1024).toFixed(2);
            fileLabel.textContent = `✓ ${file.name} (${size} MB)`;
            fileLabel.style.display = 'block';
            btnUpload.disabled = false;
        } else {
            alert('Apenas arquivos .STL e .3MF são aceitos.');
        }
    });
}

// ---- Progress bar no upload ----
const uploadForm = document.getElementById('upload-form');
if (uploadForm) {
    uploadForm.addEventListener('submit', function() {
        if (btnUpload) {
            btnUpload.disabled = true;
            btnUpload.innerHTML = `
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5"
                     style="animation:spin 0.8s linear infinite;">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
                Enviando...`;
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
