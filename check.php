<?php
session_start();

// 🔒 sem login = 403
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("negado");
}

// 📥 valida parâmetro
if (!isset($_GET['file'])) {
    http_response_code(400);
    exit("arquivo não informado");
}

$user_id = $_SESSION['user_id'];
$file = ltrim($_GET['file'], "/");

// 🔐 valida caracteres
if (!preg_match('#^[a-zA-Z0-9_\-\/\.]+$#', $file)) {
    http_response_code(400);
    exit("caminho inválido");
}

// 🔒 função segura
function servirArquivo($baseDir, $file) {
    $base = realpath($baseDir);
    $full = $baseDir . "/" . $file;
    $path = realpath($full);

    // 🔍 DEBUG (ative se precisar)
    // var_dump($baseDir, $file, $full, $path); exit;

    // 🔥 proteção real
    if ($path === false || strpos($path, $base) !== 0) {
        http_response_code(403);
        exit("acesso inválido");
    }

    if (!file_exists($path)) {
        http_response_code(404);
        exit("arquivo não encontrado");
    }

    $mime = mime_content_type($path);
    header("Content-Type: $mime");
    header("Content-Length: " . filesize($path));
    readfile($path);
    exit;
}

// =====================================================
// 🔥 ROTAS LIBERADAS (SEM BANCO)
// =====================================================

// 📁 tudo que começa com uploads/
if (str_starts_with($file, "uploads/")) {
    servirArquivo(__DIR__, $file);
}

// 📁 fallback para perfil direto
if (str_starts_with($file, "perfil/")) {
    servirArquivo(__DIR__ . "/perfil", str_replace("perfil/", "", $file));
}

// 📁 discord
if (str_starts_with($file, "discord/")) {
    servirArquivo(__DIR__ . "/uploads", $file);
}

// 📁 triagem
if (str_starts_with($file, "triagem/")) {
    servirArquivo(__DIR__ . "/uploads", $file);
}


// =====================================================
// 🔐 ARQUIVOS PROTEGIDOS (COM BANCO)
// =====================================================

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, "ca.pem", NULL, NULL);

mysqli_real_connect(
    $conn,
    "mysql-9da68d9-antonio-aa41.i.aivencloud.com",
    "avnadmin",
    "AVNS_6_FPLDruJ9UGnTDmDRr",
    "gdrive",
    23664
);

// 🔎 busca arquivo
$stmt = $conn->prepare("
    SELECT f.id, f.user_id, f.caminho
    FROM files f
    WHERE f.caminho = ?
");
$stmt->bind_param("s", $file);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit("arquivo não encontrado");
}

$row = $result->fetch_assoc();
$file_id = $row['id'];
$owner_id = $row['user_id'];

// 🔐 valida acesso
if ($owner_id != $user_id) {

    $stmt2 = $conn->prepare("
        SELECT id FROM compartilhamentos
        WHERE file_id = ? AND para_user_id = ?
    ");
    $stmt2->bind_param("ii", $file_id, $user_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    if ($res2->num_rows === 0) {
        http_response_code(403);
        exit("negado");
    }
}

// 📂 entrega segura
servirArquivo(__DIR__ . "/uploads", $file);
