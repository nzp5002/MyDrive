<?php
require_once 'config.php';
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
$owner_id = $user_id; // Padrão é o próprio usuário

if ($current_folder > 0) {
    // Descobrir quem é o dono da pasta atual para listar o conteúdo corretamente
    $stmt_owner = $conn->prepare("SELECT user_id FROM folders WHERE id = ?");
    $stmt_owner->bind_param("i", $current_folder);
    $stmt_owner->execute();
    $owner_res = $stmt_owner->get_result()->fetch_assoc();
    $owner_id = $owner_res['user_id'] ?? 0;

    // Pastas dentro desta pasta
    $stmt_f = $conn->prepare("SELECT id, nome, status, 'folder' as item_type FROM folders WHERE user_id = ? AND parent_id = ? AND nome LIKE ?");
    $stmt_f->bind_param("iis", $owner_id, $current_folder, $searchTerm);
    $stmt_f->execute();
    $folders_res = $stmt_f->get_result()->fetch_all(MYSQLI_ASSOC);

    // Arquivos dentro desta pasta
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
        // Meu Drive Raiz
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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MyDrive Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/resumablejs/resumable.min.js"></script>
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .item-card { background: white; border-radius: 2rem; transition: all 0.3s ease; border: 1px solid #f1f5f9; position: relative; }
        .fab-container { position: fixed; bottom: 2rem; right: 2rem; z-index: 1000; display: flex; flex-direction: column-reverse; align-items: center; gap: 1rem; }
        .fab-main { width: 60px; height: 60px; background: #2563eb; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 25px rgba(37,99,235,0.4); cursor: pointer; transition: all 0.3s; z-index: 1001; }
        .fab-main.active { transform: rotate(45deg); background: #1e293b; }
        .fab-menu { display: none; flex-direction: column-reverse; gap: 0.8rem; align-items: center; margin-bottom: 0.5rem; }
        .fab-menu.show { display: flex; animation: slideUp 0.3s forwards; }
        .fab-item { width: 50px; height: 50px; background: white; color: #64748b; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 5px 15px rgba(0,0,0,0.1); cursor: pointer; transition: all 0.2s; position: relative; }
        .fab-item:hover { background: #2563eb; color: white; }
        .fab-label { position: absolute; right: 65px; background: #1e293b; color: white; padding: 4px 12px; border-radius: 8px; font-size: 10px; font-weight: 800; text-transform: uppercase; white-space: nowrap; pointer-events: none; opacity: 0; transition: 0.2s; }
        .fab-item:hover .fab-label { opacity: 1; transform: translateX(-5px); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="text-slate-900 overflow-x-hidden">

<div class="fab-container">
    <div class="fab-main" onclick="toggleFab()"><i class="fas fa-plus text-xl"></i></div>
    <div id="fabMenu" class="fab-menu">
        <a href="logout.php" class="fab-item logout" style="color:#ef4444"><i class="fas fa-sign-out-alt"></i><span class="fab-label">Sair</span></a>
        <?php if($aba === 'meus'): ?>
        <div class="fab-item" id="btnUpload"><i class="fas fa-file-upload"></i><span class="fab-label">Subir Arquivo</span></div>
        <div class="fab-item" onclick="criarPasta()"><i class="fas fa-folder-plus"></i><span class="fab-label">Nova Pasta</span></div>
        <div class="fab-item" onclick="abrirBuscaAmigos()"><i class="fas fa-user-plus"></i><span class="fab-label">Add Amigo</span></div>
        <div class="fab-item" onclick="mostrarMeuQR()"><i class="fas fa-qrcode"></i><span class="fab-label">Meu QR</span></div>
        <?php endif; ?>
    </div>
</div>

<div id="mobileMenu" class="fixed inset-0 bg-slate-900/60 z-[100] hidden backdrop-blur-sm">
    <div class="w-72 bg-white h-full p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-10 text-blue-600 font-black italic"><span>MYDRIVE</span><button onclick="toggleMenu()"><i class="fas fa-times text-xl text-slate-400"></i></button></div>
        <nav class="space-y-2">
            <a href="index.php?aba=meus" class="block p-4 rounded-2xl <?= $aba=='meus'?'bg-blue-600 text-white shadow-lg':'text-slate-500' ?> font-bold text-xs uppercase tracking-widest">Meu Drive</a>
            <a href="index.php?aba=recebidos" class="block p-4 rounded-2xl <?= $aba=='recebidos'?'bg-blue-600 text-white shadow-lg':'text-slate-500' ?> font-bold text-xs uppercase tracking-widest">Recebidos</a>
        </nav>
    </div>
</div>

<div class="flex h-screen overflow-hidden">
    <aside class="w-64 bg-white border-r border-slate-100 hidden md:flex flex-col p-6 shrink-0">
        <div class="mb-10 text-blue-600 font-black italic text-xl uppercase tracking-tighter">MyDrive</div>
        <nav class="space-y-2 flex-1">
            <a href="index.php?aba=meus" class="flex items-center gap-3 p-4 rounded-2xl <?= $aba=='meus'?'bg-blue-600 text-white shadow-lg':'text-slate-500' ?> font-black text-[10px] uppercase tracking-widest transition"><i class="fas fa-hdd text-lg"></i> Drive</a>
            <a href="index.php?aba=recebidos" class="flex items-center gap-3 p-4 rounded-2xl <?= $aba=='recebidos'?'bg-blue-600 text-white shadow-lg':'text-slate-500' ?> font-black text-[10px] uppercase tracking-widest transition"><i class="fas fa-share-nodes text-lg"></i> Recebidos</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
        <header class="h-20 bg-white border-b border-slate-50 flex items-center justify-between px-4 md:px-8 shrink-0">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <button onclick="toggleMenu()" class="md:hidden text-slate-400 shrink-0"><i class="fas fa-bars text-xl"></i></button>
                <form action="" method="GET" class="relative w-full max-w-[200px] md:max-w-xs">
                    <input type="hidden" name="aba" value="<?= $aba ?>">
                    <input type="hidden" name="folder" value="<?= $hash_folder ?>">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Busca..." class="w-full pl-9 pr-2 py-2.5 bg-slate-100 border-none rounded-xl text-[10px] font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                </form>
            </div>
            <a href="perfil.php" class="shrink-0"><div class="w-10 h-10 rounded-xl overflow-hidden ring-2 ring-white shadow-sm border border-slate-100"><img src="<?= $img_src ?>" class="w-full h-full object-cover"></div></a>
        </header>

        <div class="flex-1 p-4 md:p-10 overflow-y-auto">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <?php if($current_folder > 0): ?><button onclick="window.history.back()" class="text-[10px] font-black text-blue-600 uppercase mb-2 flex items-center gap-1 bg-blue-50 px-3 py-1 rounded-full"><i class="fas fa-chevron-left"></i> Voltar</button><?php endif; ?>
                    <h2 class="text-2xl font-black text-slate-800 tracking-tighter uppercase italic"><?= $aba == 'recebidos' ? 'Recebidos' : 'Meu Espaço' ?></h2>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4 pb-32">
                <?php foreach($items as $item):
                    $isFolder = ($item['item_type'] === 'folder');
                    $status = $item['status'] ?? 'private';
                    $iconStatus = ($status == 'public') ? 'fa-globe' : (($status == 'friends') ? 'fa-user-group' : 'fa-lock');
                    $colorStatus = ($status == 'public') ? 'text-emerald-500' : (($status == 'friends') ? 'text-blue-500' : 'text-slate-400');
                    $encoded_id = encodeId($item['id']);
                ?>
                    <div class="item-card group p-4">
                        <div class="absolute top-4 left-4 flex gap-2">
                            <i class="fas <?= $iconStatus ?> text-[10px] <?= $colorStatus ?>"></i>
                        </div>

                        <!-- LOGICA CORRIGIDA: Só mostra opções se for o dono (fora da aba recebidos) -->
                        <?php if($aba !== 'recebidos' && $owner_id == $user_id): ?>
                        <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition z-10">
                            <button onclick="opcoesItem('<?= $encoded_id ?>', '<?= $item['item_type'] ?>', '<?= $status ?>')" class="text-slate-300 hover:text-blue-600"><i class="fas fa-ellipsis-v"></i></button>
                        </div>
                        <?php endif; ?>

                        <div class="flex flex-col items-center py-6">
                            <?php if($isFolder): ?>
                                <i class="fas fa-folder text-5xl <?= $colorStatus ?> mb-4 opacity-80"></i>
                                <a href="index.php?folder=<?= $encoded_id ?>&aba=<?= $aba ?>" class="text-[11px] font-bold text-slate-700 w-full text-center px-2 overflow-hidden text-ellipsis whitespace-nowrap"><?= htmlspecialchars($item['nome']) ?></a>
                                
                                <?php if($aba !== 'recebidos'): ?>
                                <button onclick="partilhar('<?= $encoded_id ?>')" class="mt-4 text-[9px] font-black text-slate-400 bg-slate-50 px-3 py-2 rounded-lg hover:bg-slate-200 transition"><i class="fas fa-share-nodes"></i> Amigos</button>
                                <?php endif; ?>
                            <?php else:
                                $fileToken = gerarTokenDownload($item['id']);
                                $type = strtolower($item['tipo'] ?? '');
                                ?>
                                <div class="w-16 h-16 flex items-center justify-center mb-4">
                                    <?php if(strpos($type, 'image') !== false): ?>
                                        <div class="w-full h-full rounded-2xl overflow-hidden border border-slate-100 bg-slate-50">
                                            <img src="stream.php?file=<?= $fileToken ?>" 
                                                 class="w-full h-full object-cover" 
                                                 onerror="this.parentElement.innerHTML='<i class=\'fas fa-file-image text-4xl text-slate-200\'></i>'">
                                        </div>
                                    <?php else: ?>
                                        <i class="fas fa-file-alt text-4xl text-slate-200"></i>
                                    <?php endif; ?>
                                </div>
                                <span class="text-[11px] font-bold text-slate-700 w-full text-center px-2 overflow-hidden text-ellipsis whitespace-nowrap"><?= htmlspecialchars($item['nome']) ?></span>
                                <div class="flex gap-2 mt-4">
                                    <a href="download.php?file=<?= $fileToken ?>" class="text-[9px] font-black text-blue-600 uppercase bg-blue-50 px-3 py-2 rounded-lg hover:bg-blue-600 hover:text-white transition">Download</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<div id="uploadProgress" class="hidden fixed bottom-10 left-1/2 -translate-x-1/2 w-[80%] max-w-xs bg-white p-4 rounded-3xl shadow-2xl z-[200]">
    <div class="flex justify-between items-center mb-2"><span class="text-[9px] font-black text-slate-400 uppercase">Enviando...</span><span id="progPerc" class="text-xs font-black text-blue-600">0%</span></div>
    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden"><div id="progBar" class="h-full bg-blue-600 w-0 transition-all"></div></div>
</div>

<script>
const MEUS_AMIGOS_IDS = <?= json_encode($js_amigos_ids) ?>;

function toggleMenu() { document.getElementById('mobileMenu').classList.toggle('hidden'); }
function toggleFab() { document.getElementById('fabMenu').classList.toggle('show'); document.querySelector('.fab-main').classList.toggle('active'); }

function copiarLink(id) {
    const url = `<?= $base_url ?>/view_folder.php?id=${id}`;
    navigator.clipboard.writeText(url).then(() => {
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Link Copiado!', showConfirmButton: false, timer: 1500 });
    });
}

async function criarPasta() {
    const { value: f } = await Swal.fire({
        title: 'NOVA PASTA',
        html: `<input id="f-n" class="swal2-input" placeholder="Nome"><select id="f-s" class="swal2-input"><option value="private">Privada</option><option value="friends">Amigos</option><option value="public">Pública</option></select>`,
        preConfirm: () => ({ n: document.getElementById('f-n').value, s: document.getElementById('f-s').value })
    });
    if(f && f.n) {
        const p = new URLSearchParams({ action: 'create', nome: f.n, status: f.s, parent_id: '<?= $current_folder ?>' });
        const res = await fetch('api_folders.php', { method: 'POST', body: p }).then(r => r.json());
        if(res.ok) {
            if(f.s === 'friends' && res.id_encoded) await dispararCompartilhamentoSilencioso(res.id_encoded);
            location.reload();
        }
    }
}

function opcoesItem(id, type, status) {
    let html = '';
    if(type === 'folder' && status === 'public') {
        html = `<button onclick="copiarLink('${id}')" class="swal2-confirm swal2-styled" style="background:#10b981; margin-bottom:10px; width:80%">COPIAR LINK PÚBLICO</button>`;
    }

    Swal.fire({
        title: 'OPÇÕES',
        html: html,
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'RENOMEAR',
        denyButtonText: 'EXCLUIR',
        cancelButtonText: 'FECHAR',
        confirmButtonColor: '#2563eb',
        denyButtonColor: '#ef4444'
    }).then(r => {
        if(r.isConfirmed) {
            // Renomear logic (abre outro prompt ou envia direto)
            Swal.fire({ title: 'Novo Nome', input: 'text', showCancelButton: true }).then(res => {
                if(res.value) window.location.href = `rename.php?id=${id}&type=${type}&newname=${encodeURIComponent(res.value)}`;
            });
        } else if(r.isDenied) {
            Swal.fire({ title: 'APAGAR?', icon: 'warning', showCancelButton: true }).then(res => {
                if(res.isConfirmed) window.location.href = `delete.php?id=${id}&type=${type}`;
            });
        }
    });
}

async function dispararCompartilhamentoSilencioso(itemId) {
    if (MEUS_AMIGOS_IDS.length === 0) return;
    return Promise.all(MEUS_AMIGOS_IDS.map(paraId => fetch(`compartilhar.php?file_id=${itemId}&para_id=${paraId}`)));
}

function partilhar(id) {
    if (MEUS_AMIGOS_IDS.length === 0) return Swal.fire('AVISO', 'Você não tem amigos.', 'info');
    Swal.fire({
        title: 'PARTILHAR?',
        text: `Enviar para todos os seus amigos?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim!',
        showLoaderOnConfirm: true,
        preConfirm: () => dispararCompartilhamentoSilencioso(id)
    }).then(r => r.isConfirmed && Swal.fire('OK', 'Sucesso!', 'success'));
}

async function abrirBuscaAmigos() {
    const { value: u } = await Swal.fire({ title: 'ADD AMIGO', input: 'text', inputPlaceholder: '@username', showCancelButton: true });
    if (u) fetch(`amizade.php?username=${encodeURIComponent(u.replace('@', ''))}`).then(r => r.json()).then(d => d.ok ? Swal.fire('OK', 'Enviado', 'success') : Swal.fire('ERRO', d.msg, 'error'));
}

function mostrarMeuQR() { 
    Swal.fire({ title: 'MEU QR', imageUrl: `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=mydrive_user:<?= urlencode($user_data['username']) ?>`, showConfirmButton: false }); 
}

<?php if($aba === 'meus'): ?>
var rs = new Resumable({ target: 'upload.php', query: { folder_id: '<?= $current_folder ?>' } });
rs.assignBrowse(document.getElementById('btnUpload'));
rs.on('fileAdded', () => { document.getElementById('uploadProgress').classList.remove('hidden'); rs.upload(); });
rs.on('fileProgress', (f) => {
    let p = Math.floor(f.progress() * 100);
    document.getElementById('progBar').style.width = p + "%";
    document.getElementById('progPerc').innerText = p + "%";
});
rs.on('fileSuccess', () => location.reload());
<?php endif; ?>
</script>
</body>
</html>

