<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

checkLogin();

// =========================
// GERAR CHAVE PELO HARDWARE
// =========================
function gerarChaveHardware() {

    $dados = '';

    // Sistema
    $dados .= php_uname();

    // CPU INFO
    if (file_exists('/proc/cpuinfo')) {
        $dados .= file_get_contents('/proc/cpuinfo');
    }

    // Machine ID Linux
    if (file_exists('/etc/machine-id')) {
        $dados .= file_get_contents('/etc/machine-id');
    }

    // Android ID (Termux)
    $android_id = trim(shell_exec('settings get secure android_id 2>/dev/null'));
    $dados .= $android_id;

    // Caminho interno do PHP
    $dados .= __DIR__;

    // Hash AES-256
    return hash('sha256', $dados);
}

// =========================
// CHAVE FINAL
// =========================
$secret_key = getenv("SKEY");

if (!$secret_key || trim($secret_key) === '') {

    // fallback automático
    $secret_key = gerarChaveHardware();
}

// =========================
// PASTAS
// =========================
$chunkDir = __DIR__ . '/chunks';
$uploadDir = __DIR__ . '/uploads';

if (!is_dir($chunkDir)) {
    mkdir($chunkDir, 0777, true);
}

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// =========================
// DADOS RESUMABLE
// =========================
$identifier  = $_POST['resumableIdentifier'] ?? null;
$chunkNumber = $_POST['resumableChunkNumber'] ?? null;
$totalChunks = $_POST['resumableTotalChunks'] ?? null;
$filename    = $_POST['resumableFilename'] ?? null;

$folder_id = $_POST['folder_id'] ?? ($_GET['folder_id'] ?? 0);
$folder_id = (int)$folder_id;

// =========================
// CHECK CHUNK
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $chunkFile = $chunkDir . "/{$identifier}_{$chunkNumber}";

    if (file_exists($chunkFile)) {
        http_response_code(200);
    } else {
        http_response_code(204);
    }

    exit;
}

// =========================
// RECEBER CHUNK
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {

    if (!$identifier || !$chunkNumber) {

        http_response_code(400);
        exit("Chunk inválido");
    }

    $chunkFile = $chunkDir . "/{$identifier}_{$chunkNumber}";

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $chunkFile)) {

        http_response_code(500);
        exit("Erro ao salvar chunk");
    }

    // =========================
    // VERIFICAR TODOS CHUNKS
    // =========================
    $allChunks = true;

    for ($i = 1; $i <= $totalChunks; $i++) {

        if (!file_exists($chunkDir . "/{$identifier}_{$i}")) {

            $allChunks = false;
            break;
        }
    }

    // =========================
    // MONTAR ARQUIVO FINAL
    // =========================
    if ($allChunks) {

        $user_id = $_SESSION['user_id'];

        $tempFile = $chunkDir . "/{$identifier}_final";

        $out = fopen($tempFile, "wb");

        if (!$out) {
            exit("Erro ao criar arquivo temporário");
        }

        for ($i = 1; $i <= $totalChunks; $i++) {

            $chunkPath = $chunkDir . "/{$identifier}_{$i}";

            $chunk = file_get_contents($chunkPath);

            fwrite($out, $chunk);

            unlink($chunkPath);
        }

        fclose($out);

        // =========================
        // MIME TYPE
        // =========================
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        $mime = finfo_file($finfo, $tempFile);

        finfo_close($finfo);

        // =========================
        // CRIPTOGRAFIA AES-256-CBC
        // =========================
        $method = "aes-256-cbc";

        $iv_length = openssl_cipher_iv_length($method);

        $iv = openssl_random_pseudo_bytes($iv_length);

        $conteudo = file_get_contents($tempFile);

        $conteudo_cifrado = openssl_encrypt(
            $conteudo,
            $method,
            $secret_key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($conteudo_cifrado === false) {

            unlink($tempFile);

            exit("Erro na criptografia");
        }

        // =========================
        // NOME SEGURO
        // =========================
        $safeName = time() . '_' . bin2hex(random_bytes(8)) . ".enc";

        $caminho_final = $uploadDir . "/" . $safeName;

        // =========================
        // SALVAR ARQUIVO
        // =========================
        $salvou = file_put_contents(
            $caminho_final,
            $iv . $conteudo_cifrado
        );

        if (!$salvou) {

            unlink($tempFile);

            exit("Erro ao salvar arquivo final");
        }

        unlink($tempFile);

        // =========================
        // TAMANHO
        // =========================
        $tamanho = filesize($caminho_final);

        // =========================
        // SALVAR NO MYSQL
        // =========================
        $stmt = $conn->prepare("
            INSERT INTO files
            (
                user_id,
                folder_id,
                nome,
                arquivo,
                tipo,
                tamanho
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {

            exit("Erro prepare: " . $conn->error);
        }

        $stmt->bind_param(
            "iisssi",
            $user_id,
            $folder_id,
            $filename,
            $safeName,
            $mime,
            $tamanho
        );

        if (!$stmt->execute()) {

            exit("Erro execute: " . $stmt->error);
        }

        $stmt->close();

        echo "UPLOAD_OK";

        exit;
    }

    echo "CHUNK_OK";
}
?>
