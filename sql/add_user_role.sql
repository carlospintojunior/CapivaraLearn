-- CapivaraLearn - Adicionar campo role à tabela de usuários
-- Permite diferenciar administradores de usuários comuns

ALTER TABLE usuarios ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER ativo;

-- Definir o primeiro usuário como admin (ajustar conforme necessário)
-- UPDATE usuarios SET role='admin' WHERE id=1;
