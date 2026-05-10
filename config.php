<?php
// 🔒 Segurança: não mostrar erros na tela
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 🛡️ Sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔐 Controle de login
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login");
        exit;
    }
}

// 📁 Constantes de upload
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// 🌐 Valida host antes de conectar
function hostValido($host) {
    if (empty($host)) return false;
    $ip = gethostbyname($host);
    return $ip !== $host;
}

// ⚠️ Página amigável de erro
function erroSistema() {
    http_response_code(500);
    die("
        <h2>⚠️ Sistema indisponível</h2>
        <p>Estamos com instabilidade no momento.</p>
        <p>Tente novamente mais tarde.</p>
    ");
}

// 🔌 Conexão principal com PDO
function connectDatabases() {

    // =========================
    // 🔹 SERVIDOR (Variáveis de Ambiente)
    // =========================
    $host = getenv("DB_HOST");
    $user = getenv("DB_USER");
    $pass = getenv("DB_PASS");
    $db   = getenv("DB_NAME");
    $port = getenv("DB_PORT") ?: 3306;

    try {
        if (empty($host) || empty($user) || empty($pass) || empty($db)) {
            throw new Exception("Variáveis de ambiente não configuradas");
        }

        if (!hostValido($host)) {
            throw new Exception("Host inválido: " . $host);
        }

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return $pdo;

    } catch (Exception $e) {
        error_log("[DB] Erro na conexão: " . $e->getMessage());
        erroSistema();
    }
}

// 🔌 Cria conexão global
$pdo = connectDatabases();

// Funções encode/decode
function encodeId($id) {
    if ($id == 0 || $id === null) return '0';
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($id . "drive"));
}

function decodeId($hash) {
    if ($hash === '0' || empty($hash)) return 0;
    $decoded = base64_decode(str_replace(['-', '_'], ['+', '/'], $hash));
    $val = str_replace("drive", "", $decoded);
    return is_numeric($val) ? (int)$val : 0;
}

// Exemplo de função de query rápida
function query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
