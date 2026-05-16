<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

checkLogin();

$user_id = $_SESSION['user_id'];

// Determina se a requisição veio via POST (AJAX do SweetAlert) ou GET
$is_ajax = ($_SERVER['REQUEST_METHOD'] === 'POST');

// Recebe ID e tipo tratando ambos os métodos
$hash = $is_ajax ? ($_POST['id'] ?? '') : ($_GET['id'] ?? '');
$type = $is_ajax ? ($_POST['type'] ?? 'file') : ($_GET['type'] ?? 'file');

$id = decodeId($hash);

if (!$id || !is_numeric($id)) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID inválido decodificado.']);
        exit;
    }
    header("Location: index.php?error=invalid_id");
    exit;
}

// Pasta local onde os arquivos físicos estão guardados
$upload_dir = __DIR__ . '/uploads/';

try {
    // =========================================================
    // APAGAR PASTA
    // =========================================================
    if ($type === 'folder') {

        // Verifica se pasta existe e pertence ao usuário
        $stmt = $conn->prepare("SELECT id FROM folders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $folder = $stmt->get_result()->fetch_assoc();

        if (!$folder) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Pasta não encontrada ou acesso negado.']);
                exit;
            }
            header("Location: index.php?error=folder_not_found");
            exit;
        }

        // Buscar a coluna 'arquivo' de todos os arquivos contidos nesta pasta
        $stmt_files = $conn->prepare("SELECT id, arquivo FROM files WHERE folder_id = ? AND user_id = ?");
        $stmt_files->bind_param("ii", $id, $user_id);
        $stmt_files->execute();
        $res_files = $stmt_files->get_result();

        // Loop para apagar fisicamente cada arquivo da pasta do disco
        while ($file = $res_files->fetch_assoc()) {
            if (!empty($file['arquivo'])) {
                $filePath = $upload_dir . $file['arquivo'];
                if (file_exists($filePath) && is_file($filePath)) {
                    unlink($filePath);
                }
            }
        }

        // Deletar os registros de arquivos da pasta no banco
        $delFiles = $conn->prepare("DELETE FROM files WHERE folder_id = ? AND user_id = ?");
        $delFiles->bind_param("ii", $id, $user_id);
        $delFiles->execute();

        // Deletar a pasta no banco
        $delFolder = $conn->prepare("DELETE FROM folders WHERE id = ? AND user_id = ?");
        $delFolder->bind_param("ii", $id, $user_id);
        $delFolder->execute();
    }
    // =========================================================
    // APAGAR ARQUIVO INDIVIDUAL
    // =========================================================
    else {
        // Seleciona a coluna correta ('arquivo') para saber o nome do arquivo físico
        $stmt = $conn->prepare("SELECT id, arquivo FROM files WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $file = $stmt->get_result()->fetch_assoc();

        if (!$file) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Arquivo não encontrado ou acesso negado.']);
                exit;
            }
            header("Location: index.php?error=file_not_found");
            exit;
        }

        $filePath = !empty($file['arquivo']) ? $upload_dir . $file['arquivo'] : '';

        // Primeiro remove o registro do banco de dados
        $delStmt = $conn->prepare("DELETE FROM files WHERE id = ? AND user_id = ?");
        $delStmt->bind_param("ii", $id, $user_id);

        if ($delStmt->execute()) {
            // Se removeu do banco com sucesso, apaga o arquivo físico do disco
            if (!empty($filePath) && file_exists($filePath) && is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    // Retorno de Sucesso para requisições AJAX
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    header("Location: index.php?status=deleted");
    exit;

} catch (Throwable $e) {
    error_log("DELETE ERROR: " . $e->getMessage());

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro interno ao processar exclusão.']);
        exit;
    }

    header("Location: index.php?error=internal_error");
    exit;
}
?>

