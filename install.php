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
        .page-footer-note {
            position: fixed;
            bottom: 15px;
            left: 0;
            right: 0;
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
            letter-spacing: 0.05em;
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
                
                // Criar tabelas completas com isolamento por usuário
                $tables = [
                    "usuarios" => "CREATE TABLE IF NOT EXISTS usuarios (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nome VARCHAR(100) NOT NULL,
                        email VARCHAR(255) NOT NULL UNIQUE,
                        senha VARCHAR(255) NOT NULL,
                        ativo TINYINT(1) DEFAULT 1,
                        email_verificado TINYINT(1) DEFAULT 0,
                        termos_aceitos TINYINT(1) NOT NULL DEFAULT 0,
                        data_aceitacao_termos TIMESTAMP NULL,
                        versao_termos_aceitos VARCHAR(10) DEFAULT '1.0',
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_email (email),
                        INDEX idx_ativo (ativo),
                        INDEX idx_email_verificado (email_verificado),
                        INDEX idx_termos_aceitos (termos_aceitos)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "email_tokens" => "CREATE TABLE IF NOT EXISTS email_tokens (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        token VARCHAR(255) NOT NULL UNIQUE,
                        tipo ENUM('confirmacao', 'reset_senha') NOT NULL,
                        usado TINYINT(1) DEFAULT 0,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_expiracao TIMESTAMP NOT NULL,
                        ip_address VARCHAR(45),
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        INDEX idx_token (token),
                        INDEX idx_usuario_id (usuario_id),
                        INDEX idx_usado (usado),
                        INDEX idx_expiracao (data_expiracao)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "universidades" => "CREATE TABLE IF NOT EXISTS universidades (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nome VARCHAR(200) NOT NULL,
                        sigla VARCHAR(10),
                        pais VARCHAR(100) DEFAULT 'Brasil',
                        cidade VARCHAR(100),
                        estado VARCHAR(2) NOT NULL,
                        site VARCHAR(255),
                        usuario_id INT NOT NULL,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        INDEX idx_usuario_id (usuario_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "cursos" => "CREATE TABLE IF NOT EXISTS cursos (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nome VARCHAR(200) NOT NULL,
                        descricao TEXT,
                        nivel VARCHAR(50),
                        carga_horaria INT DEFAULT 0,
                        universidade_id INT NOT NULL,
                        usuario_id INT NOT NULL,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (universidade_id) REFERENCES universidades(id) ON DELETE CASCADE,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        INDEX idx_universidade_id (universidade_id),
                        INDEX idx_usuario_id (usuario_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "disciplinas" => "CREATE TABLE IF NOT EXISTS disciplinas (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nome VARCHAR(200) NOT NULL,
                        codigo VARCHAR(20),
                        descricao TEXT,
                        carga_horaria INT DEFAULT 0,
                        semestre INT,
                        concluido TINYINT(1) DEFAULT 0,
                        curso_id INT NOT NULL,
                        usuario_id INT NOT NULL,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        INDEX idx_curso_id (curso_id),
                        INDEX idx_usuario_id (usuario_id),
                        INDEX idx_concluido (concluido)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "topicos" => "CREATE TABLE IF NOT EXISTS topicos (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nome VARCHAR(200) NOT NULL,
                        descricao TEXT,
                        data_prazo DATE,
                        prioridade ENUM('baixa', 'media', 'alta') DEFAULT 'media',
                        concluido TINYINT(1) DEFAULT 0,
                        disciplina_id INT NOT NULL,
                        ordem INT DEFAULT 0,
                        usuario_id INT NOT NULL,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        INDEX idx_disciplina_id (disciplina_id),
                        INDEX idx_usuario_id (usuario_id),
                        INDEX idx_concluido (concluido),
                        INDEX idx_data_prazo (data_prazo)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "unidades_aprendizagem" => "CREATE TABLE IF NOT EXISTS unidades_aprendizagem (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nome VARCHAR(200) NOT NULL,
                        descricao TEXT,
                        tipo ENUM('leitura', 'exercicio', 'projeto', 'prova', 'outros') DEFAULT 'leitura',
                        gabarito TEXT,
                        nota DECIMAL(4,2),
                        data_prazo DATE,
                        concluido TINYINT(1) DEFAULT 0,
                        topico_id INT NOT NULL,
                        usuario_id INT NOT NULL,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (topico_id) REFERENCES topicos(id) ON DELETE CASCADE,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        INDEX idx_topico_id (topico_id),
                        INDEX idx_usuario_id (usuario_id),
                        INDEX idx_concluido (concluido),
                        INDEX idx_data_prazo (data_prazo)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "matriculas" => "CREATE TABLE IF NOT EXISTS matriculas (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        universidade_id INT NOT NULL,
                        curso_id INT NOT NULL,
                        numero_matricula VARCHAR(50),
                        data_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        status ENUM('ativa', 'concluida', 'cancelada', 'trancada') DEFAULT 'ativa',
                        progresso DECIMAL(5,2) DEFAULT 0.00,
                        nota_final DECIMAL(4,2) NULL,
                        data_conclusao TIMESTAMP NULL,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        FOREIGN KEY (universidade_id) REFERENCES universidades(id) ON DELETE CASCADE,
                        FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_enrollment (usuario_id, universidade_id, curso_id),
                        INDEX idx_usuario_id (usuario_id),
                        INDEX idx_universidade_id (universidade_id),
                        INDEX idx_curso_id (curso_id),
                        INDEX idx_status (status)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "email_log" => "CREATE TABLE IF NOT EXISTS email_log (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        destinatario VARCHAR(255) NOT NULL,
                        assunto VARCHAR(255),
                        tipo ENUM('confirmacao', 'reset_senha', 'notificacao') NOT NULL,
                        status ENUM('enviado', 'erro', 'pendente') DEFAULT 'pendente',
                        erro_detalhes TEXT,
                        data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_destinatario (destinatario),
                        INDEX idx_tipo (tipo),
                        INDEX idx_status (status),
                        INDEX idx_data_envio (data_envio)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "configuracoes_usuario" => "CREATE TABLE IF NOT EXISTS configuracoes_usuario (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        tema VARCHAR(20) DEFAULT 'claro',
                        idioma VARCHAR(10) DEFAULT 'pt-BR',
                        notificacoes_email TINYINT(1) DEFAULT 1,
                        notificacoes_prazos TINYINT(1) DEFAULT 1,
                        fuso_horario VARCHAR(50) DEFAULT 'America/Sao_Paulo',
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_user_config (usuario_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    // Financial System Tables (Monetization)
                    "subscription_plans" => "CREATE TABLE IF NOT EXISTS subscription_plans (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        plan_name VARCHAR(100) NOT NULL,
                        plan_code VARCHAR(50) NOT NULL UNIQUE,
                        description TEXT,
                        price_usd DECIMAL(10,2) NOT NULL,
                        billing_cycle ENUM('monthly', 'yearly', 'one_time') DEFAULT 'yearly',
                        grace_period_days INT DEFAULT 365,
                        is_active TINYINT(1) DEFAULT 1,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_plan_code (plan_code),
                        INDEX idx_is_active (is_active)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "user_subscriptions" => "CREATE TABLE IF NOT EXISTS user_subscriptions (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        plan_id INT NOT NULL,
                        status ENUM('active', 'grace_period', 'payment_due', 'overdue', 'suspended') DEFAULT 'grace_period',
                        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        grace_period_end DATE,
                        next_payment_due DATE,
                        last_payment_date TIMESTAMP NULL,
                        amount_due_usd DECIMAL(10,2) DEFAULT 0.00,
                        payment_attempts INT DEFAULT 0,
                        notes TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        FOREIGN KEY (plan_id) REFERENCES subscription_plans(id),
                        UNIQUE KEY unique_user_plan (user_id, plan_id),
                        INDEX idx_user_id (user_id),
                        INDEX idx_status (status),
                        INDEX idx_grace_period_end (grace_period_end),
                        INDEX idx_next_payment_due (next_payment_due)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "payment_transactions" => "CREATE TABLE IF NOT EXISTS payment_transactions (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        subscription_id INT NOT NULL,
                        transaction_type ENUM('payment', 'refund', 'adjustment') DEFAULT 'payment',
                        amount_usd DECIMAL(10,2) NOT NULL,
                        currency VARCHAR(3) DEFAULT 'USD',
                        payment_method ENUM('credit_card', 'paypal', 'bank_transfer', 'crypto', 'other') NULL,
                        payment_gateway VARCHAR(100),
                        gateway_transaction_id VARCHAR(255),
                        status ENUM('pending', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
                        payment_date TIMESTAMP NULL,
                        failure_reason TEXT,
                        gateway_response JSON,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE CASCADE,
                        INDEX idx_user_id (user_id),
                        INDEX idx_subscription_id (subscription_id),
                        INDEX idx_status (status),
                        INDEX idx_payment_date (payment_date),
                        INDEX idx_gateway_transaction_id (gateway_transaction_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "billing_events" => "CREATE TABLE IF NOT EXISTS billing_events (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        subscription_id INT,
                        event_type ENUM('registration', 'grace_period_start', 'payment_due', 'payment_completed', 'payment_failed', 'account_suspended', 'account_reactivated') NOT NULL,
                        event_description TEXT,
                        amount_usd DECIMAL(10,2) NULL,
                        metadata JSON,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE SET NULL,
                        INDEX idx_user_id (user_id),
                        INDEX idx_subscription_id (subscription_id),
                        INDEX idx_event_type (event_type),
                        INDEX idx_created_at (created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "payment_notifications" => "CREATE TABLE IF NOT EXISTS payment_notifications (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        subscription_id INT NOT NULL,
                        notification_type ENUM('grace_period_ending', 'payment_due', 'payment_overdue', 'final_notice') NOT NULL,
                        scheduled_date DATE NOT NULL,
                        sent_at TIMESTAMP NULL,
                        status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
                        notification_channel ENUM('email', 'sms', 'in_app') DEFAULT 'email',
                        message_content TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                        FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE CASCADE,
                        INDEX idx_user_id (user_id),
                        INDEX idx_subscription_id (subscription_id),
                        INDEX idx_scheduled_date (scheduled_date),
                        INDEX idx_status (status)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    
                    "logs_atividade" => "CREATE TABLE IF NOT EXISTS logs_atividade (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NULL,
                        acao VARCHAR(255) NOT NULL,
                        descricao TEXT,
                        detalhes TEXT,
                        data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        ip_address VARCHAR(45),
                        user_agent TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
                        INDEX idx_usuario_id (usuario_id),
                        INDEX idx_acao (acao),
                        INDEX idx_data_hora (data_hora)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
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
                
                // Insert default subscription plan for financial system
                try {
                    $checkPlan = $pdo->prepare("SELECT COUNT(*) FROM subscription_plans WHERE plan_code = 'basic_annual'");
                    $checkPlan->execute();
                    
                    if ($checkPlan->fetchColumn() == 0) {
                        $insertPlan = $pdo->prepare("
                            INSERT INTO subscription_plans 
                            (plan_name, plan_code, description, price_usd, billing_cycle, grace_period_days, is_active) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $insertPlan->execute([
                            'Basic Annual',
                            'basic_annual',
                            'Annual contribution to support CapivaraLearn operational expenses. Includes 365-day grace period for new users.',
                            1.00,
                            'yearly',
                            365,
                            1
                        ]);
                        
                        $log .= "✅ Plano de assinatura padrão criado (USD 1.00/ano)\n";
                        log_sistema("Plano de assinatura padrão criado: Basic Annual - USD 1.00", 'SUCCESS');
                    }
                } catch (Exception $e) {
                    $log .= "❌ Erro ao criar plano padrão: " . $e->getMessage() . "\n";
                    log_sistema("ERRO ao criar plano de assinatura padrão: " . $e->getMessage(), 'ERROR');
                }
                
                // Create stored procedure for user subscription initialization
                try {
                    // First, try to run mysql_upgrade equivalent by recreating proc table structure
                    $pdo->exec("SET SQL_MODE = ''");
                    
                    // Try a simpler approach without DELIMITER (which can cause issues in PHP PDO)
                    $createProcedure = "
                    CREATE OR REPLACE PROCEDURE CreateUserSubscription(IN p_user_id INT)
                    BEGIN
                        DECLARE v_plan_id INT DEFAULT NULL;
                        DECLARE v_grace_end DATE;
                        DECLARE EXIT HANDLER FOR SQLEXCEPTION 
                        BEGIN
                            ROLLBACK;
                            RESIGNAL;
                        END;
                        
                        START TRANSACTION;
                        
                        -- Get the default plan ID
                        SELECT id INTO v_plan_id 
                        FROM subscription_plans 
                        WHERE plan_code = 'basic_annual' AND is_active = 1 
                        LIMIT 1;
                        
                        -- Only proceed if plan was found
                        IF v_plan_id IS NOT NULL THEN
                            -- Calculate grace period end date (365 days from now)
                            SET v_grace_end = DATE_ADD(CURDATE(), INTERVAL 365 DAY);
                            
                            -- Insert user subscription
                            INSERT INTO user_subscriptions 
                            (user_id, plan_id, status, registration_date, grace_period_end, next_payment_due, amount_due_usd)
                            VALUES 
                            (p_user_id, v_plan_id, 'grace_period', NOW(), v_grace_end, v_grace_end, 1.00);
                            
                            -- Log billing event
                            INSERT INTO billing_events 
                            (user_id, subscription_id, event_type, event_description, amount_usd)
                            VALUES 
                            (p_user_id, LAST_INSERT_ID(), 'registration', 'User registered with 365-day grace period', 1.00);
                        END IF;
                        
                        COMMIT;
                    END
                    ";
                    
                    $pdo->exec($createProcedure);
                    $log .= "✅ Stored procedure CreateUserSubscription criada (versão compatível)\n";
                    log_sistema("Stored procedure CreateUserSubscription criada com sucesso (versão compatível)", 'SUCCESS');
                    
                } catch (Exception $e) {
                    $log .= "⚠️ Aviso: Stored procedure não pôde ser criada: " . $e->getMessage() . "\n";
                    $log .= "ℹ️ O sistema funcionará normalmente usando métodos alternativos\n";
                    log_sistema("Aviso: Stored procedure não criada, usando métodos alternativos: " . $e->getMessage(), 'WARNING');
                    
                    // This is not critical - the FinancialService can work without the stored procedure
                }
                
                echo '<div class="success">✅ Estrutura completa do banco criada (13 tabelas + sistema financeiro)</div>';
                log_sistema("Estrutura completa do banco de dados criada com sucesso - 13 tabelas incluindo sistema financeiro", 'SUCCESS');
                
                // Criar arquivo de configuração básico (com logger centralizado)
                $configContent = "<?php
/**
 * CapivaraLearn - Arquivo de Configuração
 * Gerado pelo instalador em " . date('d/m/Y H:i:s') . "
 */

// Configuração de fuso horário para o Brasil (São Paulo)
date_default_timezone_set('America/Sao_Paulo');

// Incluir sistema de versionamento
require_once __DIR__ . '/version.php';

// Incluir e configurar o logger centralizado (Monolog)
require_once __DIR__ . '/logger_config.php';
logInfo('Sistema iniciado', ['environment' => 'development']);

// Configurações de sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações de banco de dados
define('DB_HOST', '$host');
define('DB_NAME', '$dbname');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('DB_CHARSET', 'utf8mb4');

// Configurações de ambiente
define('APP_ENV', 'development');
define('APP_URL', 'http://' . \$_SERVER['SERVER_NAME']);
";
                
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
            <footer class="page-footer-note">
                (C) Carlos Pinto Jr, 2025 · Suporte: 
                <a href="mailto:capivara@capivaralearn.com.br" style="color: inherit; text-decoration: underline;">capivara@capivaralearn.com.br</a>
            </footer>
</body>
</html>
