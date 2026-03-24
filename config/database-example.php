<?php
// ============================================
// Configuração da conexão com o banco de dados
// ============================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'stl_viewer');
define('DB_USER', 'root');         // Altere para seu usuário
define('DB_PASS', '');             // Altere para sua senha
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', 'http://localhost/stl-viewer'); // Altere para sua URL
define('UPLOAD_DIR', __DIR__ . '/../uploads/stl/');
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB

function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Erro de conexão com o banco de dados.']));
        }
    }

    return $pdo;
}