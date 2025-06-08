<?php
require_once 'includes/config.php';

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