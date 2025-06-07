<?php
echo "<h1>🔍 Diagnóstico Completo de Dependências - CapivaraLearn</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; background: #e8f5e9; padding: 10px; margin: 5px 0; border-radius: 5px; }
.error { color: red; background: #ffebee; padding: 10px; margin: 5px 0; border-radius: 5px; }
.warning { color: orange; background: #fff3e0; padding: 10px; margin: 5px 0; border-radius: 5px; }
.info { color: blue; background: #e3f2fd; padding: 10px; margin: 5px 0; border-radius: 5px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

echo "<h2>📋 1. Verificando arquivos críticos</h2>";

$files_to_check = [
    '/opt/lampp/htdocs/CapivaraLearn/includes/config.php',
    '/opt/lampp/htdocs/CapivaraLearn/includes/Database.php',
    '/opt/lampp/htdocs/CapivaraLearn/includes/DatabaseConnection.php',
    '/opt/lampp/htdocs/CapivaraLearn/includes/services/UniversityService.php',
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ {$file} existe</div>";
    } else {
        echo "<div class='error'>❌ {$file} NÃO EXISTE</div>";
    }
}

echo "<h2>🔍 2. Analisando includes e requires</h2>";

// Verificar o que está sendo incluído no UniversityService
$university_service_content = file_get_contents('/opt/lampp/htdocs/CapivaraLearn/includes/services/UniversityService.php');
echo "<h3>UniversityService.php - Primeiras 20 linhas:</h3>";
echo "<pre>" . htmlspecialchars(substr($university_service_content, 0, 1000)) . "...</pre>";

// Verificar se Database está definida no config.php
echo "<h2>🏗️ 3. Verificando definição da classe Database</h2>";

try {
    // Incluir config.php e verificar se Database existe
    require_once '/opt/lampp/htdocs/CapivaraLearn/includes/config.php';
    
    if (class_exists('Database')) {
        echo "<div class='success'>✅ Classe Database existe no config.php</div>";
        
        $db = Database::getInstance();
        echo "<div class='success'>✅ Database::getInstance() funciona</div>";
        
        $methods = get_class_methods($db);
        echo "<div class='info'>📋 Métodos disponíveis na classe Database: " . implode(', ', $methods) . "</div>";
        
        if (method_exists($db, 'insert')) {
            echo "<div class='success'>✅ Método insert() existe na classe Database</div>";
        } else {
            echo "<div class='error'>❌ Método insert() NÃO existe na classe Database</div>";
        }
        
        if (method_exists($db, 'select')) {
            echo "<div class='success'>✅ Método select() existe na classe Database</div>";
        } else {
            echo "<div class='error'>❌ Método select() NÃO existe na classe Database</div>";
        }
        
    } else {
        echo "<div class='error'>❌ Classe Database NÃO existe após incluir config.php</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro ao incluir config.php: " . $e->getMessage() . "</div>";
}

echo "<h2>🔗 4. Simulando carregamento do UniversityService</h2>";

try {
    // Limpar qualquer definição anterior
    if (class_exists('UniversityService')) {
        echo "<div class='warning'>⚠️ UniversityService já estava carregado</div>";
    }
    
    // Tentar incluir UniversityService
    require_once '/opt/lampp/htdocs/CapivaraLearn/includes/services/UniversityService.php';
    
    if (class_exists('UniversityService')) {
        echo "<div class='success'>✅ UniversityService carregado com sucesso</div>";
        
        $service = UniversityService::getInstance();
        echo "<div class='success'>✅ UniversityService::getInstance() funciona</div>";
        
        // Verificar se a propriedade $db está corretamente inicializada
        $reflection = new ReflectionClass($service);
        $dbProperty = $reflection->getProperty('db');
        $dbProperty->setAccessible(true);
        $dbInstance = $dbProperty->getValue($service);
        
        if ($dbInstance) {
            echo "<div class='success'>✅ Propriedade \$db está inicializada</div>";
            echo "<div class='info'>📋 Tipo da propriedade \$db: " . get_class($dbInstance) . "</div>";
            
            if (method_exists($dbInstance, 'insert')) {
                echo "<div class='success'>✅ O objeto \$db tem método insert()</div>";
            } else {
                echo "<div class='error'>❌ O objeto \$db NÃO tem método insert()</div>";
                echo "<div class='info'>📋 Métodos disponíveis no \$db: " . implode(', ', get_class_methods($dbInstance)) . "</div>";
            }
        } else {
            echo "<div class='error'>❌ Propriedade \$db está NULL ou não inicializada</div>";
        }
        
    } else {
        echo "<div class='error'>❌ UniversityService NÃO foi carregado</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro ao carregar UniversityService: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Stack trace: <pre>" . $e->getTraceAsString() . "</pre></div>";
}

echo "<h2>🧪 5. Teste específico do método create()</h2>";

try {
    if (class_exists('UniversityService')) {
        $service = UniversityService::getInstance();
        
        echo "<div class='info'>🔍 Tentando chamar o método create()...</div>";
        
        $testData = [
            'nome' => 'Teste Diagnóstico',
            'sigla' => 'TD',
            'cidade' => 'São Paulo',
            'estado' => 'SP'
        ];
        
        // Tentar executar o create
        $result = $service->create($testData);
        echo "<div class='success'>✅ Método create() executado com sucesso! Resultado: " . ($result ? $result : 'false') . "</div>";
        
    } else {
        echo "<div class='error'>❌ UniversityService não está disponível para teste</div>";
    }
    
} catch (Error $e) {
    echo "<div class='error'>❌ ERRO FATAL capturado: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")</div>";
    echo "<div class='error'>Stack trace: <pre>" . $e->getTraceAsString() . "</pre></div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Exceção capturada: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Stack trace: <pre>" . $e->getTraceAsString() . "</pre></div>";
}

echo "<h2>📊 6. Resumo do Diagnóstico</h2>";
echo "<div class='info'>
<p>Este diagnóstico mostra exatamente onde está o problema sistêmico.</p>
<p>Se você ver 'Call to undefined method Database::insert()' aqui, sabemos que:</p>
<ul>
<li>O problema está na forma como a classe Database está sendo carregada</li>
<li>Ou há conflito entre Database.php e DatabaseConnection.php</li>
<li>Ou o autoloader está carregando a classe errada</li>
</ul>
</div>";

?>
