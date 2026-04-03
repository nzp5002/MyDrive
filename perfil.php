<?php
require_once 'config.php';
checkLogin();

if (session_status() === PHP_SESSION_NONE) session_start();

$user_id = $_SESSION['user_id'];
$msg = "";
$invite_link = "";
	
// --- FUNÇÕES DE APOIO ---
function gerarCor($string) {
    return substr(md5($string), 0, 6);
}

function gerarAvatar($username, $random = false) {
    $seed = preg_replace('/[^a-zA-Z0-9]/', '', $username);
    if ($random) $seed .= rand(1000, 9999);
    $cor = gerarCor($seed);
    return "https://api.dicebear.com/9.x/dylan/svg?seed={$seed}&backgroundColor={$cor}";
}

// ============================
// LÓGICA DE PROCESSAMENTO (POST)
// ============================

// 1. APROVAÇÃO / REJEIÇÃO DE MEMBROS (CORRIGIDO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_decisao'])) {
    $membro_id = (int)$_POST['membro_id'];
    $decisao = $_POST['acao_decisao']; 
    $dias = (int)($_POST['dias_acesso'] ?? 30);
    $data_expira = date('Y-m-d H:i:s', strtotime("+$dias days"));

    if ($decisao === 'aprovar') {
        $up = $conn->prepare("UPDATE users SET status = 'ativo', expira_em = ? WHERE id = ?");
        $up->bind_param("si", $data_expira, $membro_id);
        if($up->execute()) {
            header("Location: perfil.php?msg=Aprovado com sucesso");
            exit;
        }
    } else if ($decisao === 'rejeitar') {
        $del = $conn->prepare("DELETE FROM users WHERE id = ?");
        $del->bind_param("i", $membro_id);
        if($del->execute()) {
            header("Location: perfil.php?msg=Membro rejeitado");
            exit;
        }
    }
}

// 2. Avatar Aleatório
if (isset($_POST['random_avatar'])) {
    $avatar = gerarAvatar($_SESSION['user_nome'], true);
    $stmt = $conn->prepare("UPDATE users SET foto = ? WHERE id = ?");
    $stmt->bind_param("si", $avatar, $user_id);
    $stmt->execute();
    header("Location: perfil.php?status=updated");
    exit;
}

// 3. Responder Amizade
if (isset($_POST['responder_amigo'])) {
    $amizade_id = (int)$_POST['amizade_id'];
    $acao = $_POST['status'] ?? '';

    if ($acao === 'aceitar') {
        $conn->query("UPDATE amizades SET status = 'aceito' WHERE id = $amizade_id");
        $dados = $conn->query("SELECT usuario_id, amigo_id FROM amizades WHERE id = $amizade_id")->fetch_assoc();
        if ($dados) {
            $uid = $dados['usuario_id']; $aid = $dados['amigo_id'];
            $conn->query("INSERT IGNORE INTO amizades (usuario_id, amigo_id, status) VALUES ($aid, $uid, 'aceito')");
            header("Location: perfil.php?msg=Amizade aceita");
            exit;
        }
    } elseif ($acao === 'recusar') {
        $conn->query("DELETE FROM amizades WHERE id = $amizade_id");
        header("Location: perfil.php?msg=Amizade recusada");
        exit;
    }
}

// 4. Atualizar Perfil
if (isset($_POST['update_profile'])) {
    $novo_nome = $_POST['nome'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nome_foto = "user_" . $user_id . "_" . time() . "." . $ext;
        if (!is_dir('perfil/')) mkdir('perfil/', 0755, true);
        if (move_uploaded_file($_FILES['foto']['tmp_name'], "perfil/" . $nome_foto)) {
            $upF = $conn->prepare("UPDATE users SET foto = ? WHERE id = ?");
            $upF->bind_param("si", $nome_foto, $user_id);
            $upF->execute();
        }
    }
    $stmt = $conn->prepare("UPDATE users SET nome = ? WHERE id = ?");
    $stmt->bind_param("si", $novo_nome, $user_id);
    $stmt->execute();
    $_SESSION['user_nome'] = $novo_nome;
    header("Location: perfil.php?msg=Perfil Atualizado");
    exit;
}

// 5. Gerar Convite
if (isset($_POST['gerar_convite'])) {
    $token = bin2hex(random_bytes(8));
    $validade = (int)$_POST['dias_validade'];
    $data_expira = date('Y-m-d H:i:s', strtotime("+$validade days"));
    $stmt_inv = $conn->prepare("INSERT INTO convites (token, criado_por, expira_em) VALUES (?, ?, ?)");
    $stmt_inv->bind_param("sis", $token, $user_id, $data_expira);
    if ($stmt_inv->execute()) {
        $invite_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/registro.php?token=" . $token;
    }
}

// Pegar mensagens via URL caso existam
if(isset($_GET['msg'])) $msg = htmlspecialchars($_GET['msg']);

// --- BUSCAS ---
$user_data = $conn->query("SELECT nome, username, foto FROM users WHERE id = $user_id")->fetch_assoc();
$pedidos_amizade = $conn->query("SELECT a.id, u.nome, u.username, u.foto FROM amizades a JOIN users u ON a.usuario_id = u.id WHERE a.amigo_id = $user_id AND a.status = 'pendente'");
$amigos = $conn->query("SELECT u.id, u.nome, u.username, u.foto FROM amizades a JOIN users u ON (u.id = a.amigo_id AND a.usuario_id = $user_id) OR (u.id = a.usuario_id AND a.amigo_id = $user_id) WHERE a.status = 'aceito' GROUP BY u.id");

$is_admin = ($user_id == 1); 
$pend_q = $is_admin ? "SELECT id, nome, username FROM users WHERE status = 'pendente'" : "SELECT id, nome, username FROM users WHERE status = 'pendente' AND indicado_por = $user_id";
$lista_pendentes = $conn->query($pend_q);

if (isset($_POST['link_discord'])) {
    $discord_id = $_POST['discord_id'];

    $stmt = $conn->prepare("
        INSERT INTO discord_users (discord_id, user_id)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE user_id = ?
    ");

    $stmt->bind_param("sii", $discord_id, $user_id, $user_id);
    $stmt->execute();

    header("Location: perfil.php?msg=Discord vinculado");
    exit;
}


?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - MyDrive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-pv+KZkCqTt...==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.2s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        .active-tab { border-bottom: 3px solid #2563eb; color: #2563eb; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen font-sans text-slate-900">

    <div class="max-w-2xl mx-auto py-10 px-4">
        
        <div class="flex items-center justify-between mb-8">
            <a href="index.php" class="p-3 bg-white rounded-2xl shadow-sm hover:bg-slate-100 transition border border-slate-200">
                <i class="fas fa-arrow-left text-slate-600"></i>
            </a>
            <h1 class="text-xl font-black italic uppercase tracking-tighter">Configurações</h1>
            <div class="w-10"></div>
        </div>

        <?php if($msg): ?>
            <div id="alertMsg" class="mb-6 bg-blue-600 text-white p-4 rounded-2xl text-center font-bold shadow-lg animate-pulse">
                <?= $msg ?>
            </div>
            <script>setTimeout(() => { document.getElementById('alertMsg').style.display='none'; }, 3000);</script>
        <?php endif; ?>

        <div class="bg-white rounded-[2.5rem] shadow-sm p-8 border border-slate-200 mb-6 text-center">
            <div class="relative w-32 h-32 mx-auto mb-4 group">
                <?php 
                    $foto_final = $user_data['foto'] ?: gerarAvatar($user_data['username']);
                    $path = (filter_var($foto_final, FILTER_VALIDATE_URL)) ? $foto_final : "perfil/".$foto_final;
                ?>
                <img id="preview" src="<?= $path ?>" class="w-full h-full rounded-full object-cover border-4 border-blue-500 shadow-xl bg-slate-100">
                <form method="POST" enctype="multipart/form-data" id="formFoto">
                    <label class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center text-white opacity-0 group-hover:opacity-100 cursor-pointer transition-all">
                        <i class="fas fa-camera text-xl"></i>
                        <input type="file" name="foto" class="hidden" accept="image/*" onchange="document.getElementById('formFoto').submit()">
                    </label>
                    <input type="hidden" name="update_profile" value="1">
                    <input type="hidden" name="nome" value="<?= htmlspecialchars($user_data['nome']) ?>">
                </form>
            </div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight"><?= htmlspecialchars($user_data['nome']) ?></h2>
            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">@<?= $user_data['username'] ?></p>
            
            <form method="POST" class="mt-4">
                <button name="random_avatar" class="text-[10px] font-black uppercase text-purple-600 bg-purple-50 px-4 py-2 rounded-full hover:bg-purple-100 transition">
                    <i class="fas fa-dice mr-1"></i> Avatar Aleatório
                </button>
            </form>
        </div>

        <div class="flex border-b border-slate-200 mb-6 px-4 overflow-x-auto whitespace-nowrap gap-6 no-scrollbar">
            <button onclick="openTab(event, 'dados')" class="tab-btn py-3 font-black text-[10px] uppercase tracking-widest active-tab">Dados</button>
            <button onclick="openTab(event, 'social')" class="tab-btn py-3 font-black text-[10px] uppercase tracking-widest flex items-center gap-2">Social <?php if($pedidos_amizade->num_rows > 0): ?><span class="bg-red-500 w-2 h-2 rounded-full"></span><?php endif; ?></button>
            <button onclick="openTab(event, 'admin')" class="tab-btn py-3 font-black text-[10px] uppercase tracking-widest">Painel</button>
            <button onclick="openTab(event, 'social')" class="tab-btn py-3 font-black text-[10px] uppercase tracking-widest flex items-center gap-2">
            Social 
            <?php if($pedidos_amizade->num_rows > 0): ?>
            <span class="bg-red-500 w-2 h-2 rounded-full"></span>
            <?php endif; ?>
            </button> 
            </div>

        <div id="dados" class="tab-content active space-y-4">
            <div class="bg-white rounded-[2rem] p-6 border border-slate-200 shadow-sm">
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase ml-2 tracking-widest">Nome Completo</label>
                        <input type="text" name="nome" required value="<?= htmlspecialchars($user_data['nome']) ?>" class="w-full p-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700 shadow-inner mt-1">
                    </div>
                    <button type="submit" name="update_profile" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-blue-600 transition shadow-lg">Salvar Perfil</button>
                </form>
            </div>
        </div>


        <div id="social" class="tab-content space-y-6">
            <div class="bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm">
                <div class="p-5 bg-slate-50 font-black text-[10px] uppercase border-b">Pedidos Pendentes</div>
                <div class="p-6 space-y-4">
                    <?php if($pedidos_amizade->num_rows == 0): ?>
                        <p class="text-center text-slate-300 text-[9px] font-bold py-4 uppercase">Sem pedidos</p>
                    <?php else: ?>
                        <?php while($p = $pedidos_amizade->fetch_assoc()): ?>
                        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-2xl border border-slate-100">
                            <div class="flex items-center gap-3">
                                <img src="<?= $p['foto'] ?: gerarAvatar($p['username']) ?>" class="w-10 h-10 rounded-xl object-cover">
                                <p class="text-xs font-black">@<?= $p['username'] ?></p>
                            </div>
                            <form method="POST" class="flex gap-1">
                                <input type="hidden" name="amizade_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="responder_amigo" value="1">
                                <button name="status" value="aceitar" class="w-8 h-8 bg-blue-600 text-white rounded-lg"><i class="fas fa-check"></i></button>
                                <button name="status" value="recusar" class="w-8 h-8 bg-slate-200 text-slate-500 rounded-lg"><i class="fas fa-times"></i></button>
                            </form>
                             <div class="flex border-b border-slate-200 mb-6 px-4 overflow-x-auto whitespace-nowrap gap-6 no-scrollbar">
    <button onclick="openTab(event, 'dados')" class="tab-btn py-3 font-black text-[10px] uppercase tracking-widest active-tab">Dados</button>

    <button onclick="openTab(event, 'social')" class="tab-btn py-3 font-black text-[10px] uppercase tracking-widest flex items-center gap-2">
        Social 
        <?php if($pedidos_amizade->num_rows > 0): ?>
            <span class="bg-red-500 w-2 h-2 rounded-full"></span>
        <?php endif; ?>
    </button>

    <button onclick="openTab(event, 'admin')" class="tab-btn py-3 font-black text-[10px] uppercase tracking-widest">Painel</button>
</div>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <?php while($am = $amigos->fetch_assoc()): 
                    $f_amigo = $am['foto'] ?: gerarAvatar($am['username']);
                    $path_amigo = filter_var($f_amigo, FILTER_VALIDATE_URL) ? $f_amigo : "perfil/".$f_amigo;
                ?>
                <div class="bg-white p-4 rounded-3xl border border-slate-200 flex flex-col items-center shadow-sm">
                    <img src="<?= $path_amigo ?>" class="w-14 h-14 rounded-2xl object-cover mb-2 border border-slate-100">
                    <p class="text-[10px] font-black text-slate-800 truncate w-full text-center"><?= htmlspecialchars($am['nome']) ?></p>
                    <p class="text-[9px] text-blue-500 font-bold">@<?= $am['username'] ?></p>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div id="admin" class="tab-content space-y-6">
            <div class="bg-slate-900 rounded-[2rem] p-6 text-white shadow-2xl relative overflow-hidden">
                <h3 class="font-black text-xs uppercase tracking-widest mb-4 flex items-center gap-2"><i class="fas fa-key text-blue-400"></i> Gerar Convite</h3>
                <form method="POST" class="flex gap-2">
                    <select name="dias_validade" class="flex-1 bg-white/10 border-none rounded-xl p-3 text-[10px] font-bold outline-none">
                        <option value="1">24 Horas</option>
                        <option value="7">7 Dias</option>
                        <option value="30">30 Dias</option>
                    </select>
                    <button name="gerar_convite" class="bg-blue-600 px-6 py-3 rounded-xl font-black text-[10px] uppercase shadow-lg hover:bg-blue-500 transition">Gerar</button>
                </form>
                <?php if($invite_link): ?>
                    <div class="mt-4 p-4 bg-white/5 border border-white/10 rounded-xl text-[9px] font-mono break-all text-blue-300 select-all"><?= $invite_link ?></div>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm">
                <div class="p-5 bg-slate-50 font-black text-[10px] uppercase border-b flex items-center gap-2">
                    <i class="fas fa-hourglass-half text-orange-500"></i> Fila de Triagem
                </div>
                <div class="p-6 space-y-4">
                    <?php if($lista_pendentes->num_rows == 0): ?>
                        <p class="text-center text-slate-300 text-[9px] font-bold py-4 uppercase italic">Vazio</p>
                    <?php else: ?>
                        <?php while($p = $lista_pendentes->fetch_assoc()): ?>
                        <div class="flex items-center justify-between gap-4 p-3 bg-slate-50 rounded-2xl border border-slate-100">
                            <div>
                                <p class="text-xs font-black">@<?= $p['username'] ?></p>
                                <a href="ver_fotos.php?uid=<?= $p['id'] ?>" class="text-[8px] text-blue-600 font-bold uppercase underline">Ver Fotos</a>
                            </div>
                            <form method="POST" class="flex items-center gap-2">
                                <input type="hidden" name="membro_id" value="<?= $p['id'] ?>">
                                <select name="dias_acesso" class="bg-white text-[9px] p-1 rounded border border-slate-200 font-black">
                                    <option value="7">7d</option>
                                    <option value="30" selected>30d</option>
                                    <option value="365">1y</option>
                                </select>
                                <button type="submit" name="acao_decisao" value="aprovar" class="bg-emerald-500 text-white px-3 py-2 rounded-lg text-[10px] font-black uppercase hover:bg-emerald-600 transition">Aprovar</button>
                                <button type="submit" name="acao_decisao" value="rejeitar" class="bg-red-500 text-white px-2 py-2 rounded-lg text-[10px] font-black uppercase hover:bg-red-600 transition"><i class="fas fa-times"></i></button>
                            </form>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
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
        }
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active-tab");
        // Salva a tab atual na memória para não resetar ao atualizar
        localStorage.setItem('activeProfileTab', tabName);
    }

    // Ao carregar a página, volta para a última tab aberta
    window.onload = () => {
        const savedTab = localStorage.getItem('activeProfileTab') || 'dados';
        const button = [...document.querySelectorAll('.tab-btn')].find(b => b.innerText.toLowerCase().includes(savedTab.substring(0,3)));
        if(button) button.click();
    };
    </script>




</script>
</body>
</html>
