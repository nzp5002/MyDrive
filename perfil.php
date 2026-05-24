<?php
// DEBUG
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db/config.php';
checkLogin();

if (session_status() === PHP_SESSION_NONE) session_start();
$user_id = $_SESSION['user_id'];
$msg = "";
$invite_link = "";

// Funções
function gerarAvatar($username, $random = false) {
    $seed = preg_replace('/[^a-zA-Z0-9]/', '', $username);
    if ($random) $seed .= rand(1000, 9999);
    return "https://api.dicebear.com/9.x/dylan/svg?seed={$seed}";
}

function formatarTamanho($bytes) {
    if (!$bytes) return '0 bytes';
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Buscar tema do usuário
$user_theme = 'aurora';
$result = $conn->query("SELECT theme FROM users WHERE id = $user_id");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (isset($row['theme']) && $row['theme'] != '' && $row['theme'] != null) {
        $user_theme = $row['theme'];
    }
}

// Temas disponíveis
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

$current_theme = isset($themes[$user_theme]) ? $themes[$user_theme] : $themes['aurora'];

// ==================== ESTATÍSTICAS DO USUÁRIO ATUAL ====================
// Meus Arquivos
$meus_arquivos = $conn->query("SELECT COUNT(*) as total FROM files WHERE user_id = $user_id")->fetch_assoc()['total'];
$meu_tamanho = $conn->query("SELECT SUM(tamanho) as total FROM files WHERE user_id = $user_id")->fetch_assoc()['total'];

// Meus Arquivos por tipo
$meus_tipos_arquivo = $conn->query("SELECT tipo, COUNT(*) as total FROM files WHERE user_id = $user_id GROUP BY tipo ORDER BY total DESC LIMIT 5");

// Minhas Pastas
$minhas_pastas = $conn->query("SELECT COUNT(*) as total FROM folders WHERE user_id = $user_id")->fetch_assoc()['total'];
$minhas_pastas_publicas = $conn->query("SELECT COUNT(*) as total FROM folders WHERE user_id = $user_id AND status = 'public'")->fetch_assoc()['total'];
$minhas_pastas_privadas = $conn->query("SELECT COUNT(*) as total FROM folders WHERE user_id = $user_id AND status = 'private'")->fetch_assoc()['total'];
$minhas_pastas_amigos = $conn->query("SELECT COUNT(*) as total FROM folders WHERE user_id = $user_id AND status = 'friends'")->fetch_assoc()['total'];

// O que EU compartilhei
$meus_compartilhamentos = $conn->query("SELECT COUNT(*) as total FROM compartilhamentos WHERE de_user_id = $user_id")->fetch_assoc()['total'];
$meus_compartilhamentos_pastas = $conn->query("SELECT COUNT(*) as total FROM compartilhamentos_folders WHERE de_user_id = $user_id")->fetch_assoc()['total'];

// O que COMPARTILHARAM COMIGO
$arquivos_recebidos = $conn->query("SELECT COUNT(*) as total FROM compartilhamentos WHERE para_user_id = $user_id")->fetch_assoc()['total'];
$pastas_recebidas = $conn->query("SELECT COUNT(*) as total FROM compartilhamentos_folders WHERE para_user_id = $user_id")->fetch_assoc()['total'];

// Meus Amigos
$meus_amigos_total = $conn->query("SELECT COUNT(*) as total FROM amizades a WHERE (a.usuario_id = $user_id OR a.amigo_id = $user_id) AND a.status = 'aceito'")->fetch_assoc()['total'];

// Meus Convites
$meus_convites_total = $conn->query("SELECT COUNT(*) as total FROM convites WHERE criado_por = $user_id")->fetch_assoc()['total'];
$meus_convites_usados = $conn->query("SELECT COUNT(*) as total FROM convites WHERE criado_por = $user_id AND usado = 1")->fetch_assoc()['total'];

// Meus uploads por dia (últimos 7 dias)
$meus_uploads_por_dia = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = $conn->query("SELECT COUNT(*) as total FROM files WHERE user_id = $user_id AND DATE(uploaded_at) = '$date'")->fetch_assoc()['total'];
    $meus_uploads_por_dia[] = ['date' => $date, 'count' => $count];
}

// Limite de espaço (1GB = 1073741824 bytes)
$limite_espaco = 1073741824;
$percentual_usado = $meu_tamanho > 0 ? ($meu_tamanho / $limite_espaco) * 100 : 0;
$percentual_usado = min($percentual_usado, 100);
// ====================================================

// Processamento POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Atualizar Tema
    if (isset($_POST['update_theme']) && isset($_POST['theme'])) {
        $new_theme = $_POST['theme'];
        if (isset($themes[$new_theme])) {
            $stmt = $conn->prepare("UPDATE users SET theme = ? WHERE id = ?");
            $stmt->bind_param("si", $new_theme, $user_id);
            $stmt->execute();
            header("Location: perfil.php?msg=" . urlencode("🎨 Tema alterado para " . ucfirst($new_theme)));
            exit;
        }
    }
    
    // Aceitar amizade
    if (isset($_POST['aceitar_amizade'])) {
        $amizade_id = (int)$_POST['amizade_id'];
        $conn->query("UPDATE amizades SET status = 'aceito' WHERE id = $amizade_id");
        $dados = $conn->query("SELECT usuario_id, amigo_id FROM amizades WHERE id = $amizade_id")->fetch_assoc();
        if ($dados) {
            $uid = $dados['usuario_id'];
            $aid = $dados['amigo_id'];
            $conn->query("INSERT IGNORE INTO amizades (usuario_id, amigo_id, status) VALUES ($aid, $uid, 'aceito')");
        }
        header("Location: perfil.php?msg=" . urlencode("🤝 Amizade aceita"));
        exit;
    }
    
    // Recusar amizade
    if (isset($_POST['recusar_amizade'])) {
        $amizade_id = (int)$_POST['amizade_id'];
        $conn->query("DELETE FROM amizades WHERE id = $amizade_id");
        header("Location: perfil.php?msg=" . urlencode("❌ Amizade recusada"));
        exit;
    }
    
    // Avatar aleatório
    if (isset($_POST['random_avatar'])) {
        $avatar = gerarAvatar($_SESSION['user_nome'] ?? 'user', true);
        $stmt = $conn->prepare("UPDATE users SET foto = ? WHERE id = ?");
        $stmt->bind_param("si", $avatar, $user_id);
        $stmt->execute();
        header("Location: perfil.php?msg=" . urlencode("🎨 Avatar atualizado"));
        exit;
    }
    
    // Atualizar perfil
    if (isset($_POST['update_profile'])) {
        $novo_nome = $conn->real_escape_string($_POST['nome']);
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nome_foto = "user_" . $user_id . "_" . time() . "." . $ext;
            if (!is_dir('perfil/')) mkdir('perfil/', 0755, true);
            if (move_uploaded_file($_FILES['foto']['tmp_name'], "perfil/" . $nome_foto)) {
                $stmt = $conn->prepare("UPDATE users SET foto = ? WHERE id = ?");
                $stmt->bind_param("si", $nome_foto, $user_id);
                $stmt->execute();
            }
        }
        
        $stmt = $conn->prepare("UPDATE users SET nome = ? WHERE id = ?");
        $stmt->bind_param("si", $novo_nome, $user_id);
        $stmt->execute();
        $_SESSION['user_nome'] = $novo_nome;
        header("Location: perfil.php?msg=" . urlencode("💾 Perfil atualizado"));
        exit;
    }
    
    // Gerar convite
    if (isset($_POST['gerar_convite'])) {
        $token = bin2hex(random_bytes(8));
        $validade = (int)$_POST['dias_validade'];
        $data_expira = date('Y-m-d H:i:s', strtotime("+$validade days"));
        
        $check = $conn->query("SHOW TABLES LIKE 'convites'");
        if ($check->num_rows == 0) {
            $conn->query("CREATE TABLE convites (
                id INT AUTO_INCREMENT PRIMARY KEY,
                token VARCHAR(255) NOT NULL UNIQUE,
                criado_por INT NOT NULL,
                usado TINYINT(1) DEFAULT 0,
                expira_em DATETIME NOT NULL,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        $stmt = $conn->prepare("INSERT INTO convites (token, criado_por, usado, expira_em, criado_em) VALUES (?, ?, 0, ?, NOW())");
        $stmt->bind_param("sis", $token, $user_id, $data_expira);
        $stmt->execute();
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $invite_link = "{$protocol}://{$host}/registro.php?token=" . $token;
        $_SESSION['temp_invite_link'] = $invite_link;
        header("Location: perfil.php?msg=" . urlencode("🔗 Link gerado com sucesso"));
        exit;
    }
}

// Recuperar link temporário
if (isset($_SESSION['temp_invite_link'])) {
    $invite_link = $_SESSION['temp_invite_link'];
    unset($_SESSION['temp_invite_link']);
}

// Pegar mensagem
if(isset($_GET['msg'])) $msg = htmlspecialchars($_GET['msg']);

// Buscar dados do usuário (CORRIGIDO - sem created_at)
$user_data = $conn->query("SELECT nome, username, foto FROM users WHERE id = $user_id")->fetch_assoc();

// Buscar pedidos de amizade
$pedidos_amizade = $conn->query("SELECT a.id, u.nome, u.username, u.foto FROM amizades a JOIN users u ON a.usuario_id = u.id WHERE a.amigo_id = $user_id AND a.status = 'pendente'");

// Buscar amigos
$amigos = $conn->query("SELECT u.id, u.nome, u.username, u.foto FROM amizades a JOIN users u ON (u.id = a.amigo_id AND a.usuario_id = $user_id) OR (u.id = a.usuario_id AND a.amigo_id = $user_id) WHERE a.status = 'aceito' GROUP BY u.id LIMIT 12");

// Buscar convites criados pelo usuário
$meus_convites = $conn->query("SELECT * FROM convites WHERE criado_por = $user_id ORDER BY criado_em DESC");
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - MyDrive Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        * { font-family: 'Inter', sans-serif; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease-out; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .active-tab {
            background: <?= $user_theme == 'light' ? '#3b82f6' : ($user_theme == 'dark' ? '#10b981' : '#8b5cf6') ?>;
            color: white !important;
        }
        
        .theme-card { cursor: pointer; transition: transform 0.2s; }
        .theme-card:hover { transform: scale(1.05); }
        .copy-btn { cursor: pointer; transition: all 0.2s; }
        .copy-btn:hover { transform: scale(1.05); }
        .stat-card { transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 10px; }
    </style>
</head>
<body class="<?= $current_theme['bg'] ?> <?= $current_theme['text'] ?> min-h-screen">

<div class="max-w-6xl mx-auto py-6 md:py-10 px-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <a href="index.php" class="w-12 h-12 rounded-2xl <?= $current_theme['card'] ?> flex items-center justify-center hover:scale-110 transition-all">
            <i class="fas fa-arrow-left <?= $current_theme['icon'] ?>"></i>
        </a>
        <div class="text-center">
            <h1 class="text-2xl md:text-3xl font-black bg-gradient-to-r <?= $current_theme['accent'] ?> bg-clip-text text-transparent uppercase tracking-tighter">
                Meu Perfil
            </h1>
            <p class="text-[10px] <?= $current_theme['text_secondary'] ?> uppercase tracking-widest mt-1">Gerencie sua conta</p>
        </div>
        <div class="w-12"></div>
    </div>

    <!-- Alert -->
    <?php if($msg): ?>
    <div id="alertMsg" class="mb-6 <?= $current_theme['accent_solid'] ?> text-white p-4 rounded-2xl text-center font-bold shadow-xl">
        <i class="fas fa-check-circle mr-2"></i> <?= $msg ?>
    </div>
    <script>setTimeout(() => { let el = document.getElementById('alertMsg'); if(el) el.style.display = 'none'; }, 4000);</script>
    <?php endif; ?>

    <!-- Profile Card -->
    <div class="<?= $current_theme['card'] ?> rounded-3xl p-6 md:p-8 mb-6 text-center backdrop-blur-sm transition-all">
        <div class="relative w-32 h-32 mx-auto mb-4 group">
            <?php
                $foto_final = !empty($user_data['foto']) ? $user_data['foto'] : gerarAvatar($user_data['username']);
                $path = (filter_var($foto_final, FILTER_VALIDATE_URL)) ? $foto_final : "perfil/".$foto_final;
            ?>
            <img id="preview" src="<?= $path ?>" class="w-full h-full rounded-2xl object-cover border-4 border-white/20 shadow-2xl transition-all group-hover:scale-105">
            <form method="POST" enctype="multipart/form-data" id="formFoto">
                <label class="absolute inset-0 bg-black/60 rounded-2xl flex items-center justify-center text-white opacity-0 group-hover:opacity-100 cursor-pointer transition-all backdrop-blur-sm">
                    <i class="fas fa-camera text-2xl"></i>
                    <input type="file" name="foto" class="hidden" accept="image/*" onchange="document.getElementById('formFoto').submit()">
                </label>
                <input type="hidden" name="update_profile" value="1">
                <input type="hidden" name="nome" value="<?= htmlspecialchars($user_data['nome']) ?>">
            </form>
        </div>
        
        <h2 class="text-2xl md:text-3xl font-black tracking-tight mb-1"><?= htmlspecialchars($user_data['nome']) ?></h2>
        <p class="<?= $current_theme['text_secondary'] ?> font-bold text-xs uppercase tracking-widest mb-2">@<?= $user_data['username'] ?></p>
        <p class="text-[10px] <?= $current_theme['text_secondary'] ?>">
            <i class="fas fa-id-card mr-1"></i> ID do usuário: #<?= $user_id ?>
        </p>

        <form method="POST" class="mt-4">
            <button name="random_avatar" class="text-[11px] font-black px-4 py-2 rounded-full <?= $current_theme['accent_solid'] ?> text-white hover:scale-105 transition-all shadow-lg">
                <i class="fas fa-dice mr-2"></i> Avatar Aleatório
            </button>
        </form>
    </div>

    <!-- Seletor de Temas -->
    <div class="<?= $current_theme['card'] ?> rounded-3xl p-6 mb-6 backdrop-blur-sm">
        <h3 class="text-lg font-black mb-4 flex items-center gap-2">
            <i class="fas fa-palette <?= $current_theme['icon'] ?>"></i>
            Escolher Tema
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php foreach($themes as $key => $theme): ?>
            <form method="POST" class="theme-card">
                <input type="hidden" name="theme" value="<?= $key ?>">
                <button type="submit" name="update_theme" class="w-full <?= $key == $user_theme ? 'ring-2 ring-white ring-offset-2 ring-offset-transparent' : '' ?> rounded-xl overflow-hidden transition-all">
                    <div class="h-20 <?= $theme['bg'] ?> flex items-center justify-center">
                        <i class="fas fa-palette text-2xl text-white/80"></i>
                    </div>
                    <div class="p-2 text-center <?= $current_theme['card'] ?>">
                        <p class="text-[10px] font-black uppercase"><?= ucfirst($key) ?></p>
                    </div>
                </button>
            </form>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-3 mb-6 flex-wrap border-b border-white/10 pb-2">
        <button onclick="openTab(event, 'dados')" class="tab-btn px-6 py-3 rounded-xl font-black text-[11px] uppercase tracking-widest transition-all active-tab">
            <i class="fas fa-user mr-2"></i> Dados
        </button>
        <button onclick="openTab(event, 'social')" class="tab-btn px-6 py-3 rounded-xl font-black text-[11px] uppercase tracking-widest transition-all">
            <i class="fas fa-users mr-2"></i> Social
            <?php if($pedidos_amizade && $pedidos_amizade->num_rows > 0): ?>
                <span class="ml-2 inline-block w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
            <?php endif; ?>
        </button>
        <button onclick="openTab(event, 'convites')" class="tab-btn px-6 py-3 rounded-xl font-black text-[11px] uppercase tracking-widest transition-all">
            <i class="fas fa-gift mr-2"></i> Convites
        </button>
        <button onclick="openTab(event, 'estatisticas')" class="tab-btn px-6 py-3 rounded-xl font-black text-[11px] uppercase tracking-widest transition-all">
            <i class="fas fa-chart-line mr-2"></i> Minhas Estatísticas
        </button>
    </div>

    <!-- Tab Dados -->
    <div id="dados" class="tab-content active">
        <div class="<?= $current_theme['card'] ?> rounded-3xl p-6 md:p-8 backdrop-blur-sm">
            <h3 class="text-lg font-black mb-4 flex items-center gap-2">
                <i class="fas fa-edit <?= $current_theme['icon'] ?>"></i>
                Informações Pessoais
            </h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="text-[10px] font-black <?= $current_theme['text_secondary'] ?> uppercase tracking-widest ml-2">Nome Completo</label>
                    <input type="text" name="nome" required value="<?= htmlspecialchars($user_data['nome']) ?>"
                           class="w-full p-4 <?= $current_theme['card'] ?> rounded-2xl outline-none focus:ring-2 focus:ring-purple-500 transition-all mt-1">
                </div>
                <div>
                    <label class="text-[10px] font-black <?= $current_theme['text_secondary'] ?> uppercase tracking-widest ml-2">Nome de Usuário</label>
                    <input type="text" value="@<?= htmlspecialchars($user_data['username']) ?>" disabled
                           class="w-full p-4 <?= $current_theme['card'] ?> rounded-2xl opacity-70 outline-none mt-1">
                </div>
                <button type="submit" name="update_profile" class="w-full <?= $current_theme['accent_solid'] ?> text-white py-4 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:scale-105 transition-all shadow-xl">
                    <i class="fas fa-save mr-2"></i> Salvar Alterações
                </button>
            </form>
        </div>
    </div>

    <!-- Tab Social -->
    <div id="social" class="tab-content">
        <div class="<?= $current_theme['card'] ?> rounded-3xl overflow-hidden backdrop-blur-sm mb-6">
            <div class="p-5 border-b border-white/10 font-black text-[11px] uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-user-plus <?= $current_theme['icon'] ?>"></i>
                Pedidos Pendentes
                <?php if($pedidos_amizade && $pedidos_amizade->num_rows > 0): ?>
                    <span class="ml-2 px-2 py-1 bg-red-500/20 text-red-400 rounded-full text-[9px]"><?= $pedidos_amizade->num_rows ?></span>
                <?php endif; ?>
            </div>
            <div class="p-6 space-y-4">
                <?php if(!$pedidos_amizade || $pedidos_amizade->num_rows == 0): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-5xl <?= $current_theme['text_secondary'] ?> opacity-30 mb-3"></i>
                        <p class="<?= $current_theme['text_secondary'] ?> text-[11px] font-bold uppercase">Nenhum pedido pendente</p>
                    </div>
                <?php else: ?>
                    <?php while($p = $pedidos_amizade->fetch_assoc()): ?>
                    <div class="flex items-center justify-between p-4 <?= $current_theme['card'] ?> rounded-2xl transition-all hover:scale-105">
                        <div class="flex items-center gap-3">
                            <img src="<?= $p['foto'] ?: gerarAvatar($p['username']) ?>" class="w-12 h-12 rounded-xl object-cover">
                            <div>
                                <p class="text-sm font-black"><?= htmlspecialchars($p['nome']) ?></p>
                                <p class="text-[10px] <?= $current_theme['text_secondary'] ?>">@<?= $p['username'] ?></p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST">
                                <input type="hidden" name="amizade_id" value="<?= $p['id'] ?>">
                                <button name="aceitar_amizade" class="w-10 h-10 rounded-xl bg-emerald-500 text-white hover:bg-emerald-600 transition-all">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="amizade_id" value="<?= $p['id'] ?>">
                                <button name="recusar_amizade" class="w-10 h-10 rounded-xl bg-red-500/20 text-red-400 hover:bg-red-500 hover:text-white transition-all">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="<?= $current_theme['card'] ?> rounded-3xl p-6 backdrop-blur-sm">
            <h3 class="font-black text-[11px] uppercase tracking-widest mb-4 flex items-center gap-2">
                <i class="fas fa-user-friends <?= $current_theme['icon'] ?>"></i>
                Meus Amigos (<?= $amigos ? $amigos->num_rows : 0 ?>)
            </h3>
            <?php if(!$amigos || $amigos->num_rows == 0): ?>
                <div class="text-center py-8">
                    <i class="fas fa-users-slash text-5xl <?= $current_theme['text_secondary'] ?> opacity-30 mb-3"></i>
                    <p class="<?= $current_theme['text_secondary'] ?> text-[11px] font-bold uppercase">Nenhum amigo ainda</p>
                    <p class="text-[9px] <?= $current_theme['text_secondary'] ?> mt-1">Adicione amigos para compartilhar arquivos</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php while($am = $amigos->fetch_assoc()): ?>
                    <div class="p-4 <?= $current_theme['card'] ?> rounded-2xl text-center hover:scale-105 transition-all">
                        <img src="<?= $am['foto'] ?: gerarAvatar($am['username']) ?>" class="w-16 h-16 rounded-xl object-cover mx-auto mb-3 border-2 border-white/20">
                        <p class="text-[11px] font-black truncate"><?= htmlspecialchars($am['nome']) ?></p>
                        <p class="text-[9px] <?= $current_theme['text_secondary'] ?>">@<?= $am['username'] ?></p>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab Convites -->
    <div id="convites" class="tab-content">
        <div class="<?= $current_theme['card'] ?> rounded-3xl p-6 md:p-8 backdrop-blur-sm relative overflow-hidden mb-6">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-r <?= $current_theme['accent'] ?> rounded-full blur-3xl opacity-20"></div>
            <h3 class="text-lg font-black mb-4 flex items-center gap-2">
                <i class="fas fa-plus-circle <?= $current_theme['icon'] ?>"></i>
                Gerar Novo Convite
            </h3>
            <form method="POST" class="flex gap-3 flex-wrap">
                <select name="dias_validade" class="flex-1 p-3 <?= $current_theme['card'] ?> rounded-xl text-[11px] font-bold outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="1">⏰ 24 horas</option>
                    <option value="7">📅 7 dias</option>
                    <option value="30" selected>📆 30 dias</option>
                    <option value="90">📅 90 dias</option>
                    <option value="365">🎉 1 ano</option>
                </select>
                <button type="submit" name="gerar_convite" class="px-6 py-3 <?= $current_theme['accent_solid'] ?> text-white rounded-xl font-black text-[11px] uppercase hover:scale-105 transition-all shadow-lg">
                    <i class="fas fa-magic mr-2"></i> Gerar Link
                </button>
            </form>

            <?php if($invite_link): ?>
            <div class="mt-4 p-4 <?= $current_theme['card'] ?> rounded-xl border border-white/20">
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <code class="text-[10px] font-mono break-all flex-1 select-all"><?= htmlspecialchars($invite_link) ?></code>
                    <button onclick="copiarLink('<?= htmlspecialchars($invite_link) ?>')" class="copy-btn px-3 py-2 bg-white/10 rounded-lg hover:bg-white/20 transition-all">
                        <i class="fas fa-copy <?= $current_theme['icon'] ?>"></i>
                    </button>
                </div>
                <p class="text-[9px] <?= $current_theme['text_secondary'] ?> mt-2">
                    <i class="fas fa-info-circle mr-1"></i> Compartilhe este link com quem deseja convidar
                </p>
            </div>
            <?php endif; ?>
        </div>

        <div class="<?= $current_theme['card'] ?> rounded-3xl overflow-hidden backdrop-blur-sm">
            <div class="p-5 border-b border-white/10 font-black text-[11px] uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-list <?= $current_theme['icon'] ?>"></i>
                Meus Convites Criados
                <?php if($meus_convites && $meus_convites->num_rows > 0): ?>
                    <span class="ml-2 px-2 py-1 bg-green-500/20 text-green-400 rounded-full text-[9px]"><?= $meus_convites->num_rows ?></span>
                <?php endif; ?>
            </div>
            <div class="p-6">
                <?php if(!$meus_convites || $meus_convites->num_rows == 0): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-envelope-open-text text-5xl <?= $current_theme['text_secondary'] ?> opacity-30 mb-3"></i>
                        <p class="<?= $current_theme['text_secondary'] ?> text-[11px] font-bold uppercase">Você ainda não gerou nenhum convite</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php while($convite = $meus_convites->fetch_assoc()): 
                            $expira_em = new DateTime($convite['expira_em']);
                            $agora = new DateTime();
                            $ativo = ($expira_em > $agora && $convite['usado'] == 0);
                            $status_texto = $ativo ? 'Ativo' : ($convite['usado'] == 1 ? 'Usado' : 'Expirado');
                            $status_cor = $ativo ? 'green' : ($convite['usado'] == 1 ? 'blue' : 'red');
                            $convite_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/registro.php?token=" . $convite['token'];
                        ?>
                        <div class="p-4 <?= $current_theme['card'] ?> rounded-xl">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <span class="px-2 py-1 bg-<?= $status_cor ?>-500/20 text-<?= $status_cor ?>-400 rounded-full text-[9px] font-bold">
                                            <?php if($ativo): ?>
                                                <i class="fas fa-check-circle mr-1"></i> <?= $status_texto ?>
                                            <?php elseif($convite['usado'] == 1): ?>
                                                <i class="fas fa-check-double mr-1"></i> <?= $status_texto ?>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle mr-1"></i> <?= $status_texto ?>
                                            <?php endif; ?>
                                        </span>
                                        <span class="text-[9px] <?= $current_theme['text_secondary'] ?>">
                                            Criado: <?= date('d/m/Y H:i', strtotime($convite['criado_em'])) ?>
                                        </span>
                                    </div>
                                    <code class="text-[10px] font-mono break-all block mt-1"><?= htmlspecialchars($convite_link) ?></code>
                                </div>
                                <button onclick="copiarLink('<?= htmlspecialchars($convite_link) ?>')" class="copy-btn px-3 py-2 bg-white/10 rounded-lg hover:bg-white/20 transition-all">
                                    <i class="fas fa-copy mr-1"></i>
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tab MINHAS ESTATÍSTICAS (apenas do usuário atual) -->
    <div id="estatisticas" class="tab-content">
        <!-- Cards Pessoais -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="stat-card <?= $current_theme['card'] ?> rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-purple-500/20 flex items-center justify-center">
                        <i class="fas fa-user-circle text-2xl text-purple-400"></i>
                    </div>
                    <span class="text-3xl font-black"><?= htmlspecialchars($user_data['nome']) ?></span>
                </div>
                <h3 class="font-bold text-sm">Meu Perfil</h3>
                <p class="text-xs <?= $current_theme['text_secondary'] ?> mt-1">
                    <i class="fas fa-at mr-1"></i> @<?= $user_data['username'] ?>
                </p>
            </div>

            <div class="stat-card <?= $current_theme['card'] ?> rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-500/20 flex items-center justify-center">
                        <i class="fas fa-file-alt text-2xl text-blue-400"></i>
                    </div>
                    <span class="text-3xl font-black"><?= $meus_arquivos ?></span>
                </div>
                <h3 class="font-bold text-sm">Meus Arquivos</h3>
                <p class="text-xs <?= $current_theme['text_secondary'] ?> mt-1">
                    <i class="fas fa-database mr-1"></i> Espaço: <?= formatarTamanho($meu_tamanho) ?>
                </p>
                <div class="mt-2 w-full bg-white/10 rounded-full h-1.5">
                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: <?= $percentual_usado ?>%"></div>
                </div>
                <p class="text-[9px] <?= $current_theme['text_secondary'] ?> mt-1">
                    <?= round($percentual_usado, 1) ?>% de 1 GB
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="stat-card <?= $current_theme['card'] ?> rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 flex items-center justify-center">
                        <i class="fas fa-folder text-2xl text-emerald-400"></i>
                    </div>
                    <span class="text-3xl font-black"><?= $minhas_pastas ?></span>
                </div>
                <h3 class="font-bold text-sm">Minhas Pastas</h3>
                <p class="text-xs <?= $current_theme['text_secondary'] ?> mt-1">
                    <i class="fas fa-globe mr-1"></i> Públicas: <?= $minhas_pastas_publicas ?> | 
                    <i class="fas fa-user-friends mr-1"></i> Amigos: <?= $minhas_pastas_amigos ?> |
                    <i class="fas fa-lock mr-1"></i> Privadas: <?= $minhas_pastas_privadas ?>
                </p>
            </div>

            <div class="stat-card <?= $current_theme['card'] ?> rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-pink-500/20 flex items-center justify-center">
                        <i class="fas fa-handshake text-2xl text-pink-400"></i>
                    </div>
                    <span class="text-3xl font-black"><?= $meus_amigos_total ?></span>
                </div>
                <h3 class="font-bold text-sm">Meus Amigos</h3>
                <p class="text-xs <?= $current_theme['text_secondary'] ?> mt-1">
                    <i class="fas fa-user-plus mr-1"></i> Conecte-se com mais amigos
                </p>
            </div>

            <div class="stat-card <?= $current_theme['card'] ?> rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-cyan-500/20 flex items-center justify-center">
                        <i class="fas fa-share-alt text-2xl text-cyan-400"></i>
                    </div>
                    <span class="text-3xl font-black"><?= $meus_compartilhamentos + $meus_compartilhamentos_pastas ?></span>
                </div>
                <h3 class="font-bold text-sm">Compartilhei com Amigos</h3>
                <p class="text-xs <?= $current_theme['text_secondary'] ?> mt-1">
                    <i class="fas fa-file mr-1"></i> Arquivos: <?= $meus_compartilhamentos ?> | 
                    <i class="fas fa-folder mr-1"></i> Pastas: <?= $meus_compartilhamentos_pastas ?>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="stat-card <?= $current_theme['card'] ?> rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-orange-500/20 flex items-center justify-center">
                        <i class="fas fa-inbox text-2xl text-orange-400"></i>
                    </div>
                    <span class="text-3xl font-black"><?= $arquivos_recebidos + $pastas_recebidas ?></span>
                </div>
                <h3 class="font-bold text-sm">Recebidos de Amigos</h3>
                <p class="text-xs <?= $current_theme['text_secondary'] ?> mt-1">
                    <i class="fas fa-file mr-1"></i> Arquivos: <?= $arquivos_recebidos ?> | 
                    <i class="fas fa-folder mr-1"></i> Pastas: <?= $pastas_recebidas ?>
                </p>
            </div>

            <div class="stat-card <?= $current_theme['card'] ?> rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-yellow-500/20 flex items-center justify-center">
                        <i class="fas fa-envelope-open-text text-2xl text-yellow-400"></i>
                    </div>
                    <span class="text-3xl font-black"><?= $meus_convites_total ?></span>
                </div>
                <h3 class="font-bold text-sm">Meus Convites</h3>
                <p class="text-xs <?= $current_theme['text_secondary'] ?> mt-1">
                    <i class="fas fa-check-circle mr-1"></i> Usados: <?= $meus_convites_usados ?>
                </p>
            </div>
        </div>

        <!-- Meus Tipos de Arquivo -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="<?= $current_theme['card'] ?> rounded-3xl p-6">
                <h3 class="font-black text-sm uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-pie <?= $current_theme['icon'] ?>"></i>
                    Meus Tipos de Arquivo
                </h3>
                <canvas id="meusTiposChart" height="200"></canvas>
                <div class="mt-4 space-y-2">
                    <?php 
                    $meus_tipos_arquivo->data_seek(0);
                    while($type = $meus_tipos_arquivo->fetch_assoc()): ?>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2">
                            <i class="fas <?= strpos($type['tipo'], 'image') !== false ? 'fa-image' : (strpos($type['tipo'], 'pdf') !== false ? 'fa-file-pdf' : 'fa-file') ?>"></i>
                            <?= $type['tipo'] ?: 'Desconhecido' ?>
                        </span>
                        <span class="font-bold"><?= $type['total'] ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="<?= $current_theme['card'] ?> rounded-3xl p-6">
                <h3 class="font-black text-sm uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-bar <?= $current_theme['icon'] ?>"></i>
                    Meus Uploads (Últimos 7 dias)
                </h3>
                <canvas id="meusUploadsChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].classList.remove("active");
    }
    tablinks = document.getElementsByClassName("tab-btn");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active-tab");
        tablinks[i].style.background = "transparent";
        tablinks[i].style.color = "";
    }
    document.getElementById(tabName).classList.add("active");
    evt.currentTarget.classList.add("active-tab");
    localStorage.setItem('activeProfileTab', tabName);
}

function copiarLink(link) {
    navigator.clipboard.writeText(link).then(() => {
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
        Swal.fire('Erro', 'Não foi possível copiar o link', 'error');
    });
}

window.onload = () => {
    const savedTab = localStorage.getItem('activeProfileTab') || 'dados';
    const buttons = document.querySelectorAll('.tab-btn');
    const tabMap = { 'dados': 0, 'social': 1, 'convites': 2, 'estatisticas': 3 };
    const index = tabMap[savedTab] || 0;
    if (buttons[index]) {
        buttons[index].click();
    }
    
    // Gráfico de Meus Tipos de Arquivo
    const meusTiposCtx = document.getElementById('meusTiposChart')?.getContext('2d');
    if (meusTiposCtx) {
        const meusTiposData = <?php 
            $types_data = ['labels' => [], 'data' => []];
            $meus_tipos_arquivo->data_seek(0);
            while($type = $meus_tipos_arquivo->fetch_assoc()) {
                $types_data['labels'][] = $type['tipo'] ?: 'Desconhecido';
                $types_data['data'][] = $type['total'];
            }
            echo json_encode($types_data);
        ?>;
        
        new Chart(meusTiposCtx, {
            type: 'doughnut',
            data: {
                labels: meusTiposData.labels,
                datasets: [{
                    data: meusTiposData.data,
                    backgroundColor: ['#8b5cf6', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '<?= $user_theme == 'light' ? '#374151' : '#cbd5e1' ?>', font: { size: 10 } }
                    }
                }
            }
        });
    }
    
    // Gráfico de Meus Uploads
    const meusUploadsCtx = document.getElementById('meusUploadsChart')?.getContext('2d');
    if (meusUploadsCtx) {
        const meusUploadsData = <?php 
            $dates = [];
            $counts = [];
            foreach($meus_uploads_por_dia as $day) {
                $dates[] = date('d/m', strtotime($day['date']));
                $counts[] = $day['count'];
            }
            echo json_encode(['dates' => $dates, 'counts' => $counts]);
        ?>;
        
        new Chart(meusUploadsCtx, {
            type: 'bar',
            data: {
                labels: meusUploadsData.dates,
                datasets: [{
                    label: 'Meus Uploads',
                    data: meusUploadsData.counts,
                    backgroundColor: '#8b5cf6',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '<?= $user_theme == 'light' ? '#374151' : '#cbd5e1' ?>' },
                        grid: { color: 'rgba(255,255,255,0.1)' }
                    },
                    x: {
                        ticks: { color: '<?= $user_theme == 'light' ? '#374151' : '#cbd5e1' ?>' },
                        grid: { display: false }
                    }
                }
            }
        });
    }
};
</script>

</body>
</html>
