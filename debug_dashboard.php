<?php
echo "🔍 Testando dashboard...<br>";

try {
    echo "1. Incluindo config...<br>";
    require_once 'includes/config.php';
    
    echo "2. Verificando sessão...<br>";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'NÃO LOGADO') . "<br>";
    
    if (!isset($_SESSION['user_id'])) {
        echo "❌ Não está logado - redirecionando...<br>";
        header('Location: login.php');
        exit();
    }
    
    echo "3. Testando banco...<br>";
    $db = Database::getInstance();
    
    echo "4. Buscando usuário...<br>";
    $user = $db->select("SELECT * FROM usuarios WHERE id = ?", [$_SESSION['user_id']]);
    
    if ($user) {
        echo "✅ Usuário encontrado: " . $user[0]['nome'] . "<br>";
    } else {
        echo "❌ Usuário não encontrado<br>";
    }
    
    echo "5. Testando consultas...<br>";
    $stats = $db->select("SELECT COUNT(*) as total FROM modulos WHERE usuario_id = ?", [$_SESSION['user_id']]);
    echo "✅ Módulos: " . ($stats[0]['total'] ?? 0) . "<br>";
    
    echo "🎉 Tudo funcionando! O problema pode estar no dashboard.php";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "<br>";
    echo "📍 Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine();
}
?>