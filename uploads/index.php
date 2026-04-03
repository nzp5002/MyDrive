<?php 
require_once 'config.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica login (ajuste conforme o nome da sua função no config.php)
checkLogin();

$user_id = $_SESSION['user_id'];
$user_nome = $_SESSION['user_nome'];

// 1. DADOS DO USUÁRIO (Username e Foto para o Header)
$stmt_user = $conn->prepare("SELECT foto, username, nome FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();

// 2. LÓGICA DE BUSCA E ABAS
$search = $_GET['search'] ?? '';
$searchTerm = "%$search%";
$aba = $_GET['aba'] ?? 'meus';

if ($aba === 'recebidos') {
    $query = "SELECT f.*, u.nome as dono FROM files f 
              INNER JOIN compartilhamentos c ON f.id = c.file_id 
              INNER JOIN users u ON f.user_id = u.id
              WHERE c.para_user_id = ? AND f.nome LIKE ? 
              ORDER BY f.uploaded_at DESC";
} else {
    $query = "SELECT * FROM files WHERE user_id = ? AND nome LIKE ? ORDER BY uploaded_at DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $searchTerm);
$stmt->execute();
$files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. BUSCAR LISTA DE AMIGOS (Para o modal de compartilhar)
$amigos_query = $conn->prepare("SELECT u.id, u.nome FROM amizades a JOIN users u ON (u.id = a.amigo_id AND a.usuario_id = ?) OR (u.id = a.usuario_id AND a.amigo_id = ?) WHERE a.status = 'aceito' GROUP BY u.id");
$amigos_query->bind_param("ii", $user_id, $user_id);
$amigos_query->execute();
$meus_amigos = $amigos_query->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. FUNÇÃO DE TOKEN DE DOWNLOAD
function gerarTokenDownload($file_id) {
    $secret_key = getenv("SKEY");
    $method = "aes-128-cbc";
    $iv = substr(hash('sha256', $secret_key), 0, 16);
    $expira = time() + 3600; 
    $dados = $file_id . "|" . $expira;
    $encrypted = openssl_encrypt($dados, $method, $secret_key, 0, $iv);
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($encrypted));
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyDrive - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .truncate-custom { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .bg-glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px); }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-900">

<div id="mobileMenu" class="fixed inset-0 bg-slate-900/60 z-50 hidden backdrop-blur-sm transition-all">
    <div class="w-72 bg-white h-full p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-10">
            <div class="flex items-center gap-2 text-blue-600 font-black text-xl italic"><i class="fas fa-cloud"></i> MYDRIVE</div>
            <button onclick="toggleMenu()"><i class="fas fa-times text-2xl text-slate-400"></i></button>
        </div>
        <nav class="space-y-2">
            <a href="index.php?aba=meus" class="flex items-center gap-4 p-4 <?= $aba=='meus'?'bg-blue-600 text-white shadow-lg shadow-blue-200':'text-slate-500' ?> rounded-2xl font-black uppercase text-[10px] tracking-widest transition">
                <i class="fas fa-hdd text-lg"></i> Meus Arquivos
            </a>
            <a href="index.php?aba=recebidos" class="flex items-center gap-4 p-4 <?= $aba=='recebidos'?'bg-blue-600 text-white shadow-lg shadow-blue-200':'text-slate-500' ?> rounded-2xl font-black uppercase text-[10px] tracking-widest transition">
                <i class="fas fa-user-friends text-lg"></i> Recebidos
            </a>

<a href="perfil" class="flex items-center gap-4 p-4 text-slate-500 hover:bg-slate-100 rounded-2xl font-black uppercase text-[10px] tracking-widest transition">
                <i class="fas fa-user-cog text-lg"></i>Configurações 

<a href="shits.php" class="flex items-center justify-between p-4 bg-gradient-to-r from-amber-900/10 to-transparent border-l-4 border-amber-800 text-amber-900 rounded-r-2xl font-black uppercase text-[10px] tracking-widest transition group hover:from-amber-900/20">
    <div class="flex items-center gap-3">
        <i class="fas fa-poop text-base"></i> Ver Shits
    </div>
    <span class="bg-amber-800 text-white text-[8px] px-2 py-0.5 rounded-full animate-pulse">Hot</span>

            </a>
            <hr class="my-4 border-slate-100">
            <a href="logout.php" class="flex items-center gap-4 p-4 text-red-500 font-black uppercase text-[10px] tracking-widest transition">
                <i class="fas fa-sign-out-alt text-lg"></i> Sair
            </a>


        </nav>
    </div>
</div>

<div class="flex h-screen overflow-hidden">
    <aside class="w-64 bg-white border-r border-slate-200 hidden md:flex flex-col">
        <div class="p-8 flex items-center gap-3">
            <div class="bg-blue-600 w-10 h-10 rounded-xl text-white flex items-center justify-center shadow-lg shadow-blue-200"><i class="fas fa-cloud text-xl"></i></div>
            <h1 class="font-black text-xl tracking-tighter italic uppercase">MyDrive</h1>
        </div>
        <nav class="flex-1 px-4 space-y-2">
            <a href="index?aba=meus" class="flex items-center gap-3 p-4 <?= $aba=='meus'?'bg-blue-600 text-white shadow-lg shadow-blue-200':'text-slate-400 hover:text-slate-600' ?> rounded-2xl font-black uppercase text-[10px] tracking-widest transition">
                <i class="fas fa-folder-open text-base"></i> Arquivos
            </a>
            <a href="index?aba=recebidos" class="flex items-center gap-3 p-4 <?= $aba=='recebidos'?'bg-blue-600 text-white shadow-lg shadow-blue-200':'text-slate-400 hover:text-slate-600' ?> rounded-2xl font-black uppercase text-[10px] tracking-widest transition">
                <i class="fas fa-share-nodes text-base"></i> Recebidos
            </a>
            <a href="perfil" class="flex items-center gap-3 p-4 text-slate-400 hover:text-slate-600 rounded-2xl font-black uppercase text-[10px] tracking-widest transition">
                <i class="fas fa-gear text-base"></i> Perfil
            </a>

</a>
            <a href="perfil" class="flex items-center gap-3 p-4 text-slate-400 hover:text-slate-600 rounded-2xl font-black uppercase text-[10px] tracking-widest transition">
                <i class="fas fa-gear text-base"></i>Melhoras
            </a>

        </nav>
        <div class="p-6">
            <a href="logout" class="flex items-center justify-center gap-2 p-4 bg-slate-50 text-red-400 rounded-2xl font-black uppercase text-[9px] tracking-[0.2em] hover:bg-red-50 hover:text-red-600 transition">
                Sair do App
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-100 flex items-center justify-between px-4 md:px-8 sticky top-0 z-30">
            <div class="flex items-center gap-4 flex-1">
                <button onclick="toggleMenu()" class="md:hidden text-slate-400 p-2 hover:bg-slate-50 rounded-xl transition"><i class="fas fa-bars text-xl"></i></button>
                <form action="" method="GET" class="relative w-full max-w-xs">
                    <input type="hidden" name="aba" value="<?= $aba ?>">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-sm"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Procurar ficheiros..." 
                        class="w-full pl-12 pr-4 py-3 bg-slate-100 border-none rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition shadow-inner">
                </form>
            </div>

            <div class="flex items-center gap-3 ml-4">
                <button onclick="abrirBuscaAmigos()" class="w-10 h-10 bg-slate-50 text-slate-400 rounded-2xl hover:text-blue-600 hover:bg-blue-50 transition"><i class="fas fa-user-plus"></i></button>
                
                <button onclick="document.getElementById('fileInput').click()" class="bg-blue-600 text-white px-5 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                    <i class="fas fa-plus"></i> <span class="hidden sm:inline">Upload</span>
                </button>
                <form id="uploadForm" action="upload" method="POST" enctype="multipart/form-data" class="hidden">
                    <input type="file" name="file" id="fileInput" onchange="this.form.submit()">
                </form>
                
                <a href="perfil" class="flex-shrink-0 ml-2 group">
                    <div class="w-11 h-11 rounded-2xl border-2 border-white shadow-md overflow-hidden bg-slate-100 ring-1 ring-slate-100 group-hover:ring-blue-400 transition-all">
                        <?php
                        $foto_db = $user_data['foto'] ?? '';
                        if (filter_var($foto_db, FILTER_VALIDATE_URL)) {
                            $img_src = $foto_db;
                        } elseif (!empty($foto_db) && file_exists("uploads/perfil/" . $foto_db)) {
                            $img_src = "uploads/perfil/" . $foto_db;
                        } else {
                            $seed = preg_replace('/[^a-zA-Z0-9]/', '', $user_data['username'] ?? 'default');
                            $cor = substr(md5($seed), 0, 6);
                            $img_src = "https://api.dicebear.com/9.x/dylan/svg?seed={$seed}&backgroundColor={$cor}";
                        }
                        ?>
                        <img src="<?= $img_src ?>" class="w-full h-full object-cover" alt="Perfil">
                    </div>
                </a>
            </div>
        </header>
<div id="progress"></div>
        <div class="flex-1 p-4 md:p-10 overflow-y-auto bg-slate-50/50">
            <div class="flex items-end justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 italic uppercase tracking-tighter leading-none">
                        <?= $aba == 'recebidos' ? 'Partilhados' : 'O meu Drive' ?>
                    </h2>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-2"><?= count($files) ?> Itens encontrados</p>
                </div>
                <button onclick="mostrarMeuQR()" class="p-3 bg-white border border-slate-200 rounded-xl text-slate-400 hover:text-blue-600 transition shadow-sm"><i class="fas fa-qrcode"></i></button>
            </div>

            <?php if(empty($files)): ?>
                <div class="flex flex-col items-center justify-center py-32 text-slate-200">
                    <div class="w-24 h-24 bg-white rounded-[2rem] flex items-center justify-center shadow-sm mb-6 border border-slate-100">
                        <i class="fas fa-folder-open text-4xl"></i>
                    </div>
                    <p class="font-black italic uppercase text-[10px] tracking-[0.3em]">Nada por aqui ainda</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6">
                    <?php foreach($files as $file): ?>
                    <div class="group bg-white p-5 rounded-[2.5rem] border border-slate-200 shadow-sm hover:shadow-2xl hover:shadow-blue-100 transition-all relative">
                        
                        <div class="absolute top-4 right-4 flex gap-1 opacity-0 group-hover:opacity-100 transition-all z-10">
                            <?php if($aba === 'meus'): ?>
                            <button onclick="abrirCompartilhar(<?= $file['id'] ?>)" class="w-8 h-8 bg-white border border-slate-100 text-blue-600 rounded-xl shadow-sm hover:bg-blue-600 hover:text-white transition">
                                <i class="fas fa-share-nodes text-[10px]"></i>
                            </button>
                            <button onclick="confirmDelete(<?= $file['id'] ?>)" class="w-8 h-8 bg-white border border-slate-100 text-red-500 rounded-xl shadow-sm hover:bg-red-600 hover:text-white transition">
                                <i class="fas fa-trash-can text-[10px]"></i>
                            </button>
                            <?php endif; ?>
                        </div>

                        <div class="flex flex-col items-center py-6">
                            <?php 
                            $icon = "fa-file text-slate-300";
                            $type = strtolower($file['tipo'] ?? '');
                            if(strpos($type, 'image') !== false) $icon = "fa-file-image text-blue-500";
                            elseif(strpos($type, 'pdf') !== false) $icon = "fa-file-pdf text-red-500";
                            elseif(strpos($type, 'video') !== false) $icon = "fa-file-video text-purple-500";
                            elseif(strpos($type, 'audio') !== false) $icon = "fa-file-audio text-emerald-500";
                            ?>
                            <div class="w-20 h-20 bg-slate-50 rounded-[2rem] flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-500 border border-slate-100">
                                <i class="fas <?= $icon ?> text-4xl"></i>
                            </div>
                            
                            <p class="text-[11px] font-black text-slate-700 truncate-custom w-full text-center px-2">
                                <?= htmlspecialchars($file['nome']) ?>
                            </p>
                            
                            <?php if(isset($file['dono'])): ?>
                                <span class="mt-2 text-[8px] font-black text-blue-500 bg-blue-50 px-3 py-1 rounded-full uppercase italic">De: <?= htmlspecialchars($file['dono']) ?></span>
                            <?php endif; ?>
                        </div>

                        <a href="download?file=<?= gerarTokenDownload($file['id']) ?>" 
                           class="block text-center py-4 text-[9px] font-black text-slate-400 bg-slate-50 rounded-2xl group-hover:bg-blue-600 group-hover:text-white transition-all uppercase tracking-[0.2em]">
                            Download
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>


    <button onclick="abrirModalShit()" class="fixed bottom-8 right-8 w-14 h-14 bg-amber-800 text-white rounded-full shadow-2xl shadow-amber-900/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-50 group">
    <i class="fas fa-poop text-xl"></i>
    <span class="absolute right-16 bg-slate-900 text-white text-[10px] px-3 py-1 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity  whitespace-nowrap">Postar um Shit</span>
</button>






<script>
function toggleMenu() { document.getElementById('mobileMenu').classList.toggle('hidden'); }

function confirmDelete(id) {
    Swal.fire({
        title: 'ELIMINAR?', 
        text: "Esta ação não pode ser desfeita!", 
        icon: 'warning',
        background: '#fff',
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'APAGAR AGORA',
        cancelButtonText: 'CANCELAR',
        showCancelButton: true,
        customClass: { confirmButton: 'rounded-xl font-black uppercase text-xs px-6 py-3', cancelButton: 'rounded-xl font-black uppercase text-xs px-6 py-3' }
    }).then((result) => { if (result.isConfirmed) window.location.href = 'delete?id=' + id; })
}

function mostrarMeuQR() {
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=mydrive_user:<?= urlencode($user_data['username']) ?>`;
    Swal.fire({ 
        title: 'ADICIONAR AMIGO', 
        imageUrl: qrUrl, 
        imageWidth: 200, imageHeight: 200, 
        text: 'Mostre este código para alguém adicionar você.',
        showConfirmButton: false 
    });
}

async function abrirBuscaAmigos() {
    const { value: u } = await Swal.fire({
        title: 'PEDIDO DE AMIZADE', 
        input: 'text', 
        inputPlaceholder: 'Introduza o @username...',
        confirmButtonText: 'ENVIAR',
        showCancelButton: true
    });

    if (u) {
        let username = u.replace('@', '');
        fetch(`amizade.php?username=${encodeURIComponent(username)}`)
            .then(r => r.json())
            .then(data => {
                if(data.ok) Swal.fire('SUCESSO!', 'Pedido enviado!', 'success');
                else Swal.fire('ERRO', data.msg, 'error');
            });
    }
}

function abrirCompartilhar(fileId) {
    const amigos = { <?php foreach($meus_amigos as $a) echo "'{$a['id']}': '" . addslashes($a['nome']) . "',"; ?> };
    
    if(Object.keys(amigos).length === 0) {
        return Swal.fire('OPS!', 'Precisas de amigos aceites primeiro.', 'info');
    }

    Swal.fire({
        title: 'PARTILHAR', 
        input: 'select', 
        inputOptions: amigos,
        inputPlaceholder: 'Escolher amigo...', 
        showCancelButton: true, 
        confirmButtonText: 'PARTILHAR',
        preConfirm: (paraId) => { 
            return fetch(`compartilhar.php?file_id=${fileId}&para_id=${paraId}`)
                .then(r => r.json())
                .then(d => { if(!d.success) throw new Error(d.message); return d; })
                .catch(e => Swal.showValidationMessage(`Erro: ${e}`))
        }
    }).then(r => { if(r.isConfirmed) Swal.fire('PARTILHADO!', '', 'success') });
}
</script>
<script>
async function abrirModalShit() {
    const { value: formValues } = await Swal.fire({
        title: '<span class="italic uppercase font-black text-slate-800">Novo Shit</span>',
        html:
            '<div class="flex flex-col gap-4 p-2 text-left">' +
            '<label class="text-[10px] font-black uppercase text-slate-400">Título do vídeo</label>' +
            '<input id="shit-titulo" class="w-full p-4 bg-slate-100 border-none rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-amber-600 transition">' +
            '<label class="text-[10px] font-black uppercase text-slate-400">Arquivo de Vídeo (MP4)</label>' +
            '<div class="relative w-full h-32 border-2 border-dashed border-slate-200 rounded-2xl flex items-center justify-center bg-slate-50 hover:bg-slate-100 transition cursor-pointer" onclick="document.getElementById(\'shit-file\').click()">' +
            '  <i class="fas fa-video text-slate-300 text-2xl"></i>' +
            '  <input id="shit-file" type="file" accept="video/*" class="hidden" onchange="window.shitFileName.innerText = this.files[0].name">' +
            '</div>' +
            '<p id="shitFileName" class="text-[10px] text-center font-bold text-amber-700"></p>' +
            '</div>',
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'PUBLICAR AGORA',
        confirmButtonColor: '#78350f', // Amber-900
        preConfirm: () => {
            const titulo = document.getElementById('shit-titulo').value;
            const file = document.getElementById('shit-file').files[0];
            
            if (!titulo || !file) {
                Swal.showValidationMessage('Preencha todos os campos!');
                return false;
            }
            return { titulo, file };
        }
    });

    if (formValues) {
        // Criando o FormData para enviar via AJAX sem recarregar
        let formData = new FormData();
        formData.append('titulo', formValues.titulo);
        formData.append('video', formValues.file);

        Swal.fire({ title: 'Enviando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch('upload_shits.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.text())
        .then(data => {
            Swal.fire('SUCESSO!', 'Seu vídeo está no ar.', 'success');
        })
        .catch(() => Swal.fire('ERRO', 'Falha ao enviar o vídeo.', 'error'));
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/resumablejs/resumable.min.js"></script>
<script>
var r = new Resumable({
    target: 'upload.php', // seu backend
    chunkSize: 2 * 1024 * 1024, // 2MB
    simultaneousUploads: 3,
    testChunks: false
});

r.assignBrowse(document.getElementById('fileInput'));

r.on('fileAdded', function(file){
    document.getElementById('progressText').innerText = "Iniciando upload...";
    r.upload();
});

r.on('fileProgress', function(file){
    let percent = Math.floor(file.progress() * 100);

    document.getElementById('progressBar').style.width = percent + "%";
    document.getElementById('progressText').innerText = percent + "% enviado";
});

r.on('fileSuccess', function(){
    document.getElementById('progressText').innerText = "Upload concluído!";
    setTimeout(() => location.reload(), 1000);
});

r.on('fileError', function(){
    document.getElementById('progressText').innerText = "Erro no upload!";
});
</script>

</body>
</html>
