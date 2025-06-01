-- =============================================
-- CapivaraLearn - Script de Criação do Banco
-- =============================================

CREATE DATABASE IF NOT EXISTS capivaralearn 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE capivaralearn;

CREATE TABLE usuarios (
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
    email_verificado BOOLEAN DEFAULT FALSE,
    INDEX idx_email (email),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB;

CREATE TABLE modulos (
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
) ENGINE=InnoDB;

CREATE TABLE topicos (
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
) ENGINE=InnoDB;

CREATE TABLE atividades (
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
) ENGINE=InnoDB;

CREATE TABLE lembretes (
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
) ENGINE=InnoDB;

CREATE TABLE sessoes (
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
) ENGINE=InnoDB;

CREATE TABLE configuracoes_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tema ENUM('claro', 'escuro', 'auto') DEFAULT 'claro',
    notificacoes_email BOOLEAN DEFAULT TRUE,
    lembrete_antecedencia_dias INT DEFAULT 3,
    timezone VARCHAR(50) DEFAULT 'America/Sao_Paulo',
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_config_usuario (usuario_id)
) ENGINE=InnoDB;

INSERT INTO usuarios (nome, email, senha, curso, instituicao) VALUES 
('Estudante Teste', 'teste@capivaralearn.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fisioterapia', 'Universidade Exemplo');

INSERT INTO configuracoes_usuario (usuario_id) VALUES (1);

INSERT INTO modulos (usuario_id, nome, codigo, descricao, data_inicio, data_fim) VALUES 
(1, 'MOD202/25 - FARMACOCINÉTICA E FARMACODINÂMICA', 'MOD202/25', 'Conceitos básicos de farmacologia aplicada à fisioterapia', '2025-05-05', '2025-07-06');

INSERT INTO topicos (modulo_id, nome, descricao, data_inicio, data_fim, ordem) VALUES 
(1, 'Tópico 1', 'Conceitos básicos de farmacologia', '2025-05-05', '2025-05-18', 1),
(1, 'Tópico 2', 'Farmacocinética avançada', '2025-05-19', '2025-06-01', 2),
(1, 'Tópico 3', 'Farmacodinâmica e interações medicamentosas', '2025-06-02', '2025-06-15', 3),
(1, 'Tópico 4', 'Aplicações clínicas em fisioterapia', '2025-06-16', '2025-06-29', 4);