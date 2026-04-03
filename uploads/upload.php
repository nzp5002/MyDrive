<?php

// 🔒 NÃO mostrar erros na tela (evita quebrar header)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 📝 Log de erros
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// ⚙️ Configurações de upload
@ini_set('upload_max_filesize', '100M');
@ini_set('post_max_size', '110M');
@ini_set('memory_limit', '256M');

require_once 'config.php';

// 🔐 sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {

    $user_id = $_SESSION['user_id'];
    $file = $_FILES['file'];

    // 🔑 chave de criptografia
    if (!isset($_SESSION['file_key'])) {
        die("Erro crítico: Chave ausente. Relogue.");
    }

    $chave = $_SESSION['file_key'];

    // 🚫 erro no upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die("Erro no upload.");
    }

    // 📏 limite de tamanho (100MB)
    if ($file['size'] > 100 * 1024 * 1024) {
        die("Arquivo muito grande.");
    }

    // 🔍 detectar MIME real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    // ❌ NÃO usa mais finfo_close()

    // ✅ tipos permitidos
    $permitidos = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'video/mp4',
        'video/webm',
        'video/ogg',
        'application/pdf',
        'audio/mpeg3'
 
    ];

    if (!in_array($mime, $permitidos)) {
        die("Tipo de arquivo não permitido.");
    }

    // 🔒 criptografia
    $method = "aes-256-cbc";
    $iv_length = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($iv_length);

    // 📁 nome seguro
    $safeName = time() . '_' . bin2hex(random_bytes(8)) . ".enc";
    $caminho_final = __DIR__ . "/uploads/" . $safeName;

    // 📂 ler arquivo
    $conteudo_original = file_get_contents($file['tmp_name']);
    if ($conteudo_original === false) {
        die("Erro ao ler arquivo.");
    }

    // 🔐 criptografar
    $conteudo_cifrado = openssl_encrypt(
        $conteudo_original,
        $method,
        $chave,
        OPENSSL_RAW_DATA,
        $iv
    );

    if ($conteudo_cifrado === false) {
        die("Erro na criptografia.");
    }

    // 💾 salvar (IV + dados)
    if (file_put_contents($caminho_final, $iv . $conteudo_cifrado)) {

        // 🧹 remove temp
        unlink($file['tmp_name']);

        // 💾 salvar no banco
        $stmt = $conn->prepare("INSERT INTO files (user_id, nome, tipo, caminho) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $file['name'], $mime, $safeName);
        $stmt->execute();

        // 🔁 redirect seguro
        header("Location: index.php?status=success");
        exit;

    } else {
        die("Erro ao salvar arquivo.");
    }
}
