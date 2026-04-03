<?php
require_once 'config.php';

// Pegar o token do link
$token = $_GET['file'] ?? '';

if (empty($token)) { die("Acesso negado."); }

// Descriptografar o Token para pegar o ID do arquivo
$secret_key = getenv("SKEY");
$token_decoded = openssl_decrypt(
    base64_decode(str_replace(['-', '_'], ['+', '/'], $token)),
    "aes-128-cbc",
    $secret_key,
    0,
    substr(hash('sha256', $secret_key), 0, 16)
);

if (!$token_decoded) { die("Link expirado ou inválido."); }

list($file_id, $expires) = explode('|', $token_decoded);

if (time() > $expires) { die("Este link de download expirou."); }

// 🔍 Buscar arquivo no banco
$stmt = $conn->prepare("SELECT * FROM files WHERE id = ?");
$stmt->bind_param("i", $file_id);
$stmt->execute();
$file = $stmt->get_result()->fetch_assoc();

if (!$file) { die("Arquivo não encontrado."); }

$caminho_real = __DIR__ . "/uploads/" . $file['caminho'];

if (!file_exists($caminho_real)) { die("Arquivo físico ausente."); }

// 🔓 Descriptografar o Conteúdo do Arquivo
$method = "aes-256-cbc";
$raw_data = file_get_contents($caminho_real);
$iv_length = openssl_cipher_iv_length($method);
$iv = substr($raw_data, 0, $iv_length);
$encrypted_content = substr($raw_data, $iv_length);

$decrypted = openssl_decrypt($encrypted_content, $method, $secret_key, OPENSSL_RAW_DATA, $iv);

// 📤 Enviar para o Navegador
header('Content-Description: File Transfer');
header('Content-Type: ' . $file['tipo']);
header('Content-Disposition: attachment; filename="' . $file['nome'] . '"');
header('Content-Length: ' . strlen($decrypted));
header('Cache-Control: must-revalidate');
header('Pragma: public');

echo $decrypted;
exit;

