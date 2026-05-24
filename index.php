<?php
require_once __DIR__ .'/../db/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
checkLogin();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Configuração de URL
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['SERVER_NAME'];
$port = $_SERVER['SERVER_PORT'];
$display_port = ($port == "80" || $port == "443") ? "" : ":" . $port;
$script_path = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
$path = ($script_path === '/' || $script_path === '\\') ? '' : rtrim($script_path, '/');
$base_url = "{$protocol}://{$host}{$display_port}{$path}";

$user_id = $_SESSION['user_id'];

// PEGAR TEMA DO USUÁRIO
$stmt_theme = $conn->prepare("SELECT theme FROM users WHERE id = ?");
$stmt_theme->bind_param("i", $user_id);
$stmt_theme->execute();
$user_theme = $stmt_theme->get_result()->fetch_assoc()['theme'] ?? 'aurora';

// DADOS DO USUÁRIO
$stmt_user = $conn->prepare("SELECT foto, username, nome FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();

// NAVEGAÇÃO
$search = $_GET['search'] ?? '';
$searchTerm = "%$search%";
$aba = $_GET['aba'] ?? 'meus';
$hash_folder = $_GET['folder'] ?? '0';
$current_folder = decodeId($hash_folder);

// AMIGOS (Para compartilhamento automático)
$amigos_query = $conn->prepare("SELECT u.id, u.nome FROM amizades a JOIN users u ON (u.id = a.amigo_id AND a.usuario_id = ?) OR (u.id = a.usuario_id AND a.amigo_id = ?) WHERE a.status = 'aceito' GROUP BY u.id");
$amigos_query->bind_param("ii", $user_id, $user_id);
$amigos_query->execute();
$meus_amigos = $amigos_query->get_result()->fetch_all(MYSQLI_ASSOC);
$js_amigos_ids = array_map(function($a) { return encodeId($a['id']); }, $meus_amigos);

// --- LÓGICA DE LISTAGEM ---
$items = [];
$owner_id = $user_id;

if ($current_folder > 0) {
    $stmt_owner = $conn->prepare("SELECT user_id FROM folders WHERE id = ?");
    $stmt_owner->bind_param("i", $current_folder);
    $stmt_owner->execute();
    $owner_res = $stmt_owner->get_result()->fetch_assoc();
    $owner_id = $owner_res['user_id'] ?? 0;

    $stmt_f = $conn->prepare("SELECT id, nome, status, 'folder' as item_type FROM folders WHERE user_id = ? AND parent_id = ? AND nome LIKE ?");
    $stmt_f->bind_param("iis", $owner_id, $current_folder, $searchTerm);
    $stmt_f->execute();
    $folders_res = $stmt_f->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt_file = $conn->prepare("SELECT *, 'file' as item_type FROM files WHERE folder_id = ? AND nome LIKE ? ORDER BY uploaded_at DESC");
    $stmt_file->bind_param("is", $current_folder, $searchTerm);
    $stmt_file->execute();
    $files_res = $stmt_file->get_result()->fetch_all(MYSQLI_ASSOC);

    $items = array_merge($folders_res, $files_res);
} else {
    if ($aba === 'recebidos') {
        $query = "
            SELECT f.id, f.nome, f.tipo, u.nome as dono, 'file' as item_type, f.uploaded_at as data_ref, 'private' as status
            FROM files f
            INNER JOIN compartilhamentos c ON f.id = c.file_id
            INNER JOIN users u ON f.user_id = u.id
            WHERE c.para_user_id = ? AND f.nome LIKE ?
            UNION ALL
            SELECT fd.id, fd.nome, 'folder' as tipo, u.nome as dono, 'folder' as item_type, fd.created_at as data_ref, fd.status
            FROM folders fd
            INNER JOIN compartilhamentos_folders cf ON fd.id = cf.folder_id
            INNER JOIN users u ON fd.user_id = u.id
            WHERE cf.para_user_id = ? AND fd.nome LIKE ?
            ORDER BY data_ref DESC";
        $stmt_rec = $conn->prepare($query);
        $stmt_rec->bind_param("isis", $user_id, $searchTerm, $user_id, $searchTerm);
        $stmt_rec->execute();
        $items = $stmt_rec->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $stmt_f = $conn->prepare("SELECT id, nome, status, 'folder' as item_type FROM folders WHERE user_id = ? AND parent_id = 0 AND nome LIKE ?");
        $stmt_f->bind_param("is", $user_id, $searchTerm);
        $stmt_f->execute();
        $folders_res = $stmt_f->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt_file = $conn->prepare("SELECT *, 'file' as item_type FROM files WHERE user_id = ? AND folder_id = 0 AND nome LIKE ? ORDER BY uploaded_at DESC");
        $stmt_file->bind_param("is", $user_id, $searchTerm);
        $stmt_file->execute();
        $files_res = $stmt_file->get_result()->fetch_all(MYSQLI_ASSOC);
        $items = array_merge($folders_res, $files_res);
    }
}

function gerarTokenDownload($file_id) {
    $secret_key = getenv("SKEY") ?: "sua_chave_aqui";
    $dados = $file_id . "|" . (time() + 3600);
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(openssl_encrypt($dados, "aes-128-cbc", $secret_key, 0, substr(hash('sha256', $secret_key), 0, 16))));
}

$seed = preg_replace('/[^a-zA-Z0-9]/', '', $user_data['username'] ?? 'default');
$img_src = "https://api.dicebear.com/9.x/dylan/svg?seed={$seed}";
if (!empty($user_data['foto']) && file_exists("perfil/" . $user_data['foto'])) $img_src = "perfil/" . $user_data['foto'];

// TEMAS DISPONÍVEIS
$themes = [
    'aurora' => [
        'bg' => 'bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900',
        'card' => 'bg-white/10 backdrop-blur-xl border-white/20',
        'text' => 'text-white',
        'text_secondary' => 'text-white/70',
        'accent' => 'from-purple-500 to-pink-500',
        'accent_solid' => 'bg-gradient-to-r from-purple-500 to-pink-500',
        'icon' => 'text-purple-400'
    ],
    'dark' => [
        'bg' => 'bg-black',
        'card' => 'bg-neutral-900 border-neutral-800',
        'text' => 'text-white',
        'text_secondary' => 'text-neutral-400',
        'accent' => 'from-emerald-500 to-teal-500',
        'accent_solid' => 'bg-gradient-to-r from-emerald-500 to-teal-500',
        'icon' => 'text-emerald-500'
    ],
    'light' => [
        'bg' => 'bg-gray-50',
        'card' => 'bg-white border-gray-200',
        'text' => 'text-gray-900',
        'text_secondary' => 'text-gray-600',
        'accent' => 'from-blue-500 to-cyan-500',
        'accent_solid' => 'bg-gradient-to-r from-blue-500 to-cyan-500',
        'icon' => 'text-blue-600'
    ],
    'ocean' => [
        'bg' => 'bg-gradient-to-br from-cyan-900 via-blue-900 to-indigo-900',
        'card' => 'bg-white/10 backdrop-blur-xl border-white/20',
        'text' => 'text-white',
        'text_secondary' => 'text-white/70',
        'accent' => 'from-cyan-400 to-blue-500',
        'accent_solid' => 'bg-gradient-to-r from-cyan-400 to-blue-500',
        'icon' => 'text-cyan-400'
    ],
    'sunset' => [
        'bg' => 'bg-gradient-to-br from-orange-900 via-red-900 to-pink-900',
        'card' => 'bg-white/10 backdrop-blur-xl border-white/20',
        'text' => 'text-white',
        'text_secondary' => 'text-white/70',
        'accent' => 'from-orange-400 to-red-500',
        'accent_solid' => 'bg-gradient-to-r from-orange-400 to-red-500',
        'icon' => 'text-orange-400'
    ],
    'forest' => [
        'bg' => 'bg-gradient-to-br from-emerald-900 via-green-900 to-teal-900',
        'card' => 'bg-white/10 backdrop-blur-xl border-white/20',
        'text' => 'text-white',
        'text_secondary' => 'text-white/70',
        'accent' => 'from-emerald-400 to-green-500',
        'accent_solid' => 'bg-gradient-to-r from-emerald-400 to-green-500',
        'icon' => 'text-emerald-400'
    ],
    'glassmorphism' => [
        'bg' => 'bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900',
        'card' => 'bg-white/5 backdrop-blur-2xl border-white/10',
        'text' => 'text-white',
        'text_secondary' => 'text-white/60',
        'accent' => 'from-violet-400 to-fuchsia-500',
        'accent_solid' => 'bg-gradient-to-r from-violet-400 to-fuchsia-500',
        'icon' => 'text-violet-400'
    ]
];

$current_theme = $themes[$user_theme] ?? $themes['aurora'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MyDrive Pro - <?= ucfirst($user_theme) ?> Theme</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/resumablejs/resumable.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .item-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }
        
        .item-card:hover {
            transform: translateY(-8px);
        }
        
        .fab-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
            display: flex;
            flex-direction: column-reverse;
            align-items: center;
            gap: 1rem;
        }
        
        .fab-main {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            cursor: pointer;
            transition: all 0.3s;
            z-index: 1001;
        }
        
        .fab-main.active {
            transform: rotate(45deg);
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .fab-menu {
            display: none;
            flex-direction: column-reverse;
            gap: 0.8rem;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .fab-menu.show {
            display: flex;
            animation: slideUp 0.3s forwards;
        }
        
        .fab-item {
            width: 50px;
            height: 50px;
            background: white;
            color: #64748b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        
        .fab-item:hover {
            transform: scale(1.1);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .fab-label {
            position: absolute;
            right: 65px;
            background: #1e293b;
            color: white;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: 0.2s;
        }
        
        .fab-item:hover .fab-label {
            opacity: 1;
            transform: translateX(-5px);
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Animações de loading */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        .shimmer {
            animation: shimmer 2s infinite linear;
            background: linear-gradient(to right, #f6f7f8 0%, #edeef1 20%, #f6f7f8 40%, #f6f7f8 100%);
            background-size: 1000px 100%;
        }
        
        /* Scrollbar personalizada */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }
        
        /* Grid responsivo */
        .grid-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.5rem;
        }
        
        @media (max-width: 640px) {
            .grid-items {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }
        }
    </style>
</head>
<body class="<?= $current_theme['bg'] ?> <?= $current_theme['text'] ?> overflow-x-hidden">

<!-- Theme Selector Modal -->
<div id="themeModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[200] hidden items-center justify-center">
    <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-8 max-w-2xl w-full mx-4 border border-white/20">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold">🎨 Escolha seu Tema</h3>
            <button onclick="closeThemeModal()" class="text-white/70 hover:text-white text-2xl">&times;</button>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach($themes as $key => $theme): ?>
            <button onclick="changeTheme('<?= $key ?>')" class="group">
                <div class="h-32 rounded-2xl overflow-hidden border-2 border-white/20 group-hover:border-white/60 transition-all mb-2">
                    <div class="w-full h-full <?= $theme['bg'] ?> flex items-center justify-center">
                        <i class="fas fa-palette text-3xl text-white/80"></i>
                    </div>
                </div>
                <p class="text-sm font-semibold text-center capitalize"><?= $key ?></p>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="fab-container">
    <div class="fab-main" onclick="toggleFab()"><i class="fas fa-plus text-xl"></i></div>
    <div id="fabMenu" class="fab-menu">
        <a href="logout.php" class="fab-item" style="background:#ef4444;color:white"><i class="fas fa-sign-out-alt"></i><span class="fab-label">Sair</span></a>
        <?php if($aba === 'meus'): ?>
        <div class="fab-item" id="btnUpload"><i class="fas fa-file-upload"></i><span class="fab-label">Subir Arquivo</span></div>
        <div class="fab-item" onclick="criarPasta()"><i class="fas fa-folder-plus"></i><span class="fab-label">Nova Pasta</span></div>
        <div class="fab-item" onclick="abrirBuscaAmigos()"><i class="fas fa-user-plus"></i><span class="fab-label">Add Amigo</span></div>
        <div class="fab-item" onclick="mostrarMeuQR()"><i class="fas fa-qrcode"></i><span class="fab-label">Meu QR</span></div>
        <?php endif; ?>
        <div class="fab-item" onclick="openThemeModal()"><i class="fas fa-palette"></i><span class="fab-label">Temas</span></div>
    </div>
</div>

<div id="mobileMenu" class="fixed inset-0 bg-black/80 backdrop-blur-md z-[100] hidden">
    <div class="w-80 bg-white/10 backdrop-blur-xl h-full p-6 shadow-2xl border-r border-white/20">
        <div class="flex justify-between items-center mb-10">
            <span class="text-2xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">MYDRIVE</span>
            <button onclick="toggleMenu()"><i class="fas fa-times text-xl text-white/70"></i></button>
        </div>
        <nav class="space-y-3">
            <a href="index.php?aba=meus" class="flex items-center gap-3 p-4 rounded-2xl <?= $aba=='meus'?$current_theme['accent_solid'].' text-white shadow-lg':'text-white/70 hover:bg-white/10' ?> font-bold text-xs uppercase tracking-widest transition-all">
                <i class="fas fa-hdd text-lg"></i> Meu Drive
            </a>
            <a href="index.php?aba=recebidos" class="flex items-center gap-3 p-4 rounded-2xl <?= $aba=='recebidos'?$current_theme['accent_solid'].' text-white shadow-lg':'text-white/70 hover:bg-white/10' ?> font-bold text-xs uppercase tracking-widest transition-all">
                <i class="fas fa-share-nodes text-lg"></i> Recebidos
            </a>
        </nav>
    </div>
</div>

<div class="flex h-screen overflow-hidden">
    <aside class="w-64 <?= $current_theme['card'] ?> border-r border-white/10 hidden md:flex flex-col p-6 shrink-0">
        <div class="mb-10">
            <span class="text-3xl font-black bg-gradient-to-r <?= $current_theme['accent'] ?> bg-clip-text text-transparent italic">MyDrive</span>
            <p class="text-xs <?= $current_theme['text_secondary'] ?> mt-1">by <?= htmlspecialchars($user_data['nome']) ?></p>
        </div>
        <nav class="space-y-2 flex-1">
            <a href="index.php?aba=meus" class="flex items-center gap-3 p-4 rounded-2xl <?= $aba=='meus'?$current_theme['accent_solid'].' text-white shadow-lg':'text-white/60 hover:bg-white/10 hover:text-white' ?> font-bold text-[11px] uppercase tracking-widest transition-all">
                <i class="fas fa-hdd text-lg"></i> Meu Drive
            </a>
            <a href="index.php?aba=recebidos" class="flex items-center gap-3 p-4 rounded-2xl <?= $aba=='recebidos'?$current_theme['accent_solid'].' text-white shadow-lg':'text-white/60 hover:bg-white/10 hover:text-white' ?> font-bold text-[11px] uppercase tracking-widest transition-all">
                <i class="fas fa-share-nodes text-lg"></i> Recebidos
            </a>
        </nav>
        <div class="pt-6 border-t border-white/10">
            <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5">
                <div class="w-10 h-10 rounded-xl overflow-hidden"><img src="<?= $img_src ?>" class="w-full h-full object-cover"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold truncate"><?= htmlspecialchars($user_data['nome']) ?></p>
                    <p class="text-xs <?= $current_theme['text_secondary'] ?>">@<?= htmlspecialchars($user_data['username']) ?></p>
                </div>
                <a href="perfil.php" class="text-white/50 hover:text-white"><i class="fas fa-cog"></i></a>
            </div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
        <header class="h-20 <?= $current_theme['card'] ?> border-b border-white/10 flex items-center justify-between px-4 md:px-8 shrink-0 backdrop-blur-md">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <button onclick="toggleMenu()" class="md:hidden text-white/60 shrink-0"><i class="fas fa-bars text-xl"></i></button>
                <form action="" method="GET" class="relative w-full max-w-[200px] md:max-w-xs">
                    <input type="hidden" name="aba" value="<?= $aba ?>">
                    <input type="hidden" name="folder" value="<?= $hash_folder ?>">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 <?= $current_theme['text_secondary'] ?> text-xs"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar arquivos..." class="w-full pl-9 pr-4 py-2.5 bg-white/5 border border-white/10 rounded-xl text-sm font-medium outline-none focus:border-white/30 transition-all <?= $current_theme['text'] ?> placeholder:text-white/30">
                </form>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="openThemeModal()" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 transition-all flex items-center justify-center">
                    <i class="fas fa-palette <?= $current_theme['icon'] ?>"></i>
                </button>
                <a href="perfil.php" class="block w-10 h-10 rounded-xl overflow-hidden ring-2 ring-white/20 hover:ring-white/40 transition-all">
                    <img src="<?= $img_src ?>" class="w-full h-full object-cover">
                </a>
            </div>
        </header>

        <div class="flex-1 p-4 md:p-10 overflow-y-auto">
            <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
                <div>
                    <?php if($current_folder > 0): ?>
                    <button onclick="window.history.back()" class="text-[11px] font-black <?= $current_theme['icon'] ?> uppercase mb-2 flex items-center gap-2 bg-white/10 px-4 py-2 rounded-full hover:bg-white/20 transition-all">
                        <i class="fas fa-chevron-left"></i> Voltar
                    </button>
                    <?php endif; ?>
                    <h1 class="text-3xl md:text-4xl font-black tracking-tighter">
                        <?= $aba == 'recebidos' ? '📥 Recebidos' : '✨ Meu Espaço' ?>
                    </h1>
                    <p class="<?= $current_theme['text_secondary'] ?> text-sm mt-2">
                        <?= count($items) ?> <?= count($items) == 1 ? 'item' : 'itens' ?> encontrado(s)
                    </p>
                </div>
                
                <?php if($aba === 'meus' && count($items) > 0): ?>
                <div class="flex gap-2">
                    <button onclick="ordenarItems()" class="px-4 py-2 bg-white/10 rounded-xl text-sm font-semibold hover:bg-white/20 transition-all">
                        <i class="fas fa-sort-amount-down-alt mr-2"></i> Ordenar
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <?php if(count($items) === 0): ?>
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <i class="fas fa-folder-open text-6xl <?= $current_theme['text_secondary'] ?> mb-4 opacity-50"></i>
                <h3 class="text-xl font-bold mb-2">Nenhum item encontrado</h3>
                <p class="<?= $current_theme['text_secondary'] ?>">Comece enviando arquivos ou criando pastas</p>
                <?php if($aba === 'meus'): ?>
                <button onclick="criarPasta()" class="mt-6 px-6 py-3 <?= $current_theme['accent_solid'] ?> rounded-xl font-bold shadow-lg hover:shadow-xl transition-all">
                    <i class="fas fa-plus mr-2"></i> Criar Primeira Pasta
                </button>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="grid-items pb-32">
                <?php foreach($items as $item):
                    $isFolder = ($item['item_type'] === 'folder');
                    $status = $item['status'] ?? 'private';
                    $iconStatus = ($status == 'public') ? 'fa-globe' : (($status == 'friends') ? 'fa-user-group' : 'fa-lock');
                    $encoded_id = encodeId($item['id']);
                ?>
                    <div class="item-card <?= $current_theme['card'] ?> rounded-2xl p-5 group">
                        <div class="absolute top-4 left-4 flex gap-2">
                            <i class="fas <?= $iconStatus ?> text-[11px] <?= $current_theme['icon'] ?> opacity-70"></i>
                        </div>

                        <?php if($aba !== 'recebidos' && $owner_id == $user_id): ?>
                        <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-all z-10">
                            <button onclick="opcoesItem('<?= $encoded_id ?>', '<?= $item['item_type'] ?>', '<?= $status ?>')" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-all">
                                <i class="fas fa-ellipsis-v text-sm"></i>
                            </button>
                        </div>
                        <?php endif; ?>

                        <div class="flex flex-col items-center pt-8 pb-4">
                            <?php if($isFolder): ?>
                                <div class="w-24 h-24 flex items-center justify-center mb-4 relative">
                                    <i class="fas fa-folder-open text-7xl <?= $current_theme['icon'] ?> opacity-80"></i>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <i class="fas fa-chevron-right text-white/40 text-2xl ml-8"></i>
                                    </div>
                                </div>
                                <a href="index.php?folder=<?= $encoded_id ?>&aba=<?= $aba ?>" class="font-bold text-center px-2 py-1 hover:<?= $current_theme['icon'] ?> transition-colors line-clamp-2">
                                    <?= htmlspecialchars($item['nome']) ?>
                                </a>

                                <?php if($aba !== 'recebidos'): ?>
                                <button onclick="partilhar('<?= $encoded_id ?>')" class="mt-4 text-[10px] font-black px-4 py-2 rounded-full bg-white/10 hover:bg-white/20 transition-all w-full">
                                    <i class="fas fa-share-nodes mr-2"></i> Compartilhar
                                </button>
                                <?php endif; ?>
                            <?php else:
                                $fileToken = gerarTokenDownload($item['id']);
                                $type = strtolower($item['tipo'] ?? '');
                                ?>
                                <div class="w-24 h-24 flex items-center justify-center mb-4">
                                    <?php if(strpos($type, 'image') !== false): ?>
                                        <div class="w-full h-full rounded-2xl overflow-hidden bg-white/5">
                                            <img src="stream.php?file=<?= $fileToken ?>"
                                                 class="w-full h-full object-cover"
                                                 onerror="this.parentElement.innerHTML='<i class=\'fas fa-file-image text-5xl <?= $current_theme['icon'] ?>\'></i>'">
                                        </div>
                                    <?php elseif(strpos($type, 'pdf') !== false): ?>
                                        <i class="fas fa-file-pdf text-6xl text-red-400"></i>
                                    <?php elseif(strpos($type, 'video') !== false): ?>
                                        <i class="fas fa-file-video text-6xl <?= $current_theme['icon'] ?>"></i>
                                    <?php elseif(strpos($type, 'audio') !== false): ?>
                                        <i class="fas fa-file-audio text-6xl <?= $current_theme['icon'] ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-file-alt text-6xl <?= $current_theme['icon'] ?>"></i>
                                    <?php endif; ?>
                                </div>
                                <span class="font-bold text-center px-2 line-clamp-2"><?= htmlspecialchars($item['nome']) ?></span>
                                <div class="flex gap-2 mt-4 w-full">
                                    <a href="download.php?file=<?= $fileToken ?>" class="flex-1 text-center text-[10px] font-black px-3 py-2 rounded-full <?= $current_theme['accent_solid'] ?> text-white hover:shadow-lg transition-all">
                                        <i class="fas fa-download mr-1"></i> Baixar
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<div id="uploadProgress" class="hidden fixed bottom-10 left-1/2 -translate-x-1/2 w-80 bg-white/10 backdrop-blur-xl p-4 rounded-2xl shadow-2xl z-[200] border border-white/20">
    <div class="flex justify-between items-center mb-2">
        <span class="text-[10px] font-black uppercase tracking-wider">Enviando...</span>
        <span id="progPerc" class="text-sm font-black <?= $current_theme['icon'] ?>">0%</span>
    </div>
    <div class="h-2 bg-white/10 rounded-full overflow-hidden">
        <div id="progBar" class="h-full <?= $current_theme['accent_solid'] ?> w-0 transition-all duration-300"></div>
    </div>
</div>

<script>
const MEUS_AMIGOS_IDS = <?= json_encode($js_amigos_ids) ?>;

function toggleMenu() {
    document.getElementById('mobileMenu').classList.toggle('hidden');
}

function toggleFab() {
    document.getElementById('fabMenu').classList.toggle('show');
    document.querySelector('.fab-main').classList.toggle('active');
}

function openThemeModal() {
    document.getElementById('themeModal').classList.remove('hidden');
    document.getElementById('themeModal').classList.add('flex');
}

function closeThemeModal() {
    document.getElementById('themeModal').classList.add('hidden');
    document.getElementById('themeModal').classList.remove('flex');
}

function changeTheme(theme) {
    fetch('change_theme.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'theme=' + theme
    }).then(() => {
        location.reload();
    });
}

function copiarLink(id) {
    const url = `<?= $base_url ?>/view_folder.php?id=${id}`;
    navigator.clipboard.writeText(url).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Link copiado!',
            showConfirmButton: false,
            timer: 1500,
            background: 'rgba(0,0,0,0.8)',
            color: '#fff'
        });
    }).catch(() => {
        Swal.fire('Erro', 'Não foi possível copiar.', 'error');
    });
}

async function criarPasta() {
    const { value: f } = await Swal.fire({
        title: '📁 Nova Pasta',
        html: `
            <input id="f-n" class="swal2-input" placeholder="Nome da pasta">
            <select id="f-s" class="swal2-input">
                <option value="private">🔒 Privada</option>
                <option value="friends">👥 Amigos</option>
                <option value="public">🌍 Pública</option>
            </select>
        `,
        showCancelButton: true,
        confirmButtonText: 'Criar',
        cancelButtonText: 'Cancelar',
        background: 'rgba(0,0,0,0.9)',
        backdrop: 'true'
    });

    if (!f || !f.n) return;

    try {
        const p = new URLSearchParams({
            action: 'create',
            nome: f.n,
            status: f.s,
            parent_id: '<?= $current_folder ?>'
        });

        const res = await fetch('api_folders.php', {
            method: 'POST',
            body: p
        });

        const data = await res.json();

        if (!data.ok) {
            return Swal.fire('Erro', data.msg || 'Falha ao criar pasta.', 'error');
        }

        if (f.s === 'friends' && data.id_encoded) {
            await dispararCompartilhamentoSilencioso(data.id_encoded);
        }

        location.reload();
    } catch (e) {
        Swal.fire('Erro', 'Falha de conexão.', 'error');
    }
}

function opcoesItem(id, type, status) {
    let html = '';
    if (type === 'folder' && status === 'public') {
        html = `
            <button onclick="copiarLink('${id}')" class="swal2-confirm swal2-styled" style="background:#10b981;margin-bottom:10px;width:80%">
                🔗 COPIAR LINK PÚBLICO
            </button>
        `;
    }

    Swal.fire({
        title: '⚙️ Opções',
        html: html,
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: '✏️ Renomear',
        denyButtonText: '🗑️ Excluir',
        cancelButtonText: 'Fechar',
        confirmButtonColor: '#2563eb',
        denyButtonColor: '#ef4444',
        background: 'rgba(0,0,0,0.9)',
        backdrop: 'true'
    }).then(r => {
        if (r.isConfirmed) {
            Swal.fire({
                title: 'Novo Nome',
                input: 'text',
                showCancelButton: true,
                confirmButtonText: 'Salvar',
                background: 'rgba(0,0,0,0.9)'
            }).then(res => {
                if (!res.value) return;
                window.location.href = `rename.php?id=${id}&type=${type}&newname=${encodeURIComponent(res.value)}`;
            });
        } else if (r.isDenied) {
            Swal.fire({
                title: '⚠️ APAGAR?',
                text: 'Esta ação apagará permanentemente!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Sim, deletar!',
                cancelButtonText: 'Cancelar',
                background: 'rgba(0,0,0,0.9)'
            }).then(async (res) => {
                if (!res.isConfirmed) return;
                try {
                    const params = new URLSearchParams({ id: id, type: type });
                    const req = await fetch('delete.php', { method: 'POST', body: params });
                    const data = await req.json();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deletado!',
                            showConfirmButton: false,
                            timer: 1000
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Erro', data.message || 'Não foi possível apagar.', 'error');
                    }
                } catch (e) {
                    Swal.fire('Erro', 'Falha de comunicação.', 'error');
                }
            });
        }
    });
}

async function dispararCompartilhamentoSilencioso(itemId) {
    if (MEUS_AMIGOS_IDS.length === 0) return;
    try {
        await Promise.all(MEUS_AMIGOS_IDS.map(async (paraId) => {
            const p = new URLSearchParams({ file_id: itemId, para_id: paraId });
            const req = await fetch('compartilhar.php', { method: 'POST', body: p });
            return await req.text();
        }));
    } catch (e) {
        console.log(e);
    }
}

function partilhar(id) {
    if (MEUS_AMIGOS_IDS.length === 0) {
        return Swal.fire('AVISO', 'Você não tem amigos ainda.', 'info');
    }
    Swal.fire({
        title: '📤 Compartilhar?',
        text: 'Enviar para todos os amigos?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim!',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
            await dispararCompartilhamentoSilencioso(id);
        }
    }).then(r => {
        if (r.isConfirmed) {
            Swal.fire('OK', 'Compartilhado com sucesso!', 'success');
        }
    });
}

async function abrirBuscaAmigos() {
    const { value: u } = await Swal.fire({
        title: '👥 Adicionar Amigo',
        input: 'text',
        inputPlaceholder: '@username',
        showCancelButton: true,
        confirmButtonText: 'Enviar convite',
        background: 'rgba(0,0,0,0.9)'
    });
    if (!u) return;
    try {
        const req = await fetch(`amizade.php?username=${encodeURIComponent(u.replace('@', ''))}`);
        const d = await req.json();
        if (d.ok) {
            Swal.fire('✅', 'Pedido de amizade enviado!', 'success');
        } else {
            Swal.fire('ERRO', d.msg, 'error');
        }
    } catch (e) {
        Swal.fire('Erro', 'Falha de conexão.', 'error');
    }
}

function mostrarMeuQR() {
    Swal.fire({
        title: '📱 Meu QR Code',
        imageUrl: `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=mydrive_user:<?= urlencode($user_data['username']) ?>`,
        showConfirmButton: false,
        background: 'rgba(0,0,0,0.9)',
        showCloseButton: true
    });
}

function ordenarItems() {
    Swal.fire({
        title: 'Ordenar por',
        input: 'select',
        inputOptions: {
            'nome_asc': 'Nome (A-Z)',
            'nome_desc': 'Nome (Z-A)',
            'data_asc': 'Mais antigos',
            'data_desc': 'Mais recentes'
        },
        showCancelButton: true,
        confirmButtonText: 'Ordenar',
        background: 'rgba(0,0,0,0.9)'
    }).then(result => {
        if (result.value) {
            // Implementar ordenação
            console.log('Ordenar por:', result.value);
        }
    });
}

// Upload com Resumable.js
const btnUploadEl = document.getElementById('btnUpload');
if (btnUploadEl) {
    var rs = new Resumable({
        target: 'upload.php',
        chunkSize: 1 * 1024 * 1024,
        simultaneousUploads: 3,
        testChunks: false,
        fileParameterName: 'file',
        chunkNumberParameterName: 'resumableChunkNumber',
        totalChunksParameterName: 'resumableTotalChunks',
        query: { folder_id: '<?= $hash_folder ?>' }
    });

    rs.assignBrowse(btnUploadEl);
    rs.on('fileAdded', function(file) {
        document.getElementById('uploadProgress').classList.remove('hidden');
        rs.upload();
    });
    rs.on('fileProgress', function(file) {
        let p = Math.floor(file.progress() * 100);
        document.getElementById('progBar').style.width = p + "%";
        document.getElementById('progPerc').innerText = p + "%";
    });                                                       
    rs.on('fileSuccess', function(file, message) {
        location.reload();
    });
    rs.on('fileError', function(file, message) {
        Swal.fire('Erro no upload', message || 'Verifique as permissões.', 'error');
    });
}
</script>

</body>
</html>
