<?php
// ============================================
// Upload AJAX de arquivo STL
// Endpoint alternativo para uploads assíncronos
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$projectId = (int)($_POST['project_id'] ?? 0);

if (!$projectId) {
    echo json_encode(['success' => false, 'message' => 'ID do projeto inválido.']);
    exit;
}

$project = getProjectById($projectId);
if (!$project) {
    echo json_encode(['success' => false, 'message' => 'Projeto não encontrado.']);
    exit;
}

if (empty($_FILES['stl_file']['name'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
    exit;
}

$result = uploadSTL($projectId, $_FILES['stl_file'], getCurrentUserId());

if ($result['success']) {
    $file = getFileById($result['id']);
    echo json_encode([
        'success'       => true,
        'message'       => 'Arquivo enviado com sucesso!',
        'file_id'       => $result['id'],
        'original_name' => $file['original_name'],
        'file_size'     => formatBytes((int)$file['file_size']),
    ]);
} else {
    echo json_encode($result);
}
