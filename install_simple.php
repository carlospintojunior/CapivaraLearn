<?php
require_once __DIR__ . '/includes/log_sistema.php';
log_sistema('Instalador iniciado', 'INFO');
/**
 * CapivaraLearn - Instalador Automático
 * Execute este arquivo uma única vez para configurar o sistema
 */

// Função para verificar dependências
function checkDependencies() {
    $results = array(
        'status' => true,
        'messages' => array()
    );
    
    // Verificar se o Composer está instalado
    exec('which composer', $output, $return_var);
    if ($return_var !== 0) {
        $results['status'] = false;
        $results['messages'][] = array(
            'type' => 'error',
            'message' => 'Composer não está instalado. Execute: sudo apt-get update && sudo apt-get install -y composer'
        );
    }
    
    // Verificar se o PHPMailer está instalado
    if (!file_exists(__DIR__ . '/vendor/phpmailer/phpmailer')) {
        $results['status'] = false;
        $results['messages'][] = array(
            'type' => 'error',
            'message' => 'PHPMailer não está instalado. Execute: cd ' . __DIR__ . ' && composer require phpmailer/phpmailer'
        );
    }
    
    // Verificar se a pasta logs existe e tem permissões corretas
    if (!file_exists(__DIR__ . '/logs')) {
        $results['status'] = false;
        $results['messages'][] = array(
            'type' => 'error',
            'message' => 'Diretório logs não existe. Execute: sudo mkdir -p ' . __DIR__ . '/logs'
        );
    } else {
        if (!is_writable(__DIR__ . '/logs')) {
            $results['status'] = false;
            $results['messages'][] = array(
                'type' => 'error',
                'message' => 'Diretório logs não tem permissões de escrita. Execute: sudo chmod -R 777 ' . __DIR__ . '/logs'
            );
        }
    }
    
    return $results;
}

// Verificar dependências antes de prosseguir
$deps = checkDependencies();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CapivaraLearn - Instalador</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .installer {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            font-size: 2.5em;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .step {
            margin: 20px 0;
            padding: 20px;
            border-radius: 10px;
            background: #f8f9fa;
        }
        .success {
            background: #d5f4e6;
            color: #27ae60;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #fdf2f2;
            color: #e74c3c;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .form-group {
            margin: 15px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background: #5a6fd8;
        }
        .log {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="logo">
            <h1>🦫 CapivaraLearn</h1>
            <p>Instalador Automático</p>
        </div>

        <?php if (!$deps['status']): ?>
            <div class="step">
                <h3>❌ Dependências Não Atendidas</h3>
                <p>Antes de continuar, resolva os seguintes problemas:</p>
                <?php foreach ($deps['messages'] as $msg): ?>
                    <div class="<?php echo $msg['type']; ?>">
                        <?php echo $msg['message']; ?>
                    </div>
                <?php endforeach; ?>
                <p><strong>Após resolver os problemas, atualize esta página.</strong></p>
            </div>
        <?php else: ?>
            <div class="step">
                <h3>✅ Dependências OK</h3>
                <div class="success">Todas as dependências foram verificadas com sucesso!</div>
            </div>

            <!-- Formulário de instalação -->
            <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <div class="step">
                <h3>🔧 Configuração do Banco de Dados</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Host do MySQL:</label>
                        <input type="text" name="host" value="localhost" required>
                    </div>
                    <div class="form-group">
                        <label>Usuário:</label>
                        <input type="text" name="user" value="root" required>
                    </div>
                    <div class="form-group">
                        <label>Senha:</label>
                        <input type="password" name="pass" value="">
                    </div>
                    <div class="form-group">
                        <label>Nome do Banco:</label>
                        <input type="text" name="dbname" value="capivaralearn" required>
                    </div>
                    <button type="submit" class="btn">🚀 Instalar CapivaraLearn</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Processo de instalação -->
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $host = $_POST['host'] ?? 'localhost';
            $user = $_POST['user'] ?? 'root';
            $pass = $_POST['pass'] ?? '';
            $dbname = $_POST['dbname'] ?? 'capivaralearn';
            
            log_sistema("Iniciando instalação do sistema - Host: $host, DB: $dbname", 'INFO');
            
            echo '<div class="step">';
            echo '<h3>🔄 Executando Instalação...</h3>';
            
            $log = '';
            
            try {
                // Conectar ao MySQL
                $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
                echo '<div class="success">✅ Conexão com MySQL estabelecida</div>';
                $log .= "✅ Conectado ao MySQL\n";
                log_sistema("Conexão com MySQL estabelecida com sucesso - Host: $host", 'SUCCESS');
                
                // Criar banco de dados
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$dbname`");
                echo '<div class="success">✅ Banco de dados criado/selecionado</div>';
                $log .= "✅ Banco '$dbname' criado/selecionado\n";
                log_sistema("Banco de dados '$dbname' criado/selecionado com sucesso", 'SUCCESS');
                
                // Criar tabelas (versão simplificada)
                $tables = [
                    "usuarios" => "CREATE TABLE IF NOT EXISTS usuarios (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nome VARCHAR(255) NOT NULL,
                        email VARCHAR(255) UNIQUE NOT NULL,
                        senha VARCHAR(255) NOT NULL,
                        ativo BOOLEAN DEFAULT TRUE,
                        email_verificado BOOLEAN DEFAULT FALSE,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB",
                    
                    "email_tokens" => "CREATE TABLE IF NOT EXISTS email_tokens (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        token VARCHAR(255) NOT NULL,
                        tipo ENUM('confirmacao', 'reset_senha') NOT NULL,
                        usado BOOLEAN DEFAULT FALSE,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_expiracao TIMESTAMP NOT NULL,
                        ip_address VARCHAR(45),
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        INDEX idx_token (token)
                    ) ENGINE=InnoDB"
                ];
                
                foreach ($tables as $tableName => $sql) {
                    try {
                        $pdo->exec($sql);
                        $log .= "✅ Tabela '$tableName' criada\n";
                        log_sistema("Tabela '$tableName' criada com sucesso", 'SUCCESS');
                    } catch (Exception $e) {
                        $log .= "❌ Erro ao criar tabela '$tableName': " . $e->getMessage() . "\n";
                        log_sistema("ERRO ao criar tabela '$tableName': " . $e->getMessage(), 'ERROR');
                    }
                }
                
                echo '<div class="success">✅ Estrutura básica do banco criada</div>';
                log_sistema("Estrutura básica do banco de dados criada com sucesso", 'SUCCESS');
                
                // Criar arquivo de configuração básico
                $configContent = "<?php
// Configuração básica do CapivaraLearn
define('DB_HOST', '$host');
define('DB_NAME', '$dbname');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('DB_CHARSET', 'utf8mb4');
define('APP_ENV', 'development');
?>";
                
                if (!is_dir('includes')) {
                    mkdir('includes', 0755, true);
                }
                
                if (file_put_contents('includes/config.php', $configContent)) {
                    echo '<div class="success">✅ Arquivo de configuração criado</div>';
                    $log .= "✅ Arquivo de configuração criado\n";
                    log_sistema("Arquivo de configuração criado com sucesso", 'SUCCESS');
                } else {
                    $log .= "❌ Erro ao criar arquivo de configuração\n";
                    log_sistema("ERRO ao criar arquivo de configuração", 'ERROR');
                }
                
                echo '<div class="success">';
                echo '<h3>🎉 Instalação Concluída com Sucesso!</h3>';
                echo '<p>O CapivaraLearn foi instalado com sucesso!</p>';
                echo '<p><a href="login.php" style="color: #667eea;">👉 Ir para a página de login</a></p>';
                echo '</div>';
                
                log_sistema("Instalação do CapivaraLearn concluída com sucesso", 'SUCCESS');
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Erro durante a instalação: ' . $e->getMessage() . '</div>';
                $log .= "❌ ERRO: " . $e->getMessage() . "\n";
                log_sistema("ERRO CRÍTICO durante instalação: " . $e->getMessage(), 'CRITICAL');
            }
            
            // Mostrar log
            echo '<div class="log">' . nl2br(htmlspecialchars($log)) . '</div>';
            echo '</div>';
        }
        ?>
        
        <?php endif; ?>
    </div>
</body>
</html>
