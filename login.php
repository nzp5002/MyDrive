<?php
require_once 'config.php';

$erro = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password_bruta = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, nome, password, status FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password_bruta, $user['password'])) {
            
            if ($user['status'] !== 'ativo') {
                $erro = "A tua conta ainda aguarda aprovação.";
            } else {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nome'] = $user['nome'];

                // --- GERAÇÃO DA CHAVE DE SESSÃO ---
                // Nota: Mantive o salt exatamente como o seu original
                $salt_fixo = "pneumoultramicroscopicossilicovulcanoconiose"; 
                $file_key = hash_pbkdf2("sha256", $password_bruta, $salt_fixo, 100000, 32);
                $_SESSION['file_key'] = $file_key;

                // --- LÓGICA DE AUTO-PONTE (ATIVAR COMPARTILHAMENTOS) ---
                // Preparamos a chave cifrada para a tabela de trânsito
                $iv_sessao = substr(hash('sha256', $salt_fixo), 0, 16);
                $chave_cifrada = base64_encode(openssl_encrypt($file_key, "aes-256-cbc", $salt_fixo, 0, $iv_sessao));

                // 1. Procurar todos os ficheiros que este utilizador partilhou
                $stmt_shares = $conn->prepare("SELECT DISTINCT file_id FROM compartilhamentos WHERE de_user_id = ?");
                $stmt_shares->bind_param("i", $user['id']);
                $stmt_shares->execute();
                $res_shares = $stmt_shares->get_result();

                // 2. Criar/Atualizar a ponte de chaves para cada ficheiro partilhado
                while ($share = $res_shares->fetch_assoc()) {
                    $f_id = $share['file_id'];
                    $stmt_k = $conn->prepare("INSERT INTO chaves_compartilhadas (file_id, de_user_id, chave_temporaria) 
                                              VALUES (?, ?, ?) 
                                              ON DUPLICATE KEY UPDATE chave_temporaria = VALUES(chave_temporaria)");
                    $stmt_k->bind_param("iis", $f_id, $user['id'], $chave_cifrada);
                    $stmt_k->execute();
                }
                // -------------------------------------------------------

                header("Location: index.php");
                exit;
            }
        } else {
            $erro = "Senha incorreta.";
        }
    } else {
        $erro = "Utilizador não encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Login - MyDrive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-slate-900 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-2xl p-8">
        <div class="text-center mb-8">
            <i class="fas fa-cloud-lock text-blue-600 text-5xl mb-4"></i>
            <h1 class="text-2xl font-bold text-slate-800">MyDrive Login</h1>
            <p class="text-slate-500 text-sm mt-2">Criptografia Ativa & Sessão Segura</p>
        </div>

        <?php if(!empty($erro)): ?>
            <div class="bg-red-50 text-red-600 p-4 rounded-xl text-xs font-bold mb-6 border border-red-100 flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i> <?= $erro ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div class="relative">
                <i class="fas fa-at absolute left-4 top-4 text-slate-400"></i>
                <input type="text" name="username" placeholder="usuário" required 
                    class="w-full p-4 pl-12 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none transition-all">
            </div>
            
            <div class="relative">
                <i class="fas fa-lock absolute left-4 top-4 text-slate-400"></i>
                <input type="password" name="password" placeholder="Sua senha" required 
                    class="w-full p-4 pl-12 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none transition-all">
            </div>

           <a href="termos.txt">Termos de Uso</a>
            <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-bold shadow-lg hover:bg-blue-700 active:scale-[0.98] transition-all">
                Entrar no Sistema
            </button>
        </form>

        <div class="mt-8 text-center text-slate-400 text-xs">
            <p><i class="fas fa-shield-halved"></i> Seus ficheiros são decifrados apenas em memória.</p>
        </div>
    </div>
</body>
</html>

