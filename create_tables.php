<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Creating database tables...\n";

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'capivaralearn';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // SQL commands from install.php
    $sqlCommands = [
        "CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            tipo ENUM('admin', 'professor', 'aluno') DEFAULT 'aluno',
            ativo BOOLEAN DEFAULT FALSE,
            data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_ultima_atividade TIMESTAMP NULL,
            token_email VARCHAR(255) NULL,
            email_verificado BOOLEAN DEFAULT FALSE,
            INDEX idx_email (email),
            INDEX idx_tipo (tipo)
        ) ENGINE=InnoDB",
        
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
        
        "CREATE TABLE IF NOT EXISTS cursos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(150) NOT NULL,
            descricao TEXT,
            carga_horaria INT,
            data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ativo BOOLEAN DEFAULT TRUE,
            INDEX idx_nome (nome)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS universidade_curso (
            id INT AUTO_INCREMENT PRIMARY KEY,
            universidade_id INT NOT NULL,
            curso_id INT NOT NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ativo BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (universidade_id) REFERENCES universidades(id) ON DELETE CASCADE,
            FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_universidade_curso (universidade_id, curso_id)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS usuario_curso_universidade (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            curso_id INT NOT NULL,
            universidade_id INT NOT NULL,
            data_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('ativo', 'concluido', 'trancado', 'cancelado') DEFAULT 'ativo',
            progresso DECIMAL(5,2) DEFAULT 0.00,
            data_conclusao TIMESTAMP NULL,
            nota_final DECIMAL(4,2) NULL,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
            FOREIGN KEY (universidade_id) REFERENCES universidades(id) ON DELETE CASCADE,
            UNIQUE KEY unique_matricula (usuario_id, curso_id, universidade_id),
            INDEX idx_usuario (usuario_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB"
    ];
    
    foreach ($sqlCommands as $index => $sql) {
        try {
            $pdo->exec($sql);
            echo "Table " . ($index + 1) . " created successfully.\n";
        } catch (PDOException $e) {
            echo "Error creating table " . ($index + 1) . ": " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nDatabase setup completed!\n";
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?>
