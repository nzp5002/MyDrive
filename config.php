<?php
// 🔒 Segurança: mostrar erros (desenvolvimento)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 🛡️ Sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔐 Controle de login
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// 📁 Constantes
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// 🌐 Validar host
function hostValido($host) {

    if (empty($host)) {
        return false;
    }

    // aceita IP
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return true;
    }

    // localhost
    if ($host === 'localhost') {
        return true;
    }

    // domínio
    $ip = gethostbyname($host);

    return $ip !== $host;
}

// ⚠️ Página de erro
function erroSistema($msg = "Erro interno") {

    http_response_code(500);

    die("
        <h2>⚠️ Erro no sistema</h2>
        <pre style='color:red'>" . htmlspecialchars($msg) . "</pre>
    ");
}

// 🔌 Conexão MySQLi
function connectDatabase() {

    $host = getenv("DB_HOST");
    $user = getenv("DB_USER");
    $pass = getenv("DB_PASS");
    $db   = getenv("DB_NAME");
    $port = getenv("DB_PORT") ?: 3306;

    try {

        if (empty($host) || empty($user) || empty($db)) {
            throw new Exception("Variáveis de ambiente não configuradas");
        }

        if (!hostValido($host)) {
            throw new Exception("Host inválido: " . $host);
        }

        $conn = new mysqli($host, $user, $pass, $db, $port);

        if ($conn->connect_error) {
            throw new Exception($conn->connect_error);
        }

        $conn->set_charset("utf8mb4");

        return $conn;

    } catch (Exception $e) {

        error_log("[MYSQL] " . $e->getMessage());

        erroSistema($e->getMessage());
    }
}

// 🔌 conexão global
$conn = connectDatabase();

// 🔐 Encode ID
function encodeId($id) {

    if ($id == 0 || $id === null) {
        return '0';
    }

    return str_replace(
        ['+', '/', '='],
        ['-', '_', ''],
        base64_encode($id . "drive")
    );
}

// 🔓 Decode ID
function decodeId($hash) {

    if ($hash === '0' || empty($hash)) {
        return 0;
    }

    $decoded = base64_decode(
        str_replace(['-', '_'], ['+', '/'], $hash)
    );

    $val = str_replace("drive", "", $decoded);

    return is_numeric($val) ? (int)$val : 0;
}

// 🔍 Query rápida
function query($sql) {

    global $conn;

    $result = $conn->query($sql);

    if (!$result) {
        die("
            <h2>Erro SQL</h2>
            <pre>" . htmlspecialchars($conn->error) . "</pre>
        ");
    }

    return $result;
}
