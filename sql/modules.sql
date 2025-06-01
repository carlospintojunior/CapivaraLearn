-- Criação da tabela de módulos
CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50), -- Para ícones de Material Design ou FontAwesome
    color VARCHAR(7), -- Código de cor hex (#RRGGBB)
    order_index INT DEFAULT 0, -- Para ordenação personalizada
    status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
