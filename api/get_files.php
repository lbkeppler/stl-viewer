<?php
// ============================================
// API: Retorna arquivos STL de um projeto
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado.']);
    exit;
}

$projectId = (int)($_GET['project_id'] ?? 0);

if (!$projectId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido.']);
    exit;
}

// Verifica permissão de acesso
if (!hasPermission($projectId, getCurrentUserId())) {
    http_response_code(403);
    echo json_encode(['error' => 'Sem permissão.']);
    exit;
}

$project = getProjectById($projectId);
if (!$project || !$project['active']) {
    http_response_code(404);
    echo json_encode(['error' => 'Projeto não encontrado.']);
    exit;
}

$rawFiles = getFilesByProject($projectId);
$files = array_map(function($f) {
    return [
        'id'            => $f['id'],
        'original_name' => $f['original_name'],
        'file_size'     => formatBytes((int)$f['file_size']),
        'created_at'    => $f['created_at'],
    ];
}, $rawFiles);

echo json_encode([
    'project_name' => $project['name'],
    'files'        => $files,
]);
