<?php
require_once 'config.php';
checkLogin();

// 1. Validar se o ID do utilizador foi passado
if (!isset($_GET['uid']) || !is_numeric($_GET['uid'])) {
    die("Utilizador inválido.");
}

$target_user_id = (int)$_GET['uid'];

// 2. Buscar informações do utilizador pendente
$stmt_u = $conn->prepare("SELECT nome, username, status FROM users WHERE id = ?");
$stmt_u->bind_param("i", $target_user_id);
$stmt_u->execute();
$target_user = $stmt_u->get_result()->fetch_assoc();

if (!$target_user) {
    die("Utilizador não encontrado.");
}

// 3. Buscar todas as fotos da triagem deste utilizador
$stmt_f = $conn->prepare("SELECT caminho FROM triagem_fotos WHERE user_id = ?");
$stmt_f->bind_param("i", $target_user_id);
$stmt_f->execute();
$fotos = $stmt_f->get_result();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise de Triagem - @<?= $target_user['username'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .img-zoom:active { transform: scale(1.5); transition: 0.3s; z-index: 50; }
    </style>
</head>
<body class="bg-slate-900 text-white min-h-screen p-4 md:p-10">

    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <a href="perfil.php" class="text-slate-400 hover:text-white flex items-center gap-2 transition">
                <i class="fas fa-arrow-left"></i> Voltar ao Perfil
            </a>
            <div class="text-right">
                <h1 class="text-xl font-bold"><?= htmlspecialchars($target_user['nome']) ?></h1>
                <p class="text-blue-400 text-xs font-mono">@<?= htmlspecialchars($target_user['username']) ?></p>
            </div>
        </div>

        <div class="bg-blue-600/20 border border-blue-500/30 p-4 rounded-2xl mb-8 flex items-center justify-between">
            <p class="text-sm">
                <i class="fas fa-info-circle mr-2"></i> 
                Este utilizador está <strong><?= strtoupper($target_user['status']) ?></strong>. Analise as fotos abaixo.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php if ($fotos->num_rows === 0): ?>
                <div class="col-span-full py-20 text-center bg-slate-800/50 rounded-3xl border-2 border-dashed border-slate-700">
                    <i class="fas fa-image-slash text-4xl text-slate-600 mb-4"></i>
                    <p class="text-slate-400">Nenhuma foto encontrada para esta triagem.</p>
                </div>
            <?php else: ?>
                <?php while($f = $fotos->fetch_assoc()): ?>
                    <div class="group relative overflow-hidden rounded-3xl bg-slate-800 border border-slate-700 shadow-xl">
                        <img src="uploads/triagem/<?= htmlspecialchars($f['caminho']) ?>" 
                             class="w-full h-auto object-cover img-zoom cursor-pointer"
                             alt="Foto de triagem">
                        <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-white/70">Foto de Verificação</p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <div class="mt-12 text-center border-t border-slate-800 pt-8">
            <p class="text-slate-500 text-xs mb-4 uppercase font-black tracking-widest">Decisão Final</p>
            <div class="flex justify-center gap-4">
                <a href="perfil.php" class="bg-white text-slate-900 px-8 py-3 rounded-2xl font-bold hover:bg-slate-200 transition">
                    Voltar para Aprovar
                </a>
            </div>
        </div>
    </div>

    <script>
        // Pequeno script para avisar que pode clicar/segurar para ver melhor
        console.log("Dica: Clique e segure nas fotos para ampliar.");
    </script>
</body>
</html>
