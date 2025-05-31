<?php
/**
 * CapivaraLearn - Instalador Automático
 * Execute este arquivo uma única vez para configurar o sistema
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CapivaraLearn - Instalação</title>
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
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .step {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ecf0f1;
            border-radius: 10px;
        }
        .step h3 {
            color: #3498db;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background: #2980b9;
        }
        .success {
            background: #d5f4e6;
            color: #27ae60;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            background: #fdf2f2;
            color: #e74c3c;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="header">
            <h1>🦫 CapivaraLearn</h1>
            <p>Instalador do Sistema de Organização de Estudos</p>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $host = $_POST['host'] ?? 'localhost';
            $user = $_POST['user'] ?? 'root';
            $pass = $_POST['pass'] ?? '';
            $dbname = $_POST['dbname'] ?? 'capivaralearn';
            
            echo '<div class="step">';
            echo '<h3>🔄 Executando Instalação...</h3>';
            
            $log = '';
            
            try {
                // Conectar ao MySQL (sem especificar banco ainda)
                $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
                echo '<div class="success">✅ Conexão com MySQL estabelecida</div>';
                $log .= "✅ Conectado ao MySQL\n";
                
                // Criar banco de dados
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$dbname`");
                echo '<div class="success">✅ Banco de dados criado/selecionado</div>';
                $log .= "✅ Banco '$dbname' criado/selecionado\n";
                
                // Definir comandos SQL inline (mais confiável)
                $sqlCommands = [
                    // Tabela de usuários
                    "CREATE TABLE IF NOT EXISTS usuarios (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nome VARCHAR(100) NOT NULL,
                        email VARCHAR(150) NOT NULL UNIQUE,
                        senha VARCHAR(255) NOT NULL,
                        avatar VARCHAR(255) DEFAULT NULL,
                        curso VARCHAR(100) DEFAULT 'Fisioterapia',
                        instituicao VARCHAR(150) DEFAULT NULL,
                        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_ultimo_acesso TIMESTAMP NULL,
                        ativo BOOLEAN DEFAULT TRUE,
                        INDEX idx_email (email),
                        INDEX idx_ativo (ativo)
                    ) ENGINE=InnoDB",
                    
                    // Tabela de módulos
                    "CREATE TABLE IF NOT EXISTS modulos (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        nome VARCHAR(200) NOT NULL,
                        codigo VARCHAR(50) DEFAULT NULL,
                        descricao TEXT DEFAULT NULL,
                        data_inicio DATE NOT NULL,
                        data_fim DATE NOT NULL,
                        cor VARCHAR(7) DEFAULT '#3498db',
                        ativo BOOLEAN DEFAULT TRUE,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        INDEX idx_usuario_modulo (usuario_id, ativo),
                        INDEX idx_datas (data_inicio, data_fim)
                    ) ENGINE=InnoDB",
                    
                    // Tabela de tópicos
                    "CREATE TABLE IF NOT EXISTS topicos (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        modulo_id INT NOT NULL,
                        nome VARCHAR(200) NOT NULL,
                        descricao TEXT DEFAULT NULL,
                        data_inicio DATE NOT NULL,
                        data_fim DATE NOT NULL,
                        data_fechamento DATETIME DEFAULT NULL,
                        concluido BOOLEAN DEFAULT FALSE,
                        nota DECIMAL(5,2) DEFAULT NULL,
                        observacoes TEXT DEFAULT NULL,
                        ordem INT DEFAULT 1,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE,
                        INDEX idx_modulo_topico (modulo_id, ordem),
                        INDEX idx_datas_topico (data_inicio, data_fim),
                        INDEX idx_status (concluido, data_fim)
                    ) ENGINE=InnoDB",
                    
                    // Tabela de atividades
                    "CREATE TABLE IF NOT EXISTS atividades (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        topico_id INT NOT NULL,
                        titulo VARCHAR(200) NOT NULL,
                        tipo ENUM('aula', 'atividade', 'prova', 'trabalho', 'seminario') DEFAULT 'aula',
                        descricao TEXT DEFAULT NULL,
                        data_entrega DATETIME DEFAULT NULL,
                        concluida BOOLEAN DEFAULT FALSE,
                        nota DECIMAL(5,2) DEFAULT NULL,
                        peso DECIMAL(3,2) DEFAULT 1.00,
                        url_material VARCHAR(500) DEFAULT NULL,
                        observacoes TEXT DEFAULT NULL,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (topico_id) REFERENCES topicos(id) ON DELETE CASCADE,
                        INDEX idx_topico_atividade (topico_id, data_entrega),
                        INDEX idx_tipo_status (tipo, concluida)
                    ) ENGINE=InnoDB",
                    
                    // Tabela de lembretes
                    "CREATE TABLE IF NOT EXISTS lembretes (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        topico_id INT DEFAULT NULL,
                        atividade_id INT DEFAULT NULL,
                        titulo VARCHAR(200) NOT NULL,
                        mensagem TEXT DEFAULT NULL,
                        data_lembrete DATETIME NOT NULL,
                        lido BOOLEAN DEFAULT FALSE,
                        tipo ENUM('deadline', 'inicio_topico', 'fim_topico', 'personalizado') DEFAULT 'personalizado',
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        FOREIGN KEY (topico_id) REFERENCES topicos(id) ON DELETE CASCADE,
                        FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE,
                        INDEX idx_usuario_lembrete (usuario_id, data_lembrete),
                        INDEX idx_pendentes (lido, data_lembrete)
                    ) ENGINE=InnoDB",
                    
                    // Tabela de sessões
                    "CREATE TABLE IF NOT EXISTS sessoes (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        token VARCHAR(255) NOT NULL UNIQUE,
                        ip_address VARCHAR(45) DEFAULT NULL,
                        user_agent TEXT DEFAULT NULL,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_expiracao TIMESTAMP NOT NULL,
                        ativo BOOLEAN DEFAULT TRUE,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        INDEX idx_token (token),
                        INDEX idx_usuario_sessao (usuario_id, ativo),
                        INDEX idx_expiracao (data_expiracao, ativo)
                    ) ENGINE=InnoDB",
                    
                    // Tabela de configurações
                    "CREATE TABLE IF NOT EXISTS configuracoes_usuario (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        tema ENUM('claro', 'escuro', 'auto') DEFAULT 'claro',
                        notificacoes_email BOOLEAN DEFAULT TRUE,
                        lembrete_antecedencia_dias INT DEFAULT 3,
                        timezone VARCHAR(50) DEFAULT 'America/Sao_Paulo',
                        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_config_usuario (usuario_id)
                    ) ENGINE=InnoDB"
                ];
                
                // Executar comandos de criação
                foreach ($sqlCommands as $index => $sql) {
                    try {
                        $pdo->exec($sql);
                        $tableName = preg_match('/CREATE TABLE[^`]*`?(\w+)`?/', $sql, $matches) ? $matches[1] : "comando " . ($index + 1);
                        $log .= "✅ Tabela '$tableName' criada\n";
                    } catch (Exception $e) {
                        $log .= "❌ Erro ao criar tabela: " . $e->getMessage() . "\n";
                    }
                }
                
                echo '<div class="success">✅ Estrutura do banco criada</div>';
                
                // Inserir dados de exemplo
                $dataCommands = [
                    // Usuário teste (senha: 123456)
                    "INSERT IGNORE INTO usuarios (nome, email, senha, curso, instituicao) VALUES 
                     ('Estudante Teste', 'teste@capivaralearn.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fisioterapia', 'Universidade Exemplo')",
                    
                    // Configuração padrão
                    "INSERT IGNORE INTO configuracoes_usuario (usuario_id) VALUES (1)",
                    
                    // Módulo de exemplo
                    "INSERT IGNORE INTO modulos (usuario_id, nome, codigo, descricao, data_inicio, data_fim) VALUES 
                     (1, 'MOD202/25 - FARMACOCINÉTICA E FARMACODINÂMICA', 'MOD202/25', 'Conceitos básicos de farmacologia aplicada à fisioterapia', '2025-05-05', '2025-07-06')",
                    
                    // Tópicos de exemplo
                    "INSERT IGNORE INTO topicos (modulo_id, nome, descricao, data_inicio, data_fim, ordem) VALUES 
                     (1, 'Tópico 1', 'Conceitos básicos de farmacologia', '2025-05-05', '2025-05-18', 1),
                     (1, 'Tópico 2', 'Farmacocinética avançada', '2025-05-19', '2025-06-01', 2),
                     (1, 'Tópico 3', 'Farmacodinâmica e interações medicamentosas', '2025-06-02', '2025-06-15', 3),
                     (1, 'Tópico 4', 'Aplicações clínicas em fisioterapia', '2025-06-16', '2025-06-29', 4)"
                ];
                
                foreach ($dataCommands as $sql) {
                    try {
                        $pdo->exec($sql);
                        $log .= "✅ Dados de exemplo inseridos\n";
                    } catch (Exception $e) {
                        $log .= "⚠️ Dados já existem ou erro: " . $e->getMessage() . "\n";
                    }
                }
                
                echo '<div class="success">✅ Dados de exemplo inseridos</div>';
                
                // Criar diretórios necessários
                $dirs = ['includes', 'public/assets/uploads', 'public/assets/uploads/avatars', 'backup', 'api'];
                foreach ($dirs as $dir) {
                    if (!is_dir($dir)) {
                        if (mkdir($dir, 0755, true)) {
                            $log .= "📁 Diretório criado: $dir\n";
                        } else {
                            $log .= "❌ Erro ao criar diretório: $dir\n";
                        }
                    } else {
                        $log .= "📁 Diretório já existe: $dir\n";
                    }
                }
                
                // Criar arquivo de configuração
                $configDir = __DIR__ . '/includes';
                if (!is_dir($configDir)) {
                    mkdir($configDir, 0755, true);
                }
                
                $configContent = "<?php
// CapivaraLearn - Configurações do Sistema
// Gerado automaticamente em " . date('Y-m-d H:i:s') . "

// Configurações do Banco de Dados
define('DB_HOST', '$host');
define('DB_NAME', '$dbname');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('DB_CHARSET', 'utf8mb4');

// Configurações da Aplicação
define('APP_NAME', 'CapivaraLearn');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['SCRIPT_NAME']));
define('TIMEZONE', 'America/Sao_Paulo');

// Configurações de Segurança
define('SECRET_KEY', '" . bin2hex(random_bytes(32)) . "');
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 dias
define('PASSWORD_MIN_LENGTH', 6);

// Configurar timezone
date_default_timezone_set(TIMEZONE);

// Classe de Conexão com Banco
class Database {
    private static \$instance = null;
    private \$connection;
    
    private function __construct() {
        try {
            \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;
            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            \$this->connection = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
        } catch (PDOException \$e) {
            die(\"Erro de conexão: \" . \$e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }
    
    public function getConnection() {
        return \$this->connection;
    }
    
    public function select(\$sql, \$params = []) {
        try {
            \$stmt = \$this->connection->prepare(\$sql);
            \$stmt->execute(\$params);
            return \$stmt->fetchAll();
        } catch (PDOException \$e) {
            error_log(\"Erro SQL SELECT: \" . \$e->getMessage());
            return false;
        }
    }
    
    public function execute(\$sql, \$params = []) {
        try {
            \$stmt = \$this->connection->prepare(\$sql);
            return \$stmt->execute(\$params);
        } catch (PDOException \$e) {
            error_log(\"Erro SQL EXECUTE: \" . \$e->getMessage());
            return false;
        }
    }
    
    public function lastInsertId() {
        return \$this->connection->lastInsertId();
    }
}

// Funções auxiliares
function hashPassword(\$password) {
    return password_hash(\$password, PASSWORD_DEFAULT);
}

function verifyPassword(\$password, \$hash) {
    return password_verify(\$password, \$hash);
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function formatDate(\$date, \$format = 'd/m/Y') {
    if (empty(\$date)) return '';
    \$dateObj = new DateTime(\$date);
    return \$dateObj->format(\$format);
}

function isLoggedIn() {
    return isset(\$_SESSION['user_id']) && !empty(\$_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function h(\$string) {
    return htmlspecialchars(\$string, ENT_QUOTES, 'UTF-8');
}

function jsonResponse(\$data, \$statusCode = 200) {
    http_response_code(\$statusCode);
    header('Content-Type: application/json');
    echo json_encode(\$data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Iniciar sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>";
                
                if (file_put_contents($configDir . '/config.php', $configContent)) {
                    echo '<div class="success">✅ Arquivo de configuração criado</div>';
                    $log .= "✅ Arquivo config.php criado\n";
                } else {
                    $log .= "❌ Erro ao criar config.php\n";
                }
                
                // Verificar se tudo funcionou
                $tables = $pdo->query("SHOW TABLES")->fetchAll();
                $log .= "✅ Total de tabelas criadas: " . count($tables) . "\n";
                
                echo '<div class="log">' . htmlspecialchars($log) . '</div>';
                
                echo '<h3>🎉 Instalação Concluída com Sucesso!</h3>';
                echo '<div class="success">';
                echo '<p><strong>Sistema CapivaraLearn instalado!</strong></p>';
                echo '<p><strong>Próximos passos:</strong></p>';
                echo '<ol>';
                echo '<li>Crie o arquivo <code>login.php</code> para acessar o sistema</li>';
                echo '<li>Use as credenciais de teste:</li>';
                echo '<ul>';
                echo '<li><strong>Email:</strong> teste@capivaralearn.com</li>';
                echo '<li><strong>Senha:</strong> 123456</li>';
                echo '</ul>';
                echo '<li>Remova este arquivo <code>install.php</code> após o teste</li>';
                echo '</ol>';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Erro durante a instalação:</div>';
                echo '<div class="error">' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '<div class="log">' . htmlspecialchars($log) . '</div>';
                echo '<p>Verifique as configurações do banco de dados e tente novamente.</p>';
            }
            
            echo '</div>';
        } else {
        ?>
        
        <form method="POST">
            <div class="step">
                <h3>📊 Configuração do Banco de Dados</h3>
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
                    <input type="password" name="pass" placeholder="Deixe vazio se não tiver senha">
                </div>
                <div class="form-group">
                    <label>Nome do Banco:</label>
                    <input type="text" name="dbname" value="capivaralearn" required>
                </div>
            </div>
            
            <button type="submit" class="btn">🚀 Instalar CapivaraLearn</button>
        </form>
        
        <div class="step">
            <h3>ℹ️ O que será instalado:</h3>
            <ul>
                <li>✅ Banco de dados com 7 tabelas</li>
                <li>✅ Usuário de teste: <code>teste@capivaralearn.com</code></li>
                <li>✅ Módulo de exemplo com 4 tópicos</li>
                <li>✅ Arquivo de configuração automático</li>
                <li>✅ Estrutura de diretórios</li>
            </ul>
        </div>
        
        <?php } ?>
    </div>
</body>
</html>