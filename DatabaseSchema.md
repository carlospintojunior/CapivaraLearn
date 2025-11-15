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
    gabarito TEXT,                                -- Answer key/expected answers
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
    numero_matricula VARCHAR(50),                 -- Enrollment number
    data_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Enrollment date
    status ENUM('ativa', 'concluida', 'cancelada', 'trancada') DEFAULT 'ativa',
    progresso DECIMAL(5,2) DEFAULT 0.00,          -- Progress percentage (0-100)
    nota_final DECIMAL(4,2) NULL,                 -- Final grade (0-10)
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

## � Table: `subscription_plans` (Subscription Plans)

Stores available subscription plans and pricing information.

```sql
CREATE TABLE subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(100) NOT NULL,              -- Plan name (e.g., 'Annual Contribution')
    plan_code VARCHAR(50) NOT NULL UNIQUE,        -- Unique plan identifier
    description TEXT,                             -- Plan description
    price_usd DECIMAL(10,2) NOT NULL,            -- Price in USD
    billing_cycle ENUM('monthly', 'yearly', 'one_time') DEFAULT 'yearly',
    grace_period_days INT DEFAULT 365,           -- Grace period before payment required
    is_active TINYINT(1) DEFAULT 1,              -- Plan active status
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_plan_code (plan_code),
    INDEX idx_is_active (is_active)
);
```

---

## 💳 Table: `user_subscriptions` (User Subscriptions)

Tracks user subscription status and payment obligations.

```sql
CREATE TABLE user_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                         -- User reference
    plan_id INT NOT NULL,                         -- Subscription plan reference
    status ENUM('active', 'grace_period', 'payment_due', 'overdue', 'suspended') DEFAULT 'grace_period',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- User registration date
    grace_period_end DATE,                        -- When grace period ends
    next_payment_due DATE,                        -- Next payment due date
    last_payment_date TIMESTAMP NULL,             -- Last successful payment
    amount_due_usd DECIMAL(10,2) DEFAULT 0.00,   -- Outstanding amount
    payment_attempts INT DEFAULT 0,               -- Number of payment attempts
    notes TEXT,                                   -- Administrative notes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id),
    UNIQUE KEY unique_user_plan (user_id, plan_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_grace_period_end (grace_period_end),
    INDEX idx_next_payment_due (next_payment_due)
);
```

---

## 💸 Table: `payment_transactions` (Payment Transactions)

Records all payment transactions and attempts.

```sql
CREATE TABLE payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                         -- User reference
    subscription_id INT NOT NULL,                 -- Subscription reference
    transaction_type ENUM('payment', 'refund', 'adjustment') DEFAULT 'payment',
    amount_usd DECIMAL(10,2) NOT NULL,            -- Transaction amount
    currency VARCHAR(3) DEFAULT 'USD',            -- Currency code
    payment_method ENUM('credit_card', 'paypal', 'bank_transfer', 'crypto', 'other') NULL,
    payment_gateway VARCHAR(100),                 -- Payment processor used
    gateway_transaction_id VARCHAR(255),          -- External transaction ID
    status ENUM('pending', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_date TIMESTAMP NULL,                  -- When payment was completed
    failure_reason TEXT,                          -- Reason for failed payments
    gateway_response JSON,                        -- Full gateway response
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_subscription_id (subscription_id),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date),
    INDEX idx_gateway_transaction_id (gateway_transaction_id)
);
```

---

## 📊 Table: `billing_events` (Billing Events)

Logs all billing-related events for audit and tracking.

```sql
CREATE TABLE billing_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                         -- User reference
    subscription_id INT,                          -- Subscription reference (nullable)
    event_type ENUM('registration', 'grace_period_start', 'payment_due', 'payment_completed', 'payment_failed', 'account_suspended', 'account_reactivated') NOT NULL,
    event_description TEXT,                       -- Detailed event description
    amount_usd DECIMAL(10,2) NULL,                -- Associated amount if applicable
    metadata JSON,                                -- Additional event data
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_subscription_id (subscription_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
);
```

---

## 🔔 Table: `payment_notifications` (Payment Notifications)

Manages payment reminders and notifications to users.

```sql
CREATE TABLE payment_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                         -- User reference
    subscription_id INT NOT NULL,                 -- Subscription reference
    notification_type ENUM('grace_period_ending', 'payment_due', 'payment_overdue', 'final_notice') NOT NULL,
    scheduled_date DATE NOT NULL,                 -- When notification should be sent
    sent_at TIMESTAMP NULL,                       -- When notification was actually sent
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    notification_channel ENUM('email', 'sms', 'in_app') DEFAULT 'email',
    message_content TEXT,                         -- Notification message
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_subscription_id (subscription_id),
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_status (status)
);
```

---

## 🗂️ New Financial Tables Structure Summary

The financial system includes:

### Core Tables:
1. **subscription_plans** - Available subscription plans and pricing
2. **user_subscriptions** - User subscription status and payment tracking
3. **payment_transactions** - All payment attempts and completions
4. **billing_events** - Audit log of all billing events
5. **payment_notifications** - Payment reminder system

### Key Features:
- **Grace Period Management**: 365-day free trial period
- **Flexible Pricing**: Support for multiple plans and currencies
- **Payment Gateway Integration**: Ready for multiple payment processors
- **Audit Trail**: Complete logging of all financial events
- **Notification System**: Automated payment reminders
- **Compliance Ready**: Structured for financial reporting and compliance

### Security Considerations:
- Foreign key constraints maintain data integrity
- JSON fields for flexible gateway integration
- Proper indexing for performance
- Audit trail for all financial operations

---

## �📝 Notes

1. **Character Encoding**: All tables use UTF8MB4 for full Unicode support
2. **Timestamps**: All tables include creation and update timestamps
3. **Soft Deletes**: Not implemented - uses CASCADE DELETE for simplicity
4. **Indexes**: Strategic indexes on foreign keys and frequently searched fields
5. **Constraints**: Proper foreign key constraints maintain data integrity

---

*Last updated: July 2025*
*Version: 1.1*
