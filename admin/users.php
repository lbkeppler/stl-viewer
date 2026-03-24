<?php
// ============================================
// Admin — Gerenciar Usuários
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

    // Criar usuário
    if ($postAction === 'create') {
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = $_POST['role'] === 'admin' ? 'admin' : 'user';

        if (!$name || !$email || !$password) {
            $error = 'Preencha todos os campos obrigatórios.';
        } elseif (strlen($password) < 6) {
            $error = 'A senha deve ter no mínimo 6 caracteres.';
        } else {
            $result = createUser($name, $email, $password, $role);
            if ($result['success']) {
                $message = 'Usuário criado com sucesso!';
                $action  = 'list';
            } else {
                $error = $result['message'];
            }
        }
    }

    // Atualizar usuário
    if ($postAction === 'update') {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name']  ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '') ?: null;
        $active   = isset($_POST['active']) ? 1 : 0;

        if (!$name || !$email) {
            $error  = 'Nome e e-mail são obrigatórios.';
            $action = 'edit';
            $editId = $id;
        } else {
            $result = updateUser($id, $name, $email, $password, $active);
            if ($result['success']) {
                $message = 'Usuário atualizado com sucesso!';
                $action  = 'list';
            } else {
                $error  = $result['message'];
                $action = 'edit';
                $editId = $id;
            }
        }
    }

    // Deletar usuário
    if ($postAction === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        deleteUser($id);
        $message = 'Usuário removido.';
        $action  = 'list';
    }
}

$users   = getAllUsers();
$editUser = ($action === 'edit' && $editId) ? getUserById($editId) : null;
$pageTitle = 'Usuários';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Usuários
            </h1>
            <p>Gerencie os usuários do sistema</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('modal-create-user')">
            + Novo Usuário
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">✕ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Tabela de Usuários -->
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
                Lista de Usuários (<?= count($users) ?>)
            </h3>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Status</th>
                        <th>Cadastro</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td style="color:#94a3b8;"><?= $u['id'] ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:9px;">
                                    <div style="width:30px;height:30px;background:#dbeafe;
                                                border-radius:50%;display:flex;align-items:center;
                                                justify-content:center;color:#2563eb;
                                                font-weight:700;font-size:0.8rem;flex-shrink:0;">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <?= htmlspecialchars($u['name']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'badge-warning' : 'badge-info' ?>">
                                    <?= $u['role'] === 'admin' ? 'Admin' : 'Usuário' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $u['active'] ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $u['active'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td style="color:#94a3b8;font-size:0.82rem;">
                                <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <button class="btn btn-warning btn-sm"
                                            onclick='editUser(<?= json_encode($u) ?>)'>
                                        Editar
                                    </button>
                                    <?php if ($u['role'] !== 'admin'): ?>
                                        <form method="POST" style="display:inline;"
                                              onsubmit="return confirm('Remover este usuário?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                Remover
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- Modal: Criar Usuário -->
<div class="modal-overlay <?= ($action === 'new') ? 'open' : '' ?>" id="modal-create-user">
    <div class="modal">
        <div class="modal-header">
            <h3>+ Novo Usuário</h3>
            <button class="btn-close-modal" onclick="closeModal('modal-create-user')">
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
                    <label>Nome completo *</label>
                    <input type="text" name="name" class="form-control"
                           placeholder="João da Silva" required>
                </div>
                <div class="form-group">
                    <label>E-mail *</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="joao@email.com" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Senha *</label>
                        <input type="password" name="password" class="form-control"
                               placeholder="Mín. 6 caracteres" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Perfil</label>
                        <select name="role" class="form-control">
                            <option value="user">Usuário</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        onclick="closeModal('modal-create-user')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Criar Usuário</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Usuário -->
<div class="modal-overlay" id="modal-edit-user">
    <div class="modal">
        <div class="modal-header">
            <h3>Editar Usuário</h3>
            <button class="btn-close-modal" onclick="closeModal('modal-edit-user')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-user-id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Nome completo *</label>
                    <input type="text" name="name" id="edit-user-name"
                           class="form-control" required>
                </div>
                <div class="form-group">
                    <label>E-mail *</label>
                    <input type="email" name="email" id="edit-user-email"
                           class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nova senha</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Deixe em branco para não alterar">
                    <span class="form-hint">Mínimo 6 caracteres se preencher</span>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="active" id="edit-user-active"
                               value="1" style="width:auto;">
                        Usuário ativo
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        onclick="closeModal('modal-edit-user')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('open');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

function editUser(user) {
    document.getElementById('edit-user-id').value    = user.id;
    document.getElementById('edit-user-name').value  = user.name;
    document.getElementById('edit-user-email').value = user.email;
    document.getElementById('edit-user-active').checked = user.active == 1;
    openModal('modal-edit-user');
}

// Fecha modal ao clicar no overlay
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
