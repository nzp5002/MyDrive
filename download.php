<?php

require_once '../db/config.php';

// =========================
// GERAR CHAVE HARDWARE
// =========================
function gerarChaveHardware() {

    $dados = '';

    $dados .= php_uname();

    if (file_exists('/proc/cpuinfo')) {
        $dados .= file_get_contents('/proc/cpuinfo');
    }

    if (file_exists('/etc/machine-id')) {
        $dados .= file_get_contents('/etc/machine-id');
    }

    // CORREÇÃO: Garante que se o shell_exec retornar null ou falso, vire uma string vazia antes do trim
    $exec_android = shell_exec('settings get secure android_id 2>/dev/null');
    $android_id = trim($exec_android ?? '');

    $dados .= $android_id;

    $dados .= __DIR__;

    return hash('sha256', $dados);
}

// =========================
// CHAVE FINAL
// =========================
$secret_key = getenv("SKEY");

if (!$secret_key || trim($secret_key) === '') {
    $secret_key = gerarChaveHardware();
}

// =========================
// TOKEN
// =========================
$token = $_GET['file'] ?? '';

if (empty($token)) {
    die("Acesso negado.");
}

// Corrigir base64
$token = str_replace(['-', '_'], ['+', '/'], $token);

// padding
$padding = strlen($token) % 4;

if ($padding > 0) {
    $token .= str_repeat('=', 4 - $padding);
}

// =========================
// DESCRIPTOGRAFAR TOKEN
// =========================
$base64_decoded = base64_decode($token);

$token_decoded = openssl_decrypt(
    $base64_decoded,
    "aes-128-cbc",
    $secret_key,
    0,
    substr(hash('sha256', $secret_key), 0, 16)
);

if (!$token_decoded) {
    // Alerta de debug caso a chave gerada no index.php divirja desta por escopo do Termux
    die("Token inválido. Verifique se a SKEY está idêntica no index.php");
}

// Garante que o explode não falhará se a string vier malformada
if (strpos($token_decoded, '|') === false) {
    die("Token corrompido ou chave secreta incompatível.");
}

list($file_id, $expires) = explode('|', $token_decoded);

if (time() > $expires) {
    die("Link expirou.");
}

// =========================
// BUSCAR ARQUIVO
// =========================
$stmt = $conn->prepare("SELECT * FROM files WHERE id = ?");

$stmt->bind_param("i", $file_id);

$stmt->execute();

$file = $stmt->get_result()->fetch_assoc();

if (!$file) {
    die("Arquivo não encontrado no banco de dados.");
}

// =========================
// CAMINHO REAL
// =========================
// NOTA: Ajustado de $file['arquivo'] para $file['caminho'] baseado na estrutura padrão do MyDrive
$nome_arquivo_fisico = $file['caminho'] ?? $file['arquivo'] ?? '';
$caminho_real = __DIR__ . "/uploads/" . $nome_arquivo_fisico;

if (!file_exists($caminho_real)) {
    die("Arquivo físico ausente no servidor.");
}

// =========================
// LER ARQUIVO
// =========================
$raw_data = file_get_contents($caminho_real);

if ($raw_data === false) {
    die("Erro ao ler arquivo.");
}

// =========================
// DESCRIPTOGRAFAR CONTEÚDO
// =========================
$method = "aes-256-cbc";

$iv_length = openssl_cipher_iv_length($method);

$iv = substr($raw_data, 0, $iv_length);

$encrypted_content = substr($raw_data, $iv_length);

$decrypted = openssl_decrypt(
    $encrypted_content,
    $method,
    $secret_key,
    OPENSSL_RAW_DATA,
    $iv
);

// Se o seu upload.php NÃO criptografa o arquivo físico ao salvar, comente a descriptografia acima
// e use a linha abaixo:
// $decrypted = $raw_data;

if ($decrypted === false) {
    die("Erro ao descriptografar o conteúdo do arquivo.");
}

// =========================
// DOWNLOAD
// =========================
// Limpa qualquer buffer de saída residual para evitar corromper o arquivo binário
if (ob_get_length()) ob_end_clean();

header('Content-Description: File Transfer');
header('Content-Type: ' . $file['tipo']);
header('Content-Disposition: attachment; filename="' . $file['nome'] . '"');
header('Content-Length: ' . strlen($decrypted));
header('Cache-Control: must-revalidate');
header('Pragma: public');

echo $decrypted;
exit;
?>

