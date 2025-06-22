<?php
/**
 * Script de teste para validar as queries do install.php
 * Executa apenas as queries de criação de tabelas com debug detalhado
 */

echo "🔧 TESTE DE CRIAÇÃO DE TABELAS DO INSTALL.PHP\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Configuração do banco
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'capivaralearn';

try {
    // Conectar ao MySQL
    $mysqli = new mysqli($host, $user, $pass);
    if ($mysqli->connect_error) {
        die("Erro de conexão: " . $mysqli->connect_error);
    }
    $mysqli->set_charset("utf8mb4");
    
    echo "✅ Conectado ao MySQL\n";
    
    // Selecionar/criar banco
    $mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $mysqli->select_db($dbname);
    echo "✅ Banco '$dbname' selecionado\n\n";
    
    // Dropar todas as tabelas para teste limpo
    echo "🗑️  LIMPANDO BANCO PARA TESTE LIMPO:\n";
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
    $tables = ['inscricoes', 'universidade_cursos', 'topicos', 'disciplinas', 'cursos', 'universidades', 'email_tokens', 'usuarios'];
    foreach ($tables as $table) {
        if ($mysqli->query("DROP TABLE IF EXISTS $table")) {
            echo "✅ Tabela '$table' removida\n";
        }
    }
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "\n";
    
    // Definir tabelas exatamente como no install.php
    $tablesQueries = [
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
        ) ENGINE=InnoDB",
        
        "universidades" => "CREATE TABLE IF NOT EXISTS universidades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            sigla VARCHAR(20),
            cidade VARCHAR(100),
            estado VARCHAR(50),
            usuario_id INT NOT NULL,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_usuario_nome (usuario_id, nome),
            INDEX idx_usuario_sigla (usuario_id, sigla)
        ) ENGINE=InnoDB",
        
        "cursos" => "CREATE TABLE IF NOT EXISTS cursos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            descricao TEXT,
            carga_horaria INT,
            usuario_id INT NOT NULL,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_usuario_nome (usuario_id, nome)
        ) ENGINE=InnoDB",
        
        "disciplinas" => "CREATE TABLE IF NOT EXISTS disciplinas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            descricao TEXT,
            codigo VARCHAR(50),
            carga_horaria INT,
            usuario_id INT NOT NULL,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_usuario_nome (usuario_id, nome),
            INDEX idx_usuario_codigo (usuario_id, codigo)
        ) ENGINE=InnoDB",
        
        "topicos" => "CREATE TABLE IF NOT EXISTS topicos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            descricao TEXT,
            disciplina_id INT NOT NULL,
            usuario_id INT NOT NULL,
            ordem INT DEFAULT 0,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_disciplina_ordem (disciplina_id, ordem),
            INDEX idx_usuario_nome (usuario_id, nome)
        ) ENGINE=InnoDB",
        
        "universidade_cursos" => "CREATE TABLE IF NOT EXISTS universidade_cursos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            universidade_id INT NOT NULL,
            curso_id INT NOT NULL,
            usuario_id INT NOT NULL,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (universidade_id) REFERENCES universidades(id) ON DELETE CASCADE,
            FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_univ_curso (universidade_id, curso_id, usuario_id),
            INDEX idx_usuario (usuario_id),
            INDEX idx_universidade (universidade_id),
            INDEX idx_curso (curso_id)
        ) ENGINE=InnoDB",
        
        "inscricoes" => "CREATE TABLE IF NOT EXISTS inscricoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            universidade_id INT NOT NULL,
            curso_id INT NOT NULL,
            status ENUM('ativo', 'concluido', 'trancado', 'cancelado') DEFAULT 'ativo',
            progresso DECIMAL(5,2) DEFAULT 0.00,
            data_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_conclusao TIMESTAMP NULL,
            nota_final DECIMAL(4,2) NULL,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (universidade_id) REFERENCES universidades(id) ON DELETE CASCADE,
            FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_matricula (usuario_id, universidade_id, curso_id),
            INDEX idx_usuario_status (usuario_id, status),
            INDEX idx_universidade (universidade_id),
            INDEX idx_curso (curso_id)
        ) ENGINE=InnoDB"
    ];
    
    echo "📋 CRIANDO TABELAS:\n";
    echo "-" . str_repeat("-", 30) . "\n";
    
    $success = 0;
    $total = count($tablesQueries);
    
    foreach ($tablesQueries as $tableName => $sql) {
        echo "Criando '$tableName'... ";
        if ($mysqli->query($sql)) {
            echo "✅ OK\n";
            $success++;
        } else {
            echo "❌ ERRO: " . $mysqli->error . "\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "📊 RESULTADO FINAL:\n";
    echo "Criadas: $success/$total tabelas\n\n";
    
    // Verificar resultado
    $result = $mysqli->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    echo "🎯 TABELAS CRIADAS:\n";
    foreach ($tables as $table) {
        echo "✅ $table\n";
    }
    
    // Verificar constraint crítica
    echo "\n🔗 VERIFICANDO CONSTRAINT CRÍTICA:\n";
    $result = $mysqli->query("SHOW CREATE TABLE universidade_cursos");
    $row = $result->fetch_assoc();
    $createTable = $row['Create Table'];
    
    if (strpos($createTable, 'unique_user_univ_curso') !== false) {
        echo "✅ Constraint UNIQUE correta (inclui usuario_id)\n";
    } else {
        echo "❌ Constraint UNIQUE incorreta\n";
        echo "SQL gerado:\n" . $createTable . "\n";
    }
    
    echo "\n🎉 TESTE CONCLUÍDO!\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
?>
