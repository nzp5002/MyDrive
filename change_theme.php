<?php
require_once __DIR__ . '/../db/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme']) && isset($_SESSION['user_id'])) {
    $theme = $_POST['theme'];
    $allowed_themes = ['aurora', 'dark', 'light', 'ocean', 'sunset', 'forest', 'glassmorphism'];
    
    if (in_array($theme, $allowed_themes)) {
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("UPDATE users SET theme = ? WHERE id = ?");
        $stmt->bind_param("si", $theme, $user_id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid theme']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>
