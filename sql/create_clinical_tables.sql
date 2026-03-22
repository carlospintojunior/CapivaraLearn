-- CapivaraLearn - Tabelas Clínicas (Ferramentas por Curso)
-- Estrutura modular: Categorias → Regiões → Testes
-- Extensível para anamnese, evolução, antropometria, etc.

-- Categorias clínicas (ex: "Testes Especiais de Ortopedia", "Anamnese", etc.)
CREATE TABLE IF NOT EXISTS categorias_clinicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    descricao TEXT,
    icone VARCHAR(50) DEFAULT 'fa-stethoscope',
    curso_alvo VARCHAR(200) DEFAULT 'Fisioterapia',
    ativo TINYINT(1) DEFAULT 1,
    ordem INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_curso_alvo (curso_alvo),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Regiões corporais / subcategorias (ex: "Ombro", "Cotovelo", "Quadril")
CREATE TABLE IF NOT EXISTS regioes_corporais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descricao TEXT,
    icone VARCHAR(50) DEFAULT 'fa-bone',
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categoria (categoria_id),
    INDEX idx_ativo (ativo),
    FOREIGN KEY (categoria_id) REFERENCES categorias_clinicas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Testes especiais / itens clínicos
CREATE TABLE IF NOT EXISTS testes_especiais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    regiao_id INT NOT NULL,
    nome VARCHAR(300) NOT NULL,
    nome_alternativo VARCHAR(300),
    descricao TEXT,
    tecnica TEXT,
    indicacao VARCHAR(500),
    positivo_quando TEXT,
    video_filename VARCHAR(255),
    imagem_filename VARCHAR(255),
    referencias TEXT,
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_regiao (regiao_id),
    INDEX idx_ativo (ativo),
    INDEX idx_nome (nome),
    FOREIGN KEY (regiao_id) REFERENCES regioes_corporais(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
