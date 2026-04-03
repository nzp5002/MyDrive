<?php
// api_folders.php
require_once 'config.php';

// 1. Evita erro de sessão duplicada (já iniciada no config.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Define o cabeçalho para JSON (importante para o fetch no JS entender a resposta)
header('Content-Type: application/json');

if (isset($_POST['action']) && $_POST['action'] === 'create') {
    
    // Pegando dados com fallback para evitar "Undefined array key"
    $nome = $_POST['nome'] ?? 'Nova Pasta';
    $status = $_POST['status'] ?? 'private';
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $user_id = $_SESSION['user_id'] ?? 0;

    if ($user_id === 0) {
        echo json_encode(['ok' => false, 'msg' => 'Usuário não autenticado']);
        exit;
    }

    // Preparando a Query
    $stmt = $conn->prepare("INSERT INTO folders (user_id, nome, parent_id, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $nome, $parent_id, $status);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id; // Pega o ID que acabou de ser criado no Banco
        
        // Retornamos o 'id_encoded' para o JavaScript poder disparar o compartilhamento automático
        echo json_encode([
            'ok' => true, 
            'id_encoded' => encodeId($new_id) 
        ]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Erro ao criar pasta no banco de dados']);
    }
    
} else {
    echo json_encode(['ok' => false, 'msg' => 'Ação inválida']);
}

