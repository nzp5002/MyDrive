<?php
require_once 'config.php';

// 1. Pegar o ID da pasta pela URL
$hash = $_GET['id'] ?? '';
$folder_id = decodeId($hash);

if (!$folder_id) {
    die("Link inválido ou expirado.");
}

// 2. Buscar informações da pasta e do dono
$stmt = $conn->prepare("
    SELECT f.*, u.nome as dono_nome 
    FROM folders f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.id = ?
");
$stmt->bind_param("i", $folder_id);
$stmt->execute();
$folder = $stmt->get_result()->fetch_assoc();

if (!$folder) {
    die("Pasta não encontrada.");
}

// 3. Verificação de Privacidade
// Se a pasta não for pública, verifica se o usuário está logado e tem acesso
if ($folder['status'] !== 'public') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?error=privado");
        exit;
    }
    
    // Se for privada e não for o dono, bloqueia
    if ($folder['status'] === 'private' && $_SESSION['user_id'] != $folder['user_id']) {
        die("Esta pasta é privada.");
    }
}

// 4. Buscar arquivos dentro desta pasta
$stmt_files = $conn->prepare("SELECT * FROM files WHERE folder_id = ? ORDER BY nome ASC");
$stmt_files->bind_param("i", $folder_id);
$stmt_files->execute();
$files = $stmt_files->get_result()->fetch_all(MYSQLI_ASSOC);

// Função para gerar token de download (mesma do index2)
function gerarTokenDownload($file_id) {
    $secret_key = getenv("SKEY") ?: "chave_reserva_321";
    $dados = $file_id . "|" . (time() + 3600);
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(openssl_encrypt($dados, "aes-128-cbc", $secret_key, 0, substr(hash('sha256', $secret_key), 0, 16))));
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasta: <?= htmlspecialchars($folder['nome']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-900 font-sans">

    <div class="max-w-4xl mx-auto px-4 py-10">
        <!-- Cabeçalho -->
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center text-3xl">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black uppercase tracking-tight"><?= htmlspecialchars($folder['nome']) ?></h1>
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">Compartilhado por: <?= htmlspecialchars($folder['dono_nome']) ?></p>
                </div>
            </div>
            <span class="bg-emerald-100 text-emerald-600 px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-widest">Link Público</span>
        </div>

        <!-- Lista de Arquivos -->
        <div class="grid grid-cols-1 gap-3">
            <?php if (empty($files)): ?>
                <div class="text-center py-20 bg-white rounded-3xl border border-dashed border-slate-200">
                    <i class="fas fa-box-open text-4xl text-slate-200 mb-4"></i>
                    <p class="text-slate-400 font-bold uppercase text-xs">Esta pasta está vazia</p>
                </div>
            <?php else: ?>
                <?php foreach($files as $file): 
                    $token = gerarTokenDownload($file['id']);
                ?>
                    <div class="bg-white p-4 rounded-2xl border border-slate-100 flex items-center justify-between hover:shadow-md transition">
                        <div class="flex items-center gap-3 min-w-0">
                            <i class="fas fa-file-alt text-blue-400 text-lg"></i>
                            <div class="truncate">
                                <p class="text-sm font-bold text-slate-700 truncate"><?= htmlspecialchars($file['nome']) ?></p>
                                <p class="text-[10px] text-slate-400 uppercase"><?= strtoupper($file['tipo']) ?></p>
                            </div>
                        </div>
                        <a href="download.php?file=<?= $token ?>" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition">
                            Download
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <footer class="mt-10 text-center">
            <p class="text-slate-300 text-[10px] font-bold uppercase tracking-tighter">Gerado por MyDrive Cloud</p>
        </footer>
    </div>

</body>
</html>

