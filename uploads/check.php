<?php
session_start();

// 🔒 sem login = 403
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("negado");
}

if (!isset($_GET['file'])) {
    http_response_code(400);
    exit("arquivo não informado");
}

$user_id = $_SESSION['user_id'];
$file = str_replace("..", "", $_GET['file']);

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



// 🔥 verifica se é discord_file
if (str_starts_with($file, "discord/")) {

$path = __DIR__ . "/uploads/" . $file;

    if (!file_exists($path)) {
        http_response_code(404);
        exit("arquivo não encontrado");
    }

    // qualquer logado pode acessar
    $mime = mime_content_type($path);
    header("Content-Type: $mime");
    header("Content-Length: " . filesize($path));
    readfile($path);
    exit;
}



//perfil
if (str_starts_with($file, "discord/")) {

$path = __DIR__ . "/perfil/" . $file;

    if (!file_exists($path)) {
        http_response_code(404);
        exit("arquivo não encontrado");
    }

    // qualquer logado pode acessar
    $mime = mime_content_type($path);
    header("Content-Type: $mime");
    header("Content-Length: " . filesize($path));
    readfile($path);
    exit;
}


//triagem
if (str_starts_with($file, "triagem/")) {

$path = __DIR__ . "/uploads/" . $file;

    if (!file_exists($path)) {
        http_response_code(404);
        exit("arquivo não encontrado");
    }

    // qualquer logado pode acessar
    $mime = mime_content_type($path);
    header("Content-Type: $mime");
    header("Content-Length: " . filesize($path));
    readfile($path);
    exit;
}


// 🔐 arquivos normais (validação forte)

// busca no banco
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

// 🔥 verifica se é dono
if ($owner_id != $user_id) {

    // 🔁 verifica compartilhamento
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

// 📂 caminho real
$path = __DIR__ . "/uploads/" . $file;

if (!file_exists($path)) {
    http_response_code(404);
    exit("arquivo não encontrado");
}

// 📤 entrega arquivo
$mime = mime_content_type($path);
header("Content-Type: $mime");
header("Content-Length: " . filesize($path));
readfile($path);
