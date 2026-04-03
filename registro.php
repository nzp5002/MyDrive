<?php
require_once 'config.php';

$token = $_GET['token'] ?? '';
$erro = "";

// Validar Token
$stmt = $conn->prepare("SELECT criado_por FROM convites WHERE token = ? AND usado = 0 AND expira_em > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$convite = $stmt->get_result()->fetch_assoc();

if (!$convite) {
    die("<h1>Link inválido ou expirado</h1>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_registro'])) {

    $nome = trim($_POST['nome']);
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username']);
    $password = $_POST['password'];

    // 🔐 Validações básicas
    if (strlen($username) < 3 || strlen($username) > 20) {
        $erro = "Username inválido.";
    }

    if (strlen($password) < 6) {
        $erro = "Senha muito curta.";
    }

    // Verificar username duplicado
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $erro = "Username já existe.";
    }

    if (!$erro) {

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $padrinho_id = $convite['criado_por'];

        // 📁 Criar pasta segura
        if (!is_dir('uploads/triagem')) {
            mkdir('uploads/triagem', 0755, true);
        }

        $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        $arquivos_validos = [];

        // 🔍 Validar arquivos primeiro
        foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {

            if (!is_uploaded_file($tmp_name)) continue;

            $mime = finfo_file($finfo, $tmp_name);

            if (!in_array($mime, $permitidos)) continue;

            if ($_FILES['fotos']['size'][$key] > $max_size) continue;

            $extensoes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $ext = $extensoes[$mime];
            $nome_seguro = bin2hex(random_bytes(16)) . "." . $ext;

            $arquivos_validos[] = [
                'tmp' => $tmp_name,
                'nome' => $nome_seguro
            ];
        }

        if (count($arquivos_validos) < 3) {
            $erro = "Envie pelo menos 3 imagens válidas.";
        }

        if (!$erro) {

            $conn->begin_transaction();

            try {

                // 👤 Criar usuário
                $ins = $conn->prepare("INSERT INTO users (nome, username, password, indicado_por, status) VALUES (?, ?, ?, ?, 'pendente')");
                $ins->bind_param("sssi", $nome, $username, $password_hash, $padrinho_id);
                $ins->execute();

                $new_user_id = $conn->insert_id;

                // 📸 Salvar imagens
                foreach ($arquivos_validos as $file) {

                    $destino = "uploads/triagem/" . $file['nome'];

                    if (move_uploaded_file($file['tmp'], $destino)) {

                        $img_ins = $conn->prepare("INSERT INTO triagem_fotos (user_id, caminho) VALUES (?, ?)");
                        $img_ins->bind_param("is", $new_user_id, $file['nome']);
                        $img_ins->execute();
                    }
                }

                // 🔥 Marcar convite como usado
                $up = $conn->prepare("UPDATE convites SET usado = 1 WHERE token = ?");
                $up->bind_param("s", $token);
                $up->execute();

                $conn->commit();

                die("<h1>Solicitação enviada! Aguarde aprovação.</h1>");

            } catch (Exception $e) {
                $conn->rollback();
                $erro = "Erro no registro.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo MyDrive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-slate-950 flex items-center justify-center min-h-screen p-4 font-sans">
    
    <div class="bg-white p-8 rounded-[2.5rem] w-full max-w-md shadow-2xl border border-white/20">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-black italic uppercase tracking-tighter text-slate-900">MyDrive</h1>
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-600">Registo por Convite</p>
        </div>

        <?php if($erro): ?>
            <div class="mb-6 bg-red-50 text-red-500 p-4 rounded-2xl text-xs font-bold border border-red-100 italic text-center">
                <i class="fas fa-exclamation-triangle mr-2"></i> <?= $erro ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            
            <div id="preview" class="grid grid-cols-3 gap-2 empty:hidden mb-4"></div>
            
            <label class="block border-2 border-dashed border-slate-200 p-6 rounded-[2rem] text-center cursor-pointer hover:bg-slate-50 hover:border-blue-300 transition-all group">
                <i class="fas fa-images text-2xl text-slate-300 group-hover:text-blue-500 transition-colors"></i>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Fotos de Verificação (3+)</p>
                <input type="file" name="fotos[]" id="fotoInput" multiple accept="image/*" required class="hidden" onchange="handlePreview(this)">
            </label>

            <div class="space-y-3">
                <input type="text" name="nome" placeholder="NOME COMPLETO" required class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-xs">
                
                <input type="text" name="username" placeholder="USUÁRIO (Ex: marcos_99)" required class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-xs uppercase tracking-widest">
                
                <div class="relative group">
                    <input type="password" name="password" id="passwordField" placeholder="SENHA" required 
                           class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 font-bold text-xs">
                    
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                        <button type="button" onclick="generateStrongPassword()" title="Gerar Senha Forte"
                                class="w-8 h-8 flex items-center justify-center bg-blue-100 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                            <i class="fas fa-magic text-xs"></i>
                        </button>
                        <button type="button" onclick="togglePass()" 
                                class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                            <i id="passIcon" class="fas fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <button name="finalizar_registro" class="w-full bg-slate-900 text-white py-5 rounded-[2rem] font-black uppercase text-[10px] tracking-[0.3em] hover:bg-blue-600 hover:shadow-xl hover:shadow-blue-200 transition-all transform active:scale-95">
                Enviar para Aprovação
            </button>
        </form>
    </div>

    <script>
    // Gerador de Senha Forte
    function generateStrongPassword() {
        const length = 14;
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+=-";
        let retVal = "";
        for (let i = 0; i < length; ++i) {
            retVal += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        
        const field = document.getElementById('passwordField');
        field.value = retVal;
        field.type = "text"; // Mostra a senha ao gerar
        document.getElementById('passIcon').className = "fas fa-eye-slash text-xs";
        
        // Pequeno feedback visual
        field.classList.add('ring-2', 'ring-emerald-400');
        setTimeout(() => field.classList.remove('ring-2', 'ring-emerald-400'), 1000);
    }

    // Mostrar/Ocultar Senha
    function togglePass() {
        const field = document.getElementById('passwordField');
        const icon = document.getElementById('passIcon');
        if (field.type === "password") {
            field.type = "text";
            icon.className = "fas fa-eye-slash text-xs";
        } else {
            field.type = "password";
            icon.className = "fas fa-eye text-xs";
        }
    }

    // Preview de Imagens
    function handlePreview(input) {
        const p = document.getElementById('preview');
        p.innerHTML = '';
        if(input.files.length < 3) {
            alert("⚠️ Selecione no mínimo 3 fotos para sua triagem.");
        }
        Array.from(input.files).forEach(f => {
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = "w-full h-24 object-cover rounded-2xl border border-slate-200 shadow-sm";
                p.appendChild(img);
            }
            reader.readAsDataURL(f);
        });
    }
    </script>
</body>
</html>
