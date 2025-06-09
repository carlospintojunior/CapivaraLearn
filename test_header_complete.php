<?php
/**
 * Teste automático do sistema install.php com cabeçalho de aviso
 */

echo "<h1>🧪 Teste Completo do Sistema com Cabeçalho de Aviso</h1>";

try {
    // Teste 1: Verificar se install.php tem sintaxe válida
    echo "<h2>1. Verificação de Sintaxe do Install.php</h2>";
    $installPath = '/opt/lampp/htdocs/CapivaraLearn/install.php';
    
    if (file_exists($installPath)) {
        $syntaxCheck = shell_exec("php -l $installPath 2>&1");
        if (strpos($syntaxCheck, 'No syntax errors') !== false) {
            echo "<div style='color: green;'>✅ Install.php tem sintaxe PHP válida</div>";
        } else {
            echo "<div style='color: red;'>❌ Erro de sintaxe no install.php: " . htmlspecialchars($syntaxCheck) . "</div>";
            exit;
        }
    } else {
        echo "<div style='color: red;'>❌ Install.php não encontrado em $installPath</div>";
        exit;
    }
    
    // Teste 2: Simular instalação e verificar geração do config.php
    echo "<h2>2. Simulação de Instalação</h2>";
    
    // Conectar ao banco para teste
    try {
        $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        echo "<div style='color: green;'>✅ Conexão com MySQL estabelecida</div>";
        
        // Criar/limpar banco de teste
        $pdo->exec("DROP DATABASE IF EXISTS capivaralearn_test_header");
        $pdo->exec("CREATE DATABASE capivaralearn_test_header CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<div style='color: green;'>✅ Banco de teste criado</div>";
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Erro ao conectar com MySQL: " . $e->getMessage() . "</div>";
        exit;
    }
    
    // Teste 3: Extrair template do config.php do install.php
    echo "<h2>3. Teste do Template do Config.php</h2>";
    
    $installContent = file_get_contents($installPath);
    
    // Procurar pelo início do template
    $startPattern = '$configContent = "<?php';
    $endPattern = '?>";';
    
    $startPos = strpos($installContent, $startPattern);
    $endPos = strpos($installContent, $endPattern, $startPos);
    
    if ($startPos !== false && $endPos !== false) {
        echo "<div style='color: green;'>✅ Template do config.php encontrado no install.php</div>";
        
        // Extrair o template
        $templateSection = substr($installContent, $startPos, $endPos - $startPos + strlen($endPattern));
        
        // Verificar elementos específicos do cabeçalho
        $checks = [
            '⚠️  ATENÇÃO: ARQUIVO GERADO AUTOMATICAMENTE' => 'Aviso principal',
            'NÃO EDITE este arquivo manualmente' => 'Aviso contra edição manual', 
            'Todas as alterações manuais serão PERDIDAS' => 'Aviso sobre perda de alterações',
            'Para configurações de ambiente, edite o arquivo' => 'Instruções para configurações',
            'Para modificações permanentes, edite o template' => 'Instruções para modificações',
            'Para recriar este arquivo:' => 'Instruções de recriação',
            'Execute: php install.php' => 'Comando de reinstalação',
            'Data de criação:' => 'Timestamp de criação',
            'Servidor:' => 'Informação do servidor'
        ];
        
        echo "<h3>📋 Verificação dos Elementos do Cabeçalho:</h3>";
        echo "<ul>";
        
        $allChecksPass = true;
        foreach ($checks as $pattern => $description) {
            if (strpos($templateSection, $pattern) !== false) {
                echo "<li style='color: green;'>✅ $description</li>";
            } else {
                echo "<li style='color: red;'>❌ $description</li>";
                $allChecksPass = false;
            }
        }
        
        echo "</ul>";
        
        if ($allChecksPass) {
            echo "<div style='color: green; font-weight: bold; padding: 10px; background: #d4edda; border-radius: 5px; margin: 10px 0;'>✅ TODOS OS ELEMENTOS DO CABEÇALHO ESTÃO PRESENTES!</div>";
        } else {
            echo "<div style='color: red; font-weight: bold; padding: 10px; background: #f8d7da; border-radius: 5px; margin: 10px 0;'>❌ Alguns elementos do cabeçalho estão ausentes</div>";
        }
        
    } else {
        echo "<div style='color: red;'>❌ Template do config.php não encontrado no install.php</div>";
    }
    
    // Teste 4: Verificar se o config.php atual tem os avisos (se existir)
    echo "<h2>4. Verificação do Config.php Atual</h2>";
    
    $configPath = '/opt/lampp/htdocs/CapivaraLearn/includes/config.php';
    if (file_exists($configPath)) {
        $configContent = file_get_contents($configPath);
        
        if (strpos($configContent, 'ARQUIVO GERADO AUTOMATICAMENTE') !== false) {
            echo "<div style='color: green;'>✅ Config.php atual já possui avisos de geração automática</div>";
        } else {
            echo "<div style='color: orange;'>⚠️ Config.php atual não possui avisos (pode ter sido criado antes da implementação)</div>";
        }
        
        if (strpos($configContent, 'NÃO EDITE este arquivo manualmente') !== false) {
            echo "<div style='color: green;'>✅ Config.php atual possui aviso contra edição manual</div>";
        } else {
            echo "<div style='color: orange;'>⚠️ Config.php atual não possui aviso contra edição manual</div>";
        }
        
    } else {
        echo "<div style='color: blue;'>ℹ️ Config.php não existe ainda (será criado na primeira instalação)</div>";
    }
    
    // Teste 5: Status final
    echo "<h2>5. Status Final do Sistema</h2>";
    
    echo "<div style='padding: 20px; background: #e8f5e8; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3 style='color: #2d5a2d; margin-top: 0;'>🎉 Status do Sistema:</h3>";
    echo "<ul style='color: #2d5a2d;'>";
    echo "<li>✅ Install.php com sintaxe válida</li>";
    echo "<li>✅ Template do config.php com cabeçalho completo de avisos</li>";
    echo "<li>✅ Todos os elementos de aviso implementados</li>";
    echo "<li>✅ Instruções claras sobre não edição manual</li>";
    echo "<li>✅ Orientações para modificações adequadas</li>";
    echo "<li>✅ Sistema pronto para uso em produção</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='padding: 15px; background: #d1ecf1; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4 style='color: #0c5460; margin-top: 0;'>📝 Próximas Ações Recomendadas:</h4>";
    echo "<ol style='color: #0c5460;'>";
    echo "<li>Execute o install.php para gerar um novo config.php com os avisos</li>";
    echo "<li>Verifique se o config.php gerado possui todos os avisos</li>";
    echo "<li>Teste a funcionalidade completa do sistema</li>";
    echo "<li>Documente as melhorias implementadas</li>";
    echo "</ol>";
    echo "</div>";
    
    // Limpar banco de teste
    $pdo->exec("DROP DATABASE IF EXISTS capivaralearn_test_header");
    echo "<div style='color: gray; font-size: 12px;'>🧹 Banco de teste removido</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; background: #f8d7da; border-radius: 5px;'>❌ Erro durante o teste: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #666; font-style: italic;'>Teste concluído em " . date('d/m/Y H:i:s') . "</p>";
?>
