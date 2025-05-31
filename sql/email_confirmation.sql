-- =============================================
-- CapivaraLearn - Tabelas para Confirmação de Email
-- Execute este SQL no phpMyAdmin ou MySQL
-- =============================================

USE capivaralearn;

-- Adicionar coluna de confirmação na tabela usuarios
ALTER TABLE usuarios 
ADD COLUMN email_verificado BOOLEAN DEFAULT FALSE AFTER ativo,
ADD COLUMN data_verificacao TIMESTAMP NULL AFTER email_verificado;

-- Criar tabela para tokens de confirmação de email
CREATE TABLE email_tokens (
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
) ENGINE=InnoDB;

-- Criar tabela para log de emails enviados
CREATE TABLE email_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    email_destino VARCHAR(150) NOT NULL,
    assunto VARCHAR(255) NOT NULL,
    tipo ENUM('confirmacao', 'recuperacao_senha', 'notificacao') NOT NULL,
    status ENUM('enviado', 'falha', 'pendente') DEFAULT 'pendente',
    tentativas INT DEFAULT 0,
    erro_detalhes TEXT DEFAULT NULL,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_entrega TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_email_status (email_destino, status),
    INDEX idx_tipo_data (tipo, data_envio)
) ENGINE=InnoDB;

-- Atualizar usuário de teste para estar verificado
UPDATE usuarios 
SET email_verificado = TRUE, data_verificacao = NOW() 
WHERE email = 'teste@capivaralearn.com';

-- Criar trigger para limpar tokens expirados automaticamente
DELIMITER //

CREATE EVENT limpar_tokens_expirados
ON SCHEDULE EVERY 1 HOUR
DO
  DELETE FROM email_tokens WHERE data_expiracao < NOW();

DELIMITER ;

-- Verificar se foi criado corretamente
SELECT 'Estrutura criada com sucesso!' as status,
       COUNT(*) as usuarios_total,
       SUM(CASE WHEN email_verificado = 1 THEN 1 ELSE 0 END) as usuarios_verificados
FROM usuarios;