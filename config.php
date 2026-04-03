<?php
// 🔒 Segurança: não mostrar erros na tela
ini_set('display_errors', 1);
error_reporting(E_ALL);

// mysqli lança exceções
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔐 controle de login
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login");
        exit;
    }
}

// 📁 constantes
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// 🌐 valida DNS antes de conectar
function hostValido($host) {
    if (empty($host)) return false;
    $ip = gethostbyname($host);
    return $ip !== $host; // se não resolveu, retorna o próprio host
}

// ⚠️ página amigável de erro
function erroSistema() {
    http_response_code(500);
    die("
        <h2>⚠️ Sistema indisponível</h2>
        <p>Estamos com instabilidade no momento.</p>
        <p>Tente novamente mais tarde.</p>
    ");
}

// 🔌 conexão principal
function connectDatabases() {

    // =========================
    // 🔹 SERVER A (principal)
    // =========================
    $a_host = getenv("PDBH");
    $a_user = getenv("PDBU");
    $a_pass = getenv("dbpass");
    $a_db   = getenv("banco");
    $a_port = 23664;

    try {

        // 🚫 evita erro DNS
        if (!hostValido($a_host)) {
            throw new Exception("DNS inválido Server A: " . $a_host);
        }

        $mysqli = mysqli_init();

        // ⏱️ timeout
        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

        // 🔐 SSL
        $mysqli->ssl_set(NULL, NULL, __DIR__ . "/ca.pem", NULL, NULL);

        $mysqli->real_connect(
            $a_host,
            $a_user,
            $a_pass,
            $a_db,
            $a_port,
            NULL,
            MYSQLI_CLIENT_SSL
        );

        $mysqli->set_charset("utf8mb4");

        return $mysqli;

    } catch (Exception $e) {

        error_log("[DB] Server A falhou: " . $e->getMessage());

        // =========================
        // 🔹 SERVER B (fallback)
        // =========================
        $b_host = getenv("DB_HOST");
        $b_user = getenv("DB_USER");
        $b_pass = getenv("DB_PASS");
        $b_db   = getenv("DB_BD");

        try {

            if (!hostValido($b_host)) {
                throw new Exception("DNS inválido Server B: " . $b_host);
            }

            $conn = mysqli_init();
            $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

            $conn->real_connect($b_host, $b_user, $b_pass, $b_db);

            $conn->set_charset("utf8mb4");

            return $conn;

        } catch (Exception $e2) {

            error_log("[DB] Server B falhou: " . $e2->getMessage());

            // 🚨 erro final (sem vazar info)
            erroSistema();
        }
    }
}


// No seu config.php (adicione ao final ou antes de fechar a tag PHP)

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


// 🔌 cria conexão
$conn = connectDatabases();
