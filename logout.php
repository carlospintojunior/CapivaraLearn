<?php
require_once 'includes/config.php';
require_once 'includes/logger_config.php';

// Capturar dados do usuário antes de destruir a sessão
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'unknown';

// Log do logout
if ($userId) {
    logInfo('Logout realizado', [
        'user_id' => $userId,
        'user_name' => $userName,
        'session_id' => session_id()
    ]);
    
    // Registrar atividade no banco
    try {
        logActivity($userId, 'user_logout', "Logout realizado: {$userName}", $pdo ?? null);
    } catch (Exception $e) {
        logError('Erro ao registrar logout no banco', ['error' => $e->getMessage()]);
    }
}

// Limpar sessão
session_destroy();

// Limpar cookies se existirem
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirecionar para login com mensagem
header('Location: login.php?logout=1');
exit();
?>