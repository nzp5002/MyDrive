<?php
require_once 'config.php';

// O config.php já deve ter o ob_start() e o session_start()
// Se não tiver certeza, podemos usar o buffer aqui também:
if (ob_get_level() == 0) ob_start();

$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id > 0) {
    // 1. Apaga as chaves temporárias do banco antes de destruir a sessão
    $stmt = $conn->prepare("DELETE FROM chaves_compartilhadas WHERE de_user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// 2. Limpa os dados da sessão
$_SESSION = array();

// 3. Destrói o cookie de sessão no navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finaliza a sessão no servidor
session_destroy();

// 5. Redireciona de forma limpa
header("Location: login.php");
exit;

