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

$dependencyCheck = checkDependencies();
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
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="header">
            <h1>CapivaraLearn - Instalação</h1>
            <p>Bem-vindo ao instalador do CapivaraLearn</p>
        </div>

        <!-- Verificação de Dependências -->
        <div class="step">
            <h3>Verificação de Dependências</h3>
            <?php if (!empty($dependencyCheck['messages'])): ?>
                <?php foreach ($dependencyCheck['messages'] as $message): ?>
                    <div class="<?php echo $message['type']; ?>">
                        <?php echo $message['message']; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="success">
                    Todas as dependências estão instaladas corretamente!
                </div>
            <?php endif; ?>
        </div>

        <!-- Resto do instalador -->
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
                // Conectar ao MySQL (sem especificar banco ainda)
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
                
                // Definir comandos SQL inline (mais confiável)
                $sqlCommands = [
                    // Tabela de usuários
                    "CREATE TABLE IF NOT EXISTS usuarios (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nome VARCHAR(100) NOT NULL,
                        email VARCHAR(150) NOT NULL UNIQUE,
                        senha VARCHAR(255) NOT NULL,
                        avatar VARCHAR(255) DEFAULT NULL,
                        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_ultimo_acesso TIMESTAMP NULL,
                        ativo BOOLEAN DEFAULT TRUE,
                        email_verificado BOOLEAN DEFAULT FALSE,
                        data_verificacao TIMESTAMP NULL,
                        INDEX idx_email (email),
                        INDEX idx_ativo (ativo)
                    ) ENGINE=InnoDB",
                    
                    // Tabela de universidades
                    "CREATE TABLE IF NOT EXISTS universidades (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nome VARCHAR(150) NOT NULL,
                        sigla VARCHAR(20),
                        cidade VARCHAR(100),
                        estado VARCHAR(2),
                        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        ativo BOOLEAN DEFAULT TRUE,
                        INDEX idx_nome (nome),
                        INDEX idx_sigla (sigla)
                    ) ENGINE=InnoDB",

                    // Tabela de relacionamento universidade-curso
                    "CREATE TABLE IF NOT EXISTS universidade_curso (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        universidade_id INT NOT NULL,
                        curso_id INT NOT NULL,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        ativo BOOLEAN DEFAULT TRUE,
                        FOREIGN KEY (universidade_id) REFERENCES universidades(id) ON DELETE CASCADE,
                        FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_universidade_curso (universidade_id, curso_id),
                        INDEX idx_ativo (ativo)
                    ) ENGINE=InnoDB",

                    // Tabela de cursos
                    "CREATE TABLE IF NOT EXISTS cursos (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nome VARCHAR(100) NOT NULL,
                        area VARCHAR(50),
                        nivel ENUM('graduacao', 'pos_graduacao', 'mestrado', 'doutorado', 'outros') DEFAULT 'graduacao',
                        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        ativo BOOLEAN DEFAULT TRUE,
                        INDEX idx_nome (nome),
                        INDEX idx_area (area)
                    ) ENGINE=InnoDB",

                    // Tabela de vínculo usuário-curso-universidade
                    "CREATE TABLE IF NOT EXISTS usuario_curso_universidade (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        curso_id INT NOT NULL,
                        universidade_id INT NOT NULL,
                        data_inicio DATE,
                        data_fim DATE,
                        situacao ENUM('cursando', 'trancado', 'concluido', 'abandonado') DEFAULT 'cursando',
                        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
                        FOREIGN KEY (universidade_id) REFERENCES universidades(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_vinculo (usuario_id, curso_id, universidade_id),
                        INDEX idx_situacao (situacao)
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
                    
                    // Tabela de tokens de email
                    "CREATE TABLE IF NOT EXISTS email_tokens (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        token VARCHAR(255) NOT NULL UNIQUE,
                        tipo ENUM('confirmacao', 'recuperacao_senha') NOT NULL,
                        usado BOOLEAN DEFAULT FALSE,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_expiracao TIMESTAMP NOT NULL,
                        ip_address VARCHAR(45) DEFAULT NULL,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        INDEX idx_token (token),
                        INDEX idx_usuario_token (usuario_id, tipo, usado),
                        INDEX idx_expiracao (data_expiracao, usado)
                    ) ENGINE=InnoDB",

                    // Tabela de arquivos
                    "CREATE TABLE IF NOT EXISTS arquivos (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nome_original VARCHAR(255) NOT NULL,
                        nome_arquivo VARCHAR(255) NOT NULL,
                        caminho VARCHAR(500) NOT NULL,
                        tipo VARCHAR(100) NOT NULL,
                        tamanho BIGINT NOT NULL,
                        data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        usuario_id INT,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
                        INDEX idx_usuario (usuario_id),
                        INDEX idx_data (data_upload)
                    ) ENGINE=InnoDB",

                    // Tabela de relacionamento tópico-arquivo
                    "CREATE TABLE IF NOT EXISTS topico_arquivo (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        topico_id INT NOT NULL,
                        arquivo_id INT NOT NULL,
                        data_anexo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (topico_id) REFERENCES topicos(id) ON DELETE CASCADE,
                        FOREIGN KEY (arquivo_id) REFERENCES arquivos(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_topico_arquivo (topico_id, arquivo_id),
                        INDEX idx_data_anexo (data_anexo)
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
                    ) ENGINE=InnoDB",
                    
                    // Tabela de logs de atividade
                    "CREATE TABLE IF NOT EXISTS logs_atividade (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT DEFAULT NULL,
                        acao VARCHAR(100) NOT NULL,
                        descricao TEXT DEFAULT NULL,
                        data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        ip_address VARCHAR(45) DEFAULT NULL,
                        user_agent TEXT DEFAULT NULL,
                        INDEX idx_usuario_atividade (usuario_id, data_hora),
                        INDEX idx_acao (acao),
                        INDEX idx_data (data_hora)
                    ) ENGINE=InnoDB",
                    
                    // Tabela de log de emails
                    "CREATE TABLE IF NOT EXISTS email_log (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        destinatario VARCHAR(255) NOT NULL,
                        assunto VARCHAR(255) NOT NULL,
                        tipo ENUM('confirmacao', 'recuperacao', 'notificacao') NOT NULL,
                        status ENUM('enviado', 'falhou', 'pendente') DEFAULT 'pendente',
                        erro TEXT DEFAULT NULL,
                        data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        token VARCHAR(255) DEFAULT NULL,
                        INDEX idx_destinatario (destinatario),
                        INDEX idx_status (status),
                        INDEX idx_data (data_envio),
                        INDEX idx_token (token)
                    ) ENGINE=InnoDB"
                ];
                
                // Executar comandos de criação
                foreach ($sqlCommands as $index => $sql) {
                    try {
                        $pdo->exec($sql);
                        $tableName = preg_match('/CREATE TABLE[^`]*`?(\w+)`?/', $sql, $matches) ? $matches[1] : "comando " . ($index + 1);
                        $log .= "✅ Tabela '$tableName' criada\n";
                        log_sistema("Tabela '$tableName' criada com sucesso", 'SUCCESS');
                    } catch (Exception $e) {
                        $log .= "❌ Erro ao criar tabela: " . $e->getMessage() . "\n";
                        log_sistema("ERRO ao criar tabela: " . $e->getMessage(), 'ERROR');
                    }
                }
                
                echo '<div class="success">✅ Estrutura do banco criada</div>';
                log_sistema("Estrutura completa do banco de dados criada com sucesso", 'SUCCESS');
                
                // Inserir dados de exemplo
                $dataCommands = [
                    // Usuário teste (senha: 123456)
                    "INSERT IGNORE INTO usuarios (nome, email, senha) VALUES 
                     ('Estudante Teste', 'teste@capivaralearn.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')",
                    
                    // Universidade de exemplo
                    "INSERT IGNORE INTO universidades (nome, sigla, cidade, estado) VALUES
                     ('Universidade Federal de São Paulo', 'UNIFESP', 'São Paulo', 'SP'),
                     ('Universidade de São Paulo', 'USP', 'São Paulo', 'SP')",
                    
                    // Cursos de exemplo
                    "INSERT IGNORE INTO cursos (nome, area, nivel) VALUES
                     ('Fisioterapia', 'Saúde', 'graduacao'),
                     ('Especialização em Fisioterapia Esportiva', 'Saúde', 'pos_graduacao')",
                    
                    // Vínculo de exemplo
                    "INSERT IGNORE INTO usuario_curso_universidade (usuario_id, curso_id, universidade_id, data_inicio, situacao) VALUES
                     (1, 1, 1, '2025-01-01', 'cursando')",
                    
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
                        log_sistema("Dados de exemplo inseridos com sucesso", 'SUCCESS');
                    } catch (Exception $e) {
                        $log .= "⚠️ Dados já existem ou erro: " . $e->getMessage() . "\n";
                        log_sistema("Aviso ao inserir dados de exemplo: " . $e->getMessage(), 'WARNING');
                    }
                }
                
                echo '<div class="success">✅ Dados de exemplo inseridos</div>';
                log_sistema("Dados de exemplo inseridos no banco de dados", 'SUCCESS');
                
                // Criar diretórios necessários
                $dirs = ['includes', 'public/assets/uploads', 'public/assets/uploads/avatars', 'backup', 'api'];
                foreach ($dirs as $dir) {
                    if (!is_dir($dir)) {
                        if (mkdir($dir, 0755, true)) {
                            $log .= "📁 Diretório criado: $dir\n";
                            log_sistema("Diretório criado: $dir", 'SUCCESS');
                        } else {
                            $log .= "❌ Erro ao criar diretório: $dir\n";
                            log_sistema("ERRO ao criar diretório: $dir", 'ERROR');
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

// =============================================
// DETECTAR AMBIENTE PRIMEIRO
// =============================================

// Tentar carregar configuração do arquivo .ini
\$envFile = __DIR__ . '/environment.ini';
\$config = null;

if (file_exists(\$envFile)) {
    \$config = parse_ini_file(\$envFile, true);
    \$isProduction = isset(\$config['environment']['environment']) && 
                   strtolower(\$config['environment']['environment']) === 'production';
} else {
    // Fallback para detecção automática baseada no domínio
    \$isProduction = (isset(\$_SERVER['HTTP_HOST']) && strpos(\$_SERVER['HTTP_HOST'], 'capivaralearn.com.br') !== false);
}

// =============================================
// CONFIGURAÇÕES DE SESSÃO (ANTES de session_start)
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    // Configurar parâmetros de sessão apenas se sessão não estiver ativa
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.use_only_cookies', 1);
    
    if (\$isProduction) {
        @ini_set('session.cookie_secure', 1);
        @ini_set('session.cookie_samesite', 'Strict');
    }
    
    session_start();
}

// =============================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// =============================================
define('DB_HOST', '$host');
define('DB_NAME', '$dbname');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('DB_CHARSET', 'utf8mb4');

// =============================================
// CONFIGURAÇÕES BASEADAS NO AMBIENTE
// =============================================
if (\$isProduction) {
    // CONFIGURAÇÕES DE PRODUÇÃO
    define('APP_URL', 'https://capivaralearn.com.br');
    define('APP_ENV', 'production');
    define('DEBUG_MODE', false);
    
    // Forçar HTTPS apenas se não estiver em CLI
    if (php_sapi_name() !== 'cli') {
        if (!isset(\$_SERVER['HTTPS']) || \$_SERVER['HTTPS'] !== 'on') {
            \$redirect_url = 'https://' . \$_SERVER['HTTP_HOST'] . \$_SERVER['REQUEST_URI'];
            header(\"Location: \$redirect_url\", true, 301);
            exit();
        }
    }
} else {
    // CONFIGURAÇÕES DE DESENVOLVIMENTO
    define('APP_URL', 'http://localhost/CapivaraLearn');
    define('APP_ENV', 'development');
    define('DEBUG_MODE', true);
}

define('TIMEZONE', 'America/Sao_Paulo');

// =============================================
// CONFIGURAÇÕES DE SEGURANÇA
// =============================================
define('SECRET_KEY', '" . bin2hex(random_bytes(32)) . "');
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 dias
define('PASSWORD_MIN_LENGTH', 6);

// =============================================
// CONFIGURAÇÕES DA APLICAÇÃO
// =============================================
define('APP_NAME', 'CapivaraLearn');
define('APP_VERSION', '1.0.0');

// Configurar timezone
date_default_timezone_set(TIMEZONE);

// =============================================
// CONFIGURAÇÕES DE EMAIL
// =============================================
if (\$config && isset(\$config[APP_ENV])) {
    \$envConfig = \$config[APP_ENV];
    define('MAIL_HOST', \$envConfig['mail_host'] ?? 'localhost');
    define('MAIL_PORT', \$envConfig['mail_port'] ?? 587);
    define('MAIL_USERNAME', \$envConfig['mail_username'] ?? '');
    define('MAIL_PASSWORD', \$envConfig['mail_password'] ?? '');
    define('MAIL_FROM_NAME', \$envConfig['mail_from_name'] ?? 'CapivaraLearn');
    define('MAIL_FROM_EMAIL', \$envConfig['mail_from_email'] ?? 'noreply@capivaralearn.com.br');
    define('MAIL_SECURE', \$envConfig['mail_secure'] ?? 'tls');
    define('MAIL_AUTH', \$envConfig['mail_auth'] ?? true);
} else {
    // Configurações padrão para desenvolvimento - SEM ENVIO DE EMAIL
    define('MAIL_HOST', 'localhost');
    define('MAIL_PORT', 587);
    define('MAIL_USERNAME', '');
    define('MAIL_PASSWORD', '');
    define('MAIL_FROM_NAME', 'CapivaraLearn (Dev)');
    define('MAIL_FROM_EMAIL', 'noreply@localhost');
    define('MAIL_SECURE', 'tls');
    define('MAIL_AUTH', false);
}

// =============================================
// CLASSE DE CONEXÃO COM BANCO - VERSÃO COMPLETA
// =============================================
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
    
    // MÉTODOS ADICIONADOS PARA COMPATIBILIDADE
    public function insert(\$table, \$data) {
        try {
            \$columns = array_keys(\$data);
            \$placeholders = array_map(function(\$col) { return ':' . \$col; }, \$columns);
            
            \$sql = \"INSERT INTO \$table (\" . implode(', ', \$columns) . \") VALUES (\" . implode(', ', \$placeholders) . \")\";
            
            \$stmt = \$this->connection->prepare(\$sql);
            
            // Bind dos parâmetros
            foreach (\$data as \$column => \$value) {
                \$stmt->bindValue(':' . \$column, \$value);
            }
            
            \$result = \$stmt->execute();
            
            if (DEBUG_MODE) {
                error_log(\"Database INSERT - Tabela: \$table, Dados: \" . json_encode(\$data));
            }
            
            return \$result;
        } catch (PDOException \$e) {
            error_log(\"Erro SQL INSERT: \" . \$e->getMessage() . \" - Tabela: \$table\");
            if (DEBUG_MODE) {
                error_log(\"Dados do INSERT: \" . json_encode(\$data));
            }
            return false;
        }
    }
    
    public function update(\$table, \$data, \$where, \$whereParams = []) {
        try {
            \$setClause = [];
            foreach (array_keys(\$data) as \$column) {
                \$setClause[] = \"\$column = :\$column\";
            }
            
            \$sql = \"UPDATE \$table SET \" . implode(', ', \$setClause) . \" WHERE \$where\";
            
            \$stmt = \$this->connection->prepare(\$sql);
            
            // Bind dos dados
            foreach (\$data as \$column => \$value) {
                \$stmt->bindValue(':' . \$column, \$value);
            }
            
            // Bind dos parâmetros WHERE
            foreach (\$whereParams as \$param => \$value) {
                \$stmt->bindValue(\$param, \$value);
            }
            
            \$result = \$stmt->execute();
            
            if (DEBUG_MODE) {
                error_log(\"Database UPDATE - Tabela: \$table, WHERE: \$where\");
            }
            
            return \$result;
        } catch (PDOException \$e) {
            error_log(\"Erro SQL UPDATE: \" . \$e->getMessage() . \" - Tabela: \$table\");
            return false;
        }
    }
    
    public function lastInsertId() {
        return \$this->connection->lastInsertId();
    }
}

// =============================================
// FUNÇÕES AUXILIARES
// =============================================
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

// =============================================
// FUNÇÕES DE LOG E ATIVIDADE
// =============================================
function logActivity(\$action, \$description = '', \$userId = null) {
    try {
        \$db = Database::getInstance();
        \$sql = \"INSERT INTO logs_atividade (usuario_id, acao, descricao, data_hora, ip_address, user_agent) 
                VALUES (:usuario_id, :acao, :descricao, NOW(), :ip, :user_agent)\";
        
        \$params = [
            ':usuario_id' => \$userId ?? (\$_SESSION['user_id'] ?? null),
            ':acao' => \$action,
            ':descricao' => \$description,
            ':ip' => \$_SERVER['REMOTE_ADDR'] ?? 'CLI',
            ':user_agent' => \$_SERVER['HTTP_USER_AGENT'] ?? 'CLI'
        ];
        
        \$db->execute(\$sql, \$params);
    } catch (Exception \$e) {
        error_log('Erro ao registrar atividade: ' . \$e->getMessage());
    }
}

function logError(\$message, \$context = []) {
    \$logMessage = '[' . date('Y-m-d H:i:s') . '] ERROR: ' . \$message;
    if (!empty(\$context)) {
        \$logMessage .= ' Context: ' . json_encode(\$context);
    }
    error_log(\$logMessage);
}

// =============================================
// CONFIGURAÇÕES DE ERRO
// =============================================
if (APP_ENV === 'production') {
    // Produção - Não mostrar erros
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
} else {
    // Desenvolvimento - Mostrar todos os erros
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Configurar timezone
date_default_timezone_set(TIMEZONE);

// Iniciar sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =============================================
// CLASSE DE SERVIÇO DE EMAIL - CORRIGIDA
// =============================================
require_once __DIR__ . \"/../vendor/autoload.php\";

use PHPMailer\\PHPMailer\\PHPMailer;
use PHPMailer\\PHPMailer\\SMTP;
use PHPMailer\\PHPMailer\\Exception;

class MailService {
    private static \$instance = null;
    private \$lastError = '';
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }
    
    public function sendConfirmationEmail(\$email, \$name, \$token) {
        try {
            // Em desenvolvimento, simular envio
            if (APP_ENV === 'development' && MAIL_HOST === 'localhost') {
                // Log do email que seria enviado
                error_log(\"=== EMAIL SIMULADO (DESENVOLVIMENTO) ===\");
                error_log(\"Para: \$email\");
                error_log(\"Nome: \$name\");
                error_log(\"Token: \$token\");
                error_log(\"URL de confirmação: \" . APP_URL . \"/confirm_email.php?token=\$token\");
                error_log(\"=== FIM EMAIL SIMULADO ===\");
                
                return true; // Simular sucesso
            }
            
            \$mail = new PHPMailer(true);
            
            // Configurações SMTP usando constantes
            \$mail->isSMTP();
            \$mail->Host = MAIL_HOST;
            \$mail->SMTPAuth = MAIL_AUTH;
            \$mail->Username = MAIL_USERNAME;
            \$mail->Password = MAIL_PASSWORD;
            \$mail->SMTPSecure = MAIL_SECURE;
            \$mail->Port = MAIL_PORT;
            
            // Remetente
            \$mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            
            // Destinatário
            \$mail->addAddress(\$email, \$name);
            
            // Conteúdo
            \$mail->isHTML(true);
            \$mail->Subject = 'Confirme seu cadastro - ' . APP_NAME;
            
            \$confirmUrl = APP_URL . '/confirm_email.php?token=' . \$token;
            \$mail->Body = \$this->getConfirmationEmailTemplate(\$name, \$confirmUrl);
            
            if (\$mail->send()) {
                return true;
            } else {
                \$this->lastError = 'Erro ao enviar email: ' . \$mail->ErrorInfo;
                return false;
            }
        } catch (Exception \$e) {
            \$this->lastError = 'Erro no MailService: ' . \$e->getMessage();
            return false;
        }
    }
    
    public function getLastError() {
        return \$this->lastError;
    }
    
    public function getConfig() {
        return [
            'host' => MAIL_HOST,
            'port' => MAIL_PORT,
            'user' => MAIL_USERNAME,
            'from_name' => MAIL_FROM_NAME,
            'from_email' => MAIL_FROM_EMAIL,
            'secure' => MAIL_SECURE,
            'auth' => MAIL_AUTH
        ];
    }
    
    private function getConfirmationEmailTemplate(\$nome, \$confirmUrl) {
        return \"
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #27ae60; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🦫 CapivaraLearn</h1>
                    <p>Bem-vindo(a) ao sistema!</p>
                </div>
                <div class='content'>
                    <h2>Olá, {\$nome}!</h2>
                    <p>Obrigado por se cadastrar no CapivaraLearn. Para ativar sua conta, clique no botão abaixo:</p>
                    <div style='text-align: center;'>
                        <a href='{\$confirmUrl}' class='button'>✅ Confirmar Cadastro</a>
                    </div>
                    <p><strong>Ou copie e cole este link no seu navegador:</strong></p>
                    <p style='word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 5px;'>{\$confirmUrl}</p>
                    <div class='footer'>
                        <p>Este email foi enviado automaticamente. Não responda.</p>
                        <p>© 2025 CapivaraLearn - Sistema de Organização de Estudos</p>
                    </div>
                </div>
            </div>
        </body>
        </html>\";
    }
}

// =============================================
// LOG INICIAL DO SISTEMA
// =============================================
if (DEBUG_MODE) {
    logActivity('config_loaded', 'Sistema inicializado para ' . APP_ENV);
}

/*
 * ===============================================
 * 🔚 FIM DO ARQUIVO DE CONFIGURAÇÃO
 * ===============================================
 * 
 * ⚠️  LEMBRE-SE: Este arquivo foi gerado automaticamente!
 * 
 * Para modificar configurações:
 * 1. Edite 'includes/environment.ini' para configurações de ambiente
 * 2. Edite 'install.php' para modificações permanentes no template
 * 3. Execute nova instalação para aplicar as mudanças
 * 
 * 📧 Suporte: https://github.com/seu-usuario/CapivaraLearn
 * ===============================================
 */
?>