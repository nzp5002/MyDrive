<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

checkLogin();

$user_id = $_SESSION['user_id'];

// Recebe ID e tipo
$hash = $_GET['id'] ?? '';
$type = $_GET['type'] ?? 'file';

$id = decodeId($hash);

if (!$id || !is_numeric($id)) {
    header("Location: index.php?error=invalid_id");
    exit;
}

// Pasta uploads
$upload_dir = __DIR__ . '/uploads/';

try {

    // =========================================================
    // APAGAR PASTA
    // =========================================================
    if ($type === 'folder') {

        // Verifica se pasta existe e pertence ao usuário
        $stmt = $conn->prepare("
            SELECT id 
            FROM folders 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();

        $folder = $stmt->get_result()->fetch_assoc();

        if (!$folder) {
            header("Location: index.php?error=folder_not_found");
            exit;
        }

        // Buscar arquivos da pasta
        $stmt_files = $conn->prepare("
            SELECT id, caminho 
            FROM files 
            WHERE folder_id = ? AND user_id = ?
        ");
        $stmt_files->bind_param("ii", $id, $user_id);
        $stmt_files->execute();

        $res_files = $stmt_files->get_result();

        while ($file = $res_files->fetch_assoc()) {

            if (!empty($file['caminho'])) {

                $filePath = $upload_dir . $file['caminho'];

                // Evita apagar diretório
                if (
                    file_exists($filePath) &&
                    is_file($filePath)
                ) {
                    unlink($filePath);
                }
            }
        }

        // Deletar arquivos do banco
        $delFiles = $conn->prepare("
            DELETE FROM files 
            WHERE folder_id = ? AND user_id = ?
        ");
        $delFiles->bind_param("ii", $id, $user_id);
        $delFiles->execute();

        // Deletar pasta
        $delFolder = $conn->prepare("
            DELETE FROM folders 
            WHERE id = ? AND user_id = ?
        ");
        $delFolder->bind_param("ii", $id, $user_id);
        $delFolder->execute();

    }

    // =========================================================
    // APAGAR ARQUIVO
    // =========================================================
    else {

        $stmt = $conn->prepare("
            SELECT id, caminho 
            FROM files 
            WHERE id = ? AND user_id = ?
        ");

        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();

        $result = $stmt->get_result();

        $file = $result->fetch_assoc();

        if (!$file) {
            header("Location: index.php?error=file_not_found");
            exit;
        }

        $filePath = '';

        if (!empty($file['caminho'])) {
            $filePath = $upload_dir . $file['caminho'];
        }

        // Primeiro remove do banco
        $delStmt = $conn->prepare("
            DELETE FROM files 
            WHERE id = ? AND user_id = ?
        ");

        $delStmt->bind_param("ii", $id, $user_id);

        if ($delStmt->execute()) {

            // Depois remove físico
            if (
                !empty($filePath) &&
                file_exists($filePath) &&
                is_file($filePath)
            ) {
                unlink($filePath);
            }
        }
    }

    header("Location: index.php?status=deleted");
    exit;

} catch (Throwable $e) {

    error_log("DELETE ERROR: " . $e->getMessage());

    header("Location: index.php?error=internal_error");
    exit;
}
?>
