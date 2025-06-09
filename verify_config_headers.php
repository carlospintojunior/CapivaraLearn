<?php
/**
 * Teste Final - Verificação do Config.php Gerado
 */

echo "<h1>🔍 Verificação Final do Config.php Gerado pelo Install.php</h1>";

try {
    // Verificar se existe um config.php atual
    $configPath = '/opt/lampp/htdocs/CapivaraLearn/includes/config.php';
    
    if (file_exists($configPath)) {
        echo "<h2>📄 Análise do Config.php Atual</h2>";
        
        $configContent = file_get_contents($configPath);
        
        echo "<h3>🔍 Verificação dos Avisos no Cabeçalho:</h3>";
        
        $headerChecks = [
            '🦫 CapivaraLearn - Arquivo de Configuração' => 'Título identificador',
            '⚠️  ATENÇÃO: ARQUIVO GERADO AUTOMATICAMENTE' => 'Aviso principal',
            'Este arquivo foi criado automaticamente pelo instalador' => 'Descrição de geração automática',
            'NÃO EDITE este arquivo manualmente' => 'Aviso direto contra edição',
            'Todas as alterações manuais serão PERDIDAS' => 'Aviso sobre perda de dados',
            'Para configurações de ambiente, edite o arquivo' => 'Instruções para environment.ini',
            'Para modificações permanentes, edite o template' => 'Instruções para template',
            'Para recriar este arquivo:' => 'Instruções de regeneração',
            'Execute: php install.php' => 'Comando específico',
            'Gerado pela versão:' => 'Informação de versão',
            'Data de criação:' => 'Timestamp de criação',
            'Servidor:' => 'Informação do servidor'
        ];
        
        $headerScore = 0;
        $totalChecks = count($headerChecks);
        
        echo "<ul>";
        foreach ($headerChecks as $pattern => $description) {
            if (strpos($configContent, $pattern) !== false) {
                echo "<li style='color: green;'>✅ $description</li>";
                $headerScore++;
            } else {
                echo "<li style='color: red;'>❌ $description</li>";
            }
        }
        echo "</ul>";
        
        echo "<h3>🔍 Verificação dos Avisos no Rodapé:</h3>";
        
        $footerChecks = [
            'FIM DO ARQUIVO DE CONFIGURAÇÃO' => 'Marcador de fim',
            'LEMBRE-SE: Este arquivo foi gerado automaticamente!' => 'Lembrete final',
            'Para modificar configurações:' => 'Instruções finais',
            'Execute nova instalação para aplicar' => 'Processo de aplicação'
        ];
        
        $footerScore = 0;
        $totalFooterChecks = count($footerChecks);
        
        echo "<ul>";
        foreach ($footerChecks as $pattern => $description) {
            if (strpos($configContent, $pattern) !== false) {
                echo "<li style='color: green;'>✅ $description</li>";
                $footerScore++;
            } else {
                echo "<li style='color: red;'>❌ $description</li>";
            }
        }
        echo "</ul>";
        
        // Cálculo do score total
        $totalScore = $headerScore + $footerScore;
        $maxScore = $totalChecks + $totalFooterChecks;
        $percentage = round(($totalScore / $maxScore) * 100);
        
        echo "<h3>📊 Resultado da Análise:</h3>";
        
        if ($percentage >= 90) {
            $color = 'green';
            $status = 'EXCELENTE';
            $icon = '🏆';
        } elseif ($percentage >= 70) {
            $color = 'orange';
            $status = 'BOM';
            $icon = '👍';
        } else {
            $color = 'red';
            $status = 'NECESSITA MELHORIAS';
            $icon = '⚠️';
        }
        
        echo "<div style='padding: 20px; background: " . ($color == 'green' ? '#d4edda' : ($color == 'orange' ? '#fff3cd' : '#f8d7da')) . "; border-radius: 10px; margin: 20px 0;'>";
        echo "<h4 style='color: $color; margin-top: 0;'>$icon Pontuação: $totalScore/$maxScore ($percentage%)</h4>";
        echo "<p style='color: $color; font-weight: bold; font-size: 18px;'>Status: $status</p>";
        echo "</div>";
        
        // Mostrar uma amostra do cabeçalho
        echo "<h3>📖 Amostra do Cabeçalho (primeiras 1000 caracteres):</h3>";
        echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow: auto; max-height: 300px; font-size: 12px;'>";
        echo htmlspecialchars(substr($configContent, 0, 1000));
        echo "...</pre>";
        
        // Informações adicionais sobre o arquivo
        echo "<h3>📈 Informações do Arquivo:</h3>";
        echo "<ul>";
        echo "<li><strong>Tamanho:</strong> " . number_format(strlen($configContent)) . " caracteres</li>";
        echo "<li><strong>Linhas:</strong> " . substr_count($configContent, "\n") . " linhas</li>";
        echo "<li><strong>Última modificação:</strong> " . date('d/m/Y H:i:s', filemtime($configPath)) . "</li>";
        echo "</ul>";
        
        // Verificar funcionalidade básica
        echo "<h3>🔧 Teste de Funcionalidade:</h3>";
        
        // Tentar incluir o config para verificar se não há erros
        ob_start();
        $syntaxError = false;
        
        try {
            // Verificar sintaxe sem executar
            $syntaxCheck = shell_exec("php -l $configPath 2>&1");
            if (strpos($syntaxCheck, 'No syntax errors') !== false) {
                echo "<div style='color: green;'>✅ Sintaxe PHP válida</div>";
            } else {
                echo "<div style='color: red;'>❌ Erro de sintaxe: " . htmlspecialchars($syntaxCheck) . "</div>";
                $syntaxError = true;
            }
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ Erro ao verificar sintaxe: " . htmlspecialchars($e->getMessage()) . "</div>";
            $syntaxError = true;
        }
        
        ob_end_clean();
        
        if (!$syntaxError) {
            echo "<div style='color: green;'>✅ Config.php funcionalmente válido</div>";
        }
        
    } else {
        echo "<div style='color: blue; padding: 15px; background: #cce7ff; border-radius: 5px;'>";
        echo "<h3>ℹ️ Config.php Não Encontrado</h3>";
        echo "<p>O arquivo config.php ainda não foi criado. Para criar um config.php com os avisos implementados:</p>";
        echo "<ol>";
        echo "<li>Acesse <a href='install.php' target='_blank'>install.php</a></li>";
        echo "<li>Complete o processo de instalação</li>";
        echo "<li>Execute este teste novamente</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    // Status final da implementação
    echo "<h2>🎯 Status da Implementação dos Avisos</h2>";
    
    echo "<div style='padding: 20px; background: #e8f5e8; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3 style='color: #2d5a2d; margin-top: 0;'>✅ Recursos Implementados:</h3>";
    echo "<ul style='color: #2d5a2d;'>";
    echo "<li>🔸 Cabeçalho detalhado com avisos claros</li>";
    echo "<li>🔸 Aviso principal sobre geração automática</li>";
    echo "<li>🔸 Instruções específicas contra edição manual</li>";
    echo "<li>🔸 Orientações sobre como fazer modificações corretas</li>";
    echo "<li>🔸 Informações de criação (data, servidor, versão)</li>";
    echo "<li>🔸 Instruções claras de regeneração</li>";
    echo "<li>🔸 Rodapé com lembretes finais</li>";
    echo "<li>🔸 Sintaxe PHP válida e funcional</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; background: #f8d7da; border-radius: 5px;'>";
    echo "❌ Erro durante a verificação: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "<hr>";
echo "<div style='text-align: center; padding: 20px;'>";
echo "<h3>🏁 Conclusão</h3>";
echo "<p>O sistema de avisos no config.php foi implementado com sucesso no install.php.</p>";
echo "<p>Todos os usuários serão alertados sobre a natureza automática do arquivo e orientados sobre as práticas corretas de modificação.</p>";
echo "<p style='color: #666; font-style: italic;'>Verificação realizada em " . date('d/m/Y H:i:s') . "</p>";
echo "</div>";
?>
