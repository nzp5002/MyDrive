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
    // 🔹 SERVER A (Railway)
    // =========================
    $a_host = getenv("DB_HOST") ?: "mysql.railway.internal";
    $a_user = getenv("DB_USER") ?: "root";
    $a_pass = getenv("DB_PASS") ?: "xItvojBtrwUlQrIxlhLWRLrLUahvAZZL";
    $a_db   = getenv("DB_NAME") ?: "railway";
    $a_port = getenv("DB_PORT") ?: 3306;

    try {
        if (!hostValido($a_host)) {
            throw new Exception("Host inválido Server A: " . $a_host);
        }

        $dsn = "mysql:host=$a_host;port=$a_port;dbname=$a_db;charset=utf8mb4";
        $pdo = new PDO($dsn, $a_user, $a_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return $pdo;

    } catch (Exception $e) {
        error_log("[DB] Server A falhou: " . $e->getMessage());

        // =========================
        // 🔹 SERVER B (fallback)
        // =========================
        $b_host = getenv("DB_FALLBACK_HOST");
        $b_user = getenv("DB_FALLBACK_USER");
        $b_pass = getenv("DB_FALLBACK_PASS");
        $b_db   = getenv("DB_FALLBACK_NAME");
        $b_port = getenv("DB_FALLBACK_PORT") ?: 3306;

        try {
            if (!hostValido($b_host)) {
                throw new Exception("Host inválido Server B: " . $b_host);
            }

            $dsn = "mysql:host=$b_host;port=$b_port;dbname=$b_db;charset=utf8mb4";
            $pdo = new PDO($dsn, $b_user, $b_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            return $pdo;

        } catch (Exception $e2) {
            error_log("[DB] Server B falhou: " . $e2->getMessage());
            erroSistema();
        }
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
