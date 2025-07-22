<?php
// Teste simples de configuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTE DE CONFIGURAÇÃO ===\n";

echo "1. Incluindo config.php...\n";
require_once __DIR__ . '/includes/config.php';

echo "2. Verificando constantes do banco...\n";
echo "   DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NÃO DEFINIDO') . "\n";
echo "   DB_USER: " . (defined('DB_USER') ? DB_USER : 'NÃO DEFINIDO') . "\n";
echo "   DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NÃO DEFINIDO') . "\n";
echo "   DB_PASS: " . (defined('DB_PASS') ? (DB_PASS ? '***' : 'VAZIO') : 'NÃO DEFINIDO') . "\n";

echo "3. Testando conexão direta...\n";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    echo "   Conexão PDO: SUCESSO!\n";
    
    $result = $pdo->query("SELECT 1 as teste")->fetch();
    echo "   Query teste: " . json_encode($result) . "\n";
    
} catch (Exception $e) {
    echo "   ERRO: " . $e->getMessage() . "\n";
}

echo "=== FIM DO TESTE ===\n";
?>
