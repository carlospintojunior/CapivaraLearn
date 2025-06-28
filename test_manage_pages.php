<?php
require_once __DIR__ . '/includes/config.php';

echo "Teste de execução das páginas de gerenciamento<br>";
echo "Arquivo config.php carregado com sucesso<br>";

try {
    requireLogin();
    echo "Função requireLogin() executada com sucesso<br>";
} catch (Exception $e) {
    echo "Erro na função requireLogin(): " . $e->getMessage() . "<br>";
}

echo "Usuário logado: " . ($_SESSION['usuario_id'] ?? 'Não logado') . "<br>";
?>
