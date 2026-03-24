<?php
// ============================================
// Funções utilitárias gerais do sistema
// ============================================

require_once __DIR__ . '/../config/database.php';

// ---------- USUÁRIOS ----------

function createUser(string $name, string $email, string $password, string $role = 'user'): array {
    $db = getDB();

    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        return ['success' => false, 'message' => 'E-mail já cadastrado.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $hash, $role]);

    return ['success' => true, 'id' => $db->lastInsertId()];
}

function updateUser(int $id, string $name, string $email, ?string $password, int $active): array {
    $db = getDB();

    $check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->execute([$email, $id]);
    if ($check->fetch()) {
        return ['success' => false, 'message' => 'E-mail já em uso por outro usuário.'];
    }

    if ($password) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare("UPDATE users SET name=?, email=?, password=?, active=? WHERE id=?");
        $stmt->execute([$name, $email, $hash, $active, $id]);
    } else {
        $stmt = $db->prepare("UPDATE users SET name=?, email=?, active=? WHERE id=?");
        $stmt->execute([$name, $email, $active, $id]);
    }

    return ['success' => true];
}

function getAllUsers(): array {
    $db = getDB();
    $stmt = $db->query("SELECT id, name, email, role, active, created_at FROM users ORDER BY name");
    return $stmt->fetchAll();
}

function getUserById(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, role, active FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function deleteUser(int $id): void {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$id]);
}

// ---------- PROJETOS ----------

function createProject(string $name, string $description, int $createdBy): int {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO projects (name, description, created_by) VALUES (?, ?, ?)");
    $stmt->execute([$name, $description, $createdBy]);
    return (int)$db->lastInsertId();
}

function updateProject(int $id, string $name, string $description, int $active): void {
    $db = getDB();
    $stmt = $db->prepare("UPDATE projects SET name=?, description=?, active=? WHERE id=?");
    $stmt->execute([$name, $description, $active, $id]);
}

function getAllProjects(): array {
    $db = getDB();
    $stmt = $db->query("
        SELECT p.*, u.name AS creator_name,
               COUNT(DISTINCT sf.id) AS file_count
        FROM projects p
        JOIN users u ON u.id = p.created_by
        LEFT JOIN model_files sf ON sf.project_id = p.id
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    return $stmt->fetchAll();
}

function getProjectById(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function deleteProject(int $id): void {
    $db  = getDB();
    // Remove arquivos físicos antes de deletar do banco
    $stmt = $db->prepare("SELECT stored_name FROM model_files WHERE project_id = ?");
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $file) {
        $path = UPLOAD_DIR . $file['stored_name'];
        if (file_exists($path)) unlink($path);
    }
    $db->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
}

// ---------- ARQUIVOS STL ----------

function uploadModel(int $projectId, array $file, int $userId): array {
    $db = getDB();

    $allowedExtensions = ['stl', '3mf'];
    $originalName = $file['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions)) {
        return ['success' => false, 'message' => 'Formato não suportado. Use STL ou 3MF.'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Arquivo muito grande. Máximo: 100MB.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erro no upload.'];
    }

    // Validação de assinatura ZIP para 3MF
    if ($ext === '3mf') {
        $handle    = fopen($file['tmp_name'], 'rb');
        $signature = fread($handle, 2);
        fclose($handle);
        if ($signature !== 'PK') {
            return ['success' => false, 'message' => 'Arquivo 3MF inválido (não é um ZIP válido).'];
        }
    }

    $storedName  = uniqid('model_', true) . '.' . $ext;
    $destination = UPLOAD_DIR . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'message' => 'Falha ao salvar o arquivo.'];
    }

    $stmt = $db->prepare("
        INSERT INTO model_files (project_id, original_name, stored_name, file_type, file_size, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$projectId, $originalName, $storedName, $ext, $file['size'], $userId]);

    return ['success' => true, 'id' => $db->lastInsertId()];
}



function getFilesByProject(int $projectId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM model_files WHERE project_id = ? ORDER BY created_at DESC");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

function getFileById(int $fileId): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM model_files WHERE id = ?");
    $stmt->execute([$fileId]);
    return $stmt->fetch() ?: null;
}

function deleteSTLFile(int $fileId): void {
    $db = getDB();
    $stmt = $db->prepare("SELECT stored_name FROM model_files WHERE id = ?");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();
    if ($file) {
        $path = UPLOAD_DIR . $file['stored_name'];
        if (file_exists($path)) unlink($path);
        $db->prepare("DELETE FROM model_files WHERE id = ?")->execute([$fileId]);
    }
}

// ---------- PERMISSÕES ----------

function grantPermission(int $projectId, int $userId): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT IGNORE INTO project_permissions (project_id, user_id) VALUES (?, ?)");
    $stmt->execute([$projectId, $userId]);
}

function revokePermission(int $projectId, int $userId): void {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM project_permissions WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$projectId, $userId]);
}

function hasPermission(int $projectId, int $userId): bool {
    if (isAdmin()) return true;
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM project_permissions WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$projectId, $userId]);
    return (bool)$stmt->fetch();
}

function getProjectsForUser(int $userId): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.*, COUNT(sf.id) AS file_count
        FROM projects p
        JOIN project_permissions pp ON pp.project_id = p.id
        LEFT JOIN model_files sf ON sf.project_id = p.id
        WHERE pp.user_id = ? AND p.active = 1
        GROUP BY p.id
        ORDER BY p.name
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getUsersWithPermission(int $projectId): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.email
        FROM users u
        JOIN project_permissions pp ON pp.user_id = u.id
        WHERE pp.project_id = ?
    ");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

function formatBytes(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}
