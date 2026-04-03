<?php
require_once 'config.php';

// Iniciar sessão apenas se necessário para checar login do dono
if (session_status() === PHP_SESSION_NONE) session_start();

$token_raw = $_GET['file'] ?? '';
// A chave mestre do servidor para descriptografar o TOKEN (não o arquivo)
$skey = getenv("SKEY") ?: "sua_chave_secreta_padrao_123"; 

if (empty($token_raw)) exit("Token ausente");

// --- 1. DECODIFICAR O TOKEN DO LINK ---
$token = str_replace(['-', '_'], ['+', '/'], $token_raw);
$decrypted_token = openssl_decrypt(
    base64_decode($token), 
    "aes-128-cbc", 
    $skey, 
    0, 
    substr(hash('sha256', $skey), 0, 16)
);

if (!$decrypted_token) exit("Token inválido");

// O token contém: ID_DO_ARQUIVO | TIMESTAMP_EXPIRACAO
list($file_id, $expira) = explode('|', $decrypted_token);

if (time() > $expira) exit("Link de visualização expirado");

// --- 2. BUSCAR DADOS DO ARQUIVO NO BANCO ---
$stmt = $conn->prepare("SELECT caminho, tipo, user_id FROM files WHERE id = ?");
$stmt->bind_param("i", $file_id);
$stmt->execute();
$file = $stmt->get_result()->fetch_assoc();

if (!$file) exit("Arquivo não encontrado");

// --- 3. DEFINIR A CHAVE DE DESCRIPTOGRAFIA DO CONTEÚDO ---
// IMPORTANTE: Para o stream funcionar em links públicos, a chave de criptografia 
// dos arquivos deve ser consistente. Se você usa uma chave por usuário, 
// busque-a aqui. Se usa a SKEY, use a SKEY.
$chave_arquivo = $skey; 

$caminho_real = __DIR__ . "/uploads/" . $file['caminho'];

if (file_exists($caminho_real)) {
    // --- 4. DESCRIPTOGRAFAR O ARQUIVO EM TEMPO REAL ---
    $dados_brutos = file_get_contents($caminho_real);
    $method = "aes-256-cbc";
    $iv_length = openssl_cipher_iv_length($method);
    
    $iv = substr($dados_brutos, 0, $iv_length);
    $conteudo_cifrado = substr($dados_brutos, $iv_length);
    
    $conteudo_original = openssl_decrypt(
        $conteudo_cifrado, 
        $method, 
        $chave_arquivo, 
        OPENSSL_RAW_DATA, 
        $iv
    );

    // --- 5. ENTREGAR O CONTEÚDO ---
    // Limpar qualquer buffer para não corromper a imagem/vídeo
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: ' . $file['tipo']);
    header('Content-Length: ' . strlen($conteudo_original));
    header('Cache-Control: public, max-age=3600'); // Cache de 1 hora para performance
    
    echo $conteudo_original;
    exit;
} else {
    exit("Arquivo físico não encontrado.");
}

