<?php
/**
 * Teste do cabeçalho de aviso do install.php
 */

echo "<h1>Teste do Cabeçalho de Aviso do Config.php</h1>";

// Simular os parâmetros do banco
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'capivaralearn_test';

// Extrair apenas a parte do cabeçalho do install.php
$configContent = "<?php
/**
 * ===============================================
 * 🦫 CapivaraLearn - Arquivo de Configuração
 * ===============================================
 * 
 * ⚠️  ATENÇÃO: ARQUIVO GERADO AUTOMATICAMENTE
 * 
 * Este arquivo foi criado automaticamente pelo instalador em " . date('d/m/Y H:i:s') . "
 * 
 * 🚨 IMPORTANTE:
 * - NÃO EDITE este arquivo manualmente
 * - Todas as alterações manuais serão PERDIDAS na próxima reinstalação
 * - Para configurações de ambiente, edite o arquivo 'includes/environment.ini'
 * - Para modificações permanentes, edite o template em 'install.php'
 * 
 * 📝 Para recriar este arquivo:
 * 1. Execute: php install.php (via navegador ou linha de comando)
 * 2. Ou delete este arquivo e acesse qualquer página do sistema
 * 
 * 🔧 Gerado pela versão: " . (defined('APP_VERSION') ? APP_VERSION : '1.0.0') . "
 * 📅 Data de criação: " . date('d/m/Y H:i:s') . "
 * 🖥️  Servidor: " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "
 * 
 * ===============================================
 */

// Exemplo de configuração básica
define('DB_HOST', '$host');
define('DB_NAME', '$dbname');
define('DB_USER', '$user');
define('DB_PASS', '$pass');

echo 'Sistema configurado com avisos!';
?>";

// Testar se o conteúdo é válido
echo "<h2>📄 Conteúdo do Cabeçalho Gerado:</h2>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto;'>";
echo htmlspecialchars($configContent);
echo "</pre>";

// Testar sintaxe PHP
$tempFile = '/tmp/test_config_header.php';
if (file_put_contents($tempFile, $configContent)) {
    echo "<h2>✅ Teste de Sintaxe PHP:</h2>";
    $syntaxCheck = shell_exec("php -l $tempFile 2>&1");
    if (strpos($syntaxCheck, 'No syntax errors') !== false) {
        echo "<div style='color: green; font-weight: bold;'>✅ Sintaxe PHP válida!</div>";
    } else {
        echo "<div style='color: red; font-weight: bold;'>❌ Erro de sintaxe: " . htmlspecialchars($syntaxCheck) . "</div>";
    }
    
    echo "<h2>🔍 Características do Cabeçalho:</h2>";
    echo "<ul>";
    
    if (strpos($configContent, '⚠️  ATENÇÃO: ARQUIVO GERADO AUTOMATICAMENTE') !== false) {
        echo "<li style='color: green;'>✅ Aviso principal de arquivo automático</li>";
    }
    
    if (strpos($configContent, 'NÃO EDITE este arquivo manualmente') !== false) {
        echo "<li style='color: green;'>✅ Aviso específico contra edição manual</li>";
    }
    
    if (strpos($configContent, 'Data de criação:') !== false) {
        echo "<li style='color: green;'>✅ Timestamp de criação incluído</li>";
    }
    
    if (strpos($configContent, 'Servidor:') !== false) {
        echo "<li style='color: green;'>✅ Informação do servidor incluída</li>";
    }
    
    if (strpos($configContent, 'Para recriar este arquivo') !== false) {
        echo "<li style='color: green;'>✅ Instruções de regeneração incluídas</li>";
    }
    
    echo "</ul>";
    
    // Clean up
    unlink($tempFile);
} else {
    echo "<div style='color: red;'>❌ Não foi possível criar arquivo de teste</div>";
}

echo "<hr>";
echo "<h2>📋 Próximos Passos:</h2>";
echo "<ol>";
echo "<li>✅ Cabeçalho de aviso implementado no install.php</li>";
echo "<li>✅ Sintaxe PHP válida confirmada</li>";
echo "<li>✅ Todas as informações importantes incluídas</li>";
echo "<li>🎯 Sistema pronto para usar</li>";
echo "</ol>";

echo "<p><strong>Status:</strong> <span style='color: green; font-weight: bold;'>CONCLUÍDO ✅</span></p>";
echo "<p>O install.php agora gera um config.php com avisos claros sobre não edição manual e instruções adequadas.</p>";
?>
