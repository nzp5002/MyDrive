<?php
require_once 'config.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$username_alvo = $_GET['username'] ?? '';

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR nome = ?");
$stmt->bind_param("ss", $username_alvo, $username_alvo);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    echo json_encode(['ok' => false, 'msg' => 'Utilizador não encontrado.']);
    exit;
}

$amigo_id = $res['id'];
if ($amigo_id == $user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Não podes ser teu próprio amigo.']);
    exit;
}

// Verifica se já existe pedido ou amizade
$check = $conn->prepare("SELECT id FROM amizades WHERE (usuario_id = ? AND amigo_id = ?) OR (usuario_id = ? AND amigo_id = ?)");
$check->bind_param("iiii", $user_id, $amigo_id, $amigo_id, $user_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    echo json_encode(['ok' => false, 'msg' => 'Já existe um pedido ou amizade ativa.']);
} else {
    $ins = $conn->prepare("INSERT INTO amizades (usuario_id, amigo_id, status) VALUES (?, ?, 'pendente')");
    $ins->bind_param("ii", $user_id, $amigo_id);
    $ins->execute();
    echo json_encode(['ok' => true]);
}

