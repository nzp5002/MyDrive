<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

checkLogin();

// ----------------------------------------------------------------------------

$user_id = $_SESSION['user_id'];

// Recebe o ID codificado e o Tipo (file ou folder)
$hash = $_GET['id'] ?? '';
$type = $_GET['type'] ?? 'file';
$id = decodeId($hash);

if (!$id) {
    header("Location: index.php?error=invalid_id");
    exit;
}

// Define o diretório de uploads (ajuste se o seu config.php já não tiver isso)
$upload_dir = __DIR__ . '/uploads/';

try {
    if ($type === 'folder') {
        // 1. Verificar se a pasta pertence ao usuário
        $stmt = $conn->prepare("SELECT id FROM folders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            // 2. Buscar caminhos dos arquivos dentro dessa pasta para apagar do disco
            $stmt_files = $conn->prepare("SELECT caminho FROM files WHERE folder_id = ? AND user_id = ?");
            $stmt_files->bind_param("ii", $id, $user_id);
            $stmt_files->execute();
            $res_files = $stmt_files->get_result();

            while ($f = $res_files->fetch_assoc()) {
                $fPath = $upload_dir . $f['caminho'];
                if (file_exists($fPath)) unlink($fPath);
            }

            // 3. Deletar os registros dos arquivos no banco
            $conn->query("DELETE FROM files WHERE folder_id = $id AND user_id = $user_id");

            // 4. Deletar a pasta
            $del = $conn->prepare("DELETE FROM folders WHERE id = ? AND user_id = ?");
            $del->bind_param("ii", $id, $user_id);
            $del->execute();
        }
    } else {
        // --- LÓGICA PARA DELETAR ARQUIVO ---
        $stmt = $conn->prepare("SELECT caminho FROM files WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($file = $result->fetch_assoc()) {
            $filePath = $upload_dir . $file['caminho'];

            $delStmt = $conn->prepare("DELETE FROM files WHERE id = ? AND user_id = ?");
            $delStmt->bind_param("ii", $id, $user_id);

            if ($delStmt->execute()) {
                if (file_exists($filePath)) unlink($filePath);
            }
        }
    }

    header("Location: index.php?status=deleted");

} catch (Exception $e) {
    error_log($e->getMessage());
    header("Location: index.php?error=internal_error");
}
exit;

