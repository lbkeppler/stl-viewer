<?php
// ============================================
// Endpoint seguro para servir arquivos STL
// Valida sessão e permissão antes de servir
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    exit('Não autorizado.');
}

$fileId = (int)($_GET['file'] ?? 0);

if (!$fileId) {
    http_response_code(400);
    exit('ID inválido.');
}

$file = getFileById($fileId);

if (!$file) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

// Verifica permissão do usuário no projeto
if (!hasPermission((int)$file['project_id'], getCurrentUserId())) {
    http_response_code(403);
    exit('Sem permissão.');
}

$filePath = UPLOAD_DIR . $file['stored_name'];

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('Arquivo físico não encontrado.');
}

// ---- Suporte a Range Request (arquivos grandes) ----
$fileSize = filesize($filePath);
$start    = 0;
$end      = $fileSize - 1;

header('Content-Type: model/stl');
header('Content-Disposition: inline; filename="' .
       addslashes($file['original_name']) . '"');
header('Accept-Ranges: bytes');
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

if (isset($_SERVER['HTTP_RANGE'])) {
    preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches);
    $start = (int)$matches[1];
    $end   = isset($matches[2]) && $matches[2] !== ''
             ? (int)$matches[2]
             : $fileSize - 1;

    $length = $end - $start + 1;
    http_response_code(206);
    header("Content-Range: bytes $start-$end/$fileSize");
    header("Content-Length: $length");
} else {
    header("Content-Length: $fileSize");
}

// Serve o arquivo em chunks (evita estouro de memória)
$fp      = fopen($filePath, 'rb');
fseek($fp, $start);
$remain  = $end - $start + 1;
$chunk   = 1024 * 256; // 256KB por chunk

while ($remain > 0 && !feof($fp)) {
    $read = min($chunk, $remain);
    echo fread($fp, $read);
    $remain -= $read;
    flush();
}

fclose($fp);
exit;
