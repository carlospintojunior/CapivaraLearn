# 🐾 CapivaraLearn Database Schema

## Database Structure Documentation

This document describes the complete database structure for CapivaraLearn, including all tables, fields, indexes, and relationships.

---

## 📋 Table: `usuarios` (Users)

Main table for storing user information and authentication data.

```sql
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,                    -- User's full name
    email VARCHAR(255) NOT NULL UNIQUE,            -- User's email address (unique)
    senha VARCHAR(255) NOT NULL,                   -- Password hash (bcrypt)
    ativo TINYINT(1) DEFAULT 1,                   -- Account status (1=active, 0=inactive)
    email_verificado TINYINT(1) DEFAULT 0,        -- Email verification status
    termos_aceitos TINYINT(1) NOT NULL DEFAULT 0, -- Terms of use acceptance
    data_aceitacao_termos TIMESTAMP NULL,         -- When terms were accepted
    versao_termos_aceitos VARCHAR(10) DEFAULT '1.0', -- Version of accepted terms
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_ativo (ativo),
    INDEX idx_email_verificado (email_verificado),
    INDEX idx_termos_aceitos (termos_aceitos)
);
```

---

## 🏛️ Table: `universidades` (Universities)

Stores information about universities/institutions.

```sql
CREATE TABLE universidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,                    -- University name
    sigla VARCHAR(10),                            -- University abbreviation
    pais VARCHAR(100) DEFAULT 'Brasil',          -- Country
    cidade VARCHAR(100),                          -- City
    estado VARCHAR(2) NOT NULL,                  -- State (UF)
    site VARCHAR(255),                            -- Website URL
    usuario_id INT NOT NULL,                      -- Owner user ID
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_id (usuario_id)
);
```

---

## 📚 Table: `cursos` (Courses)

Stores course information within universities.

```sql
CREATE TABLE cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,                   -- Course name
    descricao TEXT,                               -- Course description
    nivel VARCHAR(50),                            -- Level (graduacao, pos-graduacao, etc.)
    carga_horaria INT DEFAULT 0,                  -- Workload hours
    universidade_id INT NOT NULL,                 -- University reference
    usuario_id INT NOT NULL,                      -- Owner user ID
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (universidade_id) REFERENCES universidades(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_universidade_id (universidade_id),
    INDEX idx_usuario_id (usuario_id)
);
```

---

## 📖 Table: `disciplinas` (Subjects)

Stores subject/module information within courses.

```sql
CREATE TABLE disciplinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,                   -- Subject name
    codigo VARCHAR(20),                           -- Subject code
    descricao TEXT,                               -- Subject description
    carga_horaria INT DEFAULT 0,                  -- Workload hours
    semestre INT,                                 -- Semester number
    concluido TINYINT(1) DEFAULT 0,              -- Completion status
    curso_id INT NOT NULL,                        -- Course reference
    usuario_id INT NOT NULL,                      -- Owner user ID
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_curso_id (curso_id),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_concluido (concluido)
);
```

---

## 📝 Table: `topicos` (Topics)

Stores topics within subjects for detailed study organization.

```sql
CREATE TABLE topicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,                   -- Topic name
    descricao TEXT,                               -- Topic description
    data_prazo DATE,                              -- Due date
    prioridade ENUM('baixa', 'media', 'alta') DEFAULT 'media', -- Priority level
    concluido TINYINT(1) DEFAULT 0,              -- Completion status
    disciplina_id INT NOT NULL,                   -- Subject reference
    ordem INT DEFAULT 0,                          -- Order/index of the topic
    usuario_id INT NOT NULL,                      -- Owner user ID
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_disciplina_id (disciplina_id),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_concluido (concluido),
    INDEX idx_data_prazo (data_prazo)
);
```

---

## 📚 Table: `unidades_aprendizagem` (Learning Units)

Stores detailed learning units within topics.

```sql
CREATE TABLE unidades_aprendizagem (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,                   -- Learning unit name
    descricao TEXT,                               -- Description
    tipo ENUM('leitura', 'exercicio', 'projeto', 'prova', 'outros') DEFAULT 'leitura',
    nota DECIMAL(4,2),                            -- Grade/Score
    data_prazo DATE,                              -- Due date
    concluido TINYINT(1) DEFAULT 0,              -- Completion status
    topico_id INT NOT NULL,                       -- Topic reference
    usuario_id INT NOT NULL,                      -- Owner user ID
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (topico_id) REFERENCES topicos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_topico_id (topico_id),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_concluido (concluido),
    INDEX idx_data_prazo (data_prazo)
);
```

---

## 🎓 Table: `matriculas` (Enrollments)

Tracks user enrollments in courses.

```sql
CREATE TABLE matriculas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,                      -- User reference
    universidade_id INT NOT NULL,                 -- University reference
    curso_id INT NOT NULL,                        -- Course reference
    data_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Enrollment date
    status ENUM('ativa', 'concluida', 'cancelada', 'trancada') DEFAULT 'ativa',
    data_conclusao TIMESTAMP NULL,                -- Completion date
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (universidade_id) REFERENCES universidades(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (usuario_id, universidade_id, curso_id),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_universidade_id (universidade_id),
    INDEX idx_curso_id (curso_id),
    INDEX idx_status (status)
);
```

---

## 🔐 Table: `email_tokens` (Email Verification Tokens)

Stores tokens for email verification and password reset.

```sql
CREATE TABLE email_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,                      -- User reference
    token VARCHAR(255) NOT NULL UNIQUE,           -- Verification token
    tipo ENUM('confirmacao', 'reset_senha') NOT NULL, -- Token type
    usado TINYINT(1) DEFAULT 0,                  -- Usage status
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_expiracao TIMESTAMP NOT NULL,           -- Expiration date
    ip_address VARCHAR(45),                       -- IP address when created
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_usado (usado),
    INDEX idx_expiracao (data_expiracao)
);
```

---

## 📧 Table: `email_log` (Email Log)

Logs all email communications for auditing purposes.

```sql
CREATE TABLE email_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destinatario VARCHAR(255) NOT NULL,           -- Recipient email
    assunto VARCHAR(255),                         -- Email subject
    tipo ENUM('confirmacao', 'reset_senha', 'notificacao') NOT NULL,
    status ENUM('enviado', 'erro', 'pendente') DEFAULT 'pendente',
    erro_detalhes TEXT,                           -- Error details if failed
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_destinatario (destinatario),
    INDEX idx_tipo (tipo),
    INDEX idx_status (status),
    INDEX idx_data_envio (data_envio)
);
```

---

## ⚙️ Table: `configuracoes_usuario` (User Settings)

Stores user-specific configuration settings.

```sql
CREATE TABLE configuracoes_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,                      -- User reference
    tema VARCHAR(20) DEFAULT 'claro',             -- Theme preference
    idioma VARCHAR(10) DEFAULT 'pt-BR',           -- Language preference
    notificacoes_email TINYINT(1) DEFAULT 1,     -- Email notifications enabled
    notificacoes_prazos TINYINT(1) DEFAULT 1,    -- Deadline notifications
    fuso_horario VARCHAR(50) DEFAULT 'America/Sao_Paulo', -- Timezone
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_config (usuario_id)
);
```

---

## 📊 Database Relationships

### Primary Relationships:
- `usuarios` (1) → `universidades` (N)
- `universidades` (1) → `cursos` (N)
- `cursos` (1) → `disciplinas` (N)
- `disciplinas` (1) → `topicos` (N)
- `topicos` (1) → `unidades_aprendizagem` (N)
- `usuarios` (1) → `matriculas` (N)
- `cursos` (1) → `matriculas` (N)

### Supporting Tables:
- `email_tokens` → `usuarios`
- `email_log` (standalone audit table)
- `configuracoes_usuario` → `usuarios`

---

## 🔧 Key Features

### Security:
- Password hashing with bcrypt
- Email verification system
- Token-based authentication
- Audit trail for emails

### LGPD Compliance:
- Terms acceptance tracking
- Version control for terms
- User consent management
- Data retention policies

### Performance:
- Proper indexing on frequently queried fields
- Cascading deletes for data integrity
- Optimized foreign key relationships

### Scalability:
- Normalized structure
- Efficient data types
- Proper constraints and validations

---

## 📝 Notes

1. **Character Encoding**: All tables use UTF8MB4 for full Unicode support
2. **Timestamps**: All tables include creation and update timestamps
3. **Soft Deletes**: Not implemented - uses CASCADE DELETE for simplicity
4. **Indexes**: Strategic indexes on foreign keys and frequently searched fields
5. **Constraints**: Proper foreign key constraints maintain data integrity

---

*Last updated: January 2025*
*Version: 1.0*
