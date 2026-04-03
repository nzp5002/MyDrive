<?php
// compartilhar.php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// 1. Pegar os IDs da URL (enviados pelo fetch do index.php)
$hash_item = $_GET['file_id'] ?? $_GET['id'] ?? null; 
$hash_para = $_GET['para_id'] ?? null;

// 2. Decodificar os IDs usando as funções que agora estão no config.php
$item_id = decodeId($hash_item);
$para_user_id = decodeId($hash_para);
$de_user_id = $_SESSION['user_id'] ?? 0;

if (!$item_id || !$para_user_id || !$de_user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Dados inválidos ou sessão expirada']);
    exit;
}

// 3. Lógica de compartilhamento
// IMPORTANTE: Precisamos saber se o item_id pertence a um arquivo ou pasta.
// Vamos verificar primeiro na tabela de arquivos:
$check_file = $conn->prepare("SELECT id FROM files WHERE id = ? AND user_id = ?");
$check_file->bind_param("ii", $item_id, $de_user_id);
$check_file->execute();
$is_file = $check_file->get_result()->num_rows > 0;

if ($is_file) {
    // É um arquivo: insere na tabela 'compartilhamentos'
    $stmt = $conn->prepare("INSERT IGNORE INTO compartilhamentos (file_id, para_user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $item_id, $para_user_id);
} else {
    // Se não for arquivo, verificamos se é uma pasta
    $check_folder = $conn->prepare("SELECT id FROM folders WHERE id = ? AND user_id = ?");
    $check_folder->bind_param("ii", $item_id, $de_user_id);
    $check_folder->execute();
    $is_folder = $check_folder->get_result()->num_rows > 0;

    if ($is_folder) {
        // É uma pasta: insere na tabela 'compartilhamentos_folders'
        $stmt = $conn->prepare("INSERT IGNORE INTO compartilhamentos_folders (folder_id, para_user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $item_id, $para_user_id);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Item não encontrado ou você não tem permissão']);
        exit;
    }
}

if ($stmt->execute()) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Erro ao compartilhar']);
}

