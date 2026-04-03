<?php                                                
// 🔒 Configurações de erro e limites
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }                                                    
checkLogin();

$chunkDir = __DIR__ . '/chunks';
if (!is_dir($chunkDir)) { mkdir($chunkDir, 0777, true); }

// 📥 Dados do Resumable e da Pasta
$identifier = $_POST['resumableIdentifier'] ?? null;
$chunkNumber = $_POST['resumableChunkNumber'] ?? null;                                                    
$totalChunks = $_POST['resumableTotalChunks'] ?? null;                                                    
$filename = $_POST['resumableFilename'] ?? null;

// Captura o ID da pasta (veio do 'query' no JS do index2.php)
$folder_id = $_POST['folder_id'] ?? ($_GET['folder_id'] ?? 0);
$folder_id = (int)$folder_id; 

// 🔍 CHECK chunk
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $chunkFile = $chunkDir . "/{$identifier}_{$chunkNumber}";
    if (file_exists($chunkFile)) { http_response_code(200); } 
    else { http_response_code(204); }                                                    
    exit;
}
                                                     
// 📤 RECEBER CHUNK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if (!$identifier || !$chunkNumber) { http_response_code(400); exit; }                                                
    
    $chunkFile = $chunkDir . "/{$identifier}_{$chunkNumber}";                                                                                                      
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $chunkFile)) {
        http_response_code(500); exit;
    }
                                                         
    // 🔄 Verifica se todos os chunks chegaram
    $allChunks = true;                                   
    for ($i = 1; $i <= $totalChunks; $i++) {
        if (!file_exists($chunkDir . "/{$identifier}_{$i}")) { $allChunks = false; break; }
    }                                                

    if ($allChunks) {
        $user_id = $_SESSION['user_id'];
        $chave = getenv("SKEY"); // Use a mesma chave do index2
              
        $tempFile = $chunkDir . "/{$identifier}_final";                                                           
        $out = fopen($tempFile, "wb");
        for ($i = 1; $i <= $totalChunks; $i++) {
            $chunk = file_get_contents($chunkDir . "/{$identifier}_{$i}");                                            
            fwrite($out, $chunk);
            unlink($chunkDir . "/{$identifier}_{$i}");                                                            
        }
        fclose($out);

        // MIME Check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);             
        $mime = finfo_file($finfo, $tempFile);       
        
        // 🔒 Criptografia AES-256-CBC
        $method = "aes-256-cbc";
        $iv_length = openssl_cipher_iv_length($method);                                                           
        $iv = openssl_random_pseudo_bytes($iv_length);
        $conteudo = file_get_contents($tempFile);
        $conteudo_cifrado = openssl_encrypt($conteudo, $method, $chave, OPENSSL_RAW_DATA, $iv);
                                                             
        $safeName = time() . '_' . bin2hex(random_bytes(8)) . ".enc";                                             
        $caminho_final = __DIR__ . "/uploads/" . $safeName;

        if (file_put_contents($caminho_final, $iv . $conteudo_cifrado)) {
            unlink($tempFile);

            // 💾 BANCO: Incluindo o folder_id para o arquivo aparecer na pasta certa
            $stmt = $conn->prepare("INSERT INTO files (user_id, folder_id, nome, tipo, caminho) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $user_id, $folder_id, $filename, $mime, $safeName);
            $stmt->execute();                        
            
            echo "UPLOAD_OK"; exit;
        }
    }
    echo "chunk recebido";
}

