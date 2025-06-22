# 📊 ESTRUTURA DO BANCO DE DADOS - CAPIVARALEARN

## 🔄 Status: INSTALL.PHP 100% FUNCIONAL ✅

Esta documentação reflete exatamente a estrutura atual do banco de dados conforme definido no `install.php`.

**🎯 VALIDAÇÃO FINAL CONCLUÍDA (14:10 - 22/06/2025):**
- ✅ **8 tabelas** criadas corretamente pelo install.php
- ✅ **Constraint de isolamento** perfeita: `unique_user_univ_curso (universidade_id,curso_id,usuario_id)`
- ✅ **Tabela `inscricoes`** criada automaticamente
- ✅ **Todos os campos** presentes: `ativo`, `data_atualizacao`, etc.
- ✅ **Sistema 100% pronto** para CRUD isolado por usuário
- ✅ **Install.php validado** e funcionando perfeitamente

---

## 🔐 TABELAS EXISTENTES (Confirmadas no banco de dados)

### Tabela: `usuarios`
```sql
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    email_verificado BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB
```

### Tabela: `email_tokens`
```sql
CREATE TABLE IF NOT EXISTS email_tokens (
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
) ENGINE=InnoDB
```

### Tabela: `universidades` ✅ EXISTE
```sql
id               | int(11)      | NO   | PRI | NULL                | auto_increment                |
nome             | varchar(255) | NO   |     | NULL                |                               |
sigla            | varchar(10)  | NO   |     | NULL                |                               |
cidade           | varchar(100) | NO   |     | NULL                |                               |
estado           | varchar(2)   | NO   |     | NULL                |                               |
usuario_id       | int(11)      | NO   | MUL | NULL                |                               |
data_criacao     | timestamp    | NO   |     | current_timestamp() |                               |
data_atualizacao | timestamp    | NO   |     | current_timestamp() | on update current_timestamp() |
```

### Tabela: `cursos` ✅ EXISTE
```sql
id               | int(11)      | NO   | PRI | NULL                | auto_increment                |
nome             | varchar(255) | NO   |     | NULL                |                               |
descricao        | text         | YES  |     | NULL                |                               |
codigo           | varchar(20)  | YES  |     | NULL                |                               |
carga_horaria    | int(11)      | YES  |     | NULL                |                               |
usuario_id       | int(11)      | NO   | MUL | NULL                |                               |
data_criacao     | timestamp    | NO   |     | current_timestamp() |                               |
data_atualizacao | timestamp    | NO   |     | current_timestamp() | on update current_timestamp() |
```

### Tabela: `universidade_cursos` ✅ EXISTE (Relacionamento N:N)
```sql
id              | int(11)   | NO   | PRI | NULL                | auto_increment |
universidade_id | int(11)   | NO   | MUL | NULL                |                |
curso_id        | int(11)   | NO   | MUL | NULL                |                |
usuario_id      | int(11)   | NO   | MUL | NULL                |                |
data_criacao    | timestamp | NO   |     | current_timestamp() |                |
```

### Tabela: `disciplinas` ✅ EXISTE
### Tabela: `topicos` ✅ EXISTE

---

## 🎯 SESSÃO PHP (Campos reais conforme login.php)

Quando um usuário faz login com sucesso, os seguintes campos são definidos na sessão:

```php
$_SESSION['user_id'] = $user[0]['id'];          // ID do usuário (INT)
$_SESSION['user_name'] = $user[0]['nome'];      // Nome do usuário (STRING)
$_SESSION['user_email'] = $user[0]['email'];    // Email do usuário (STRING)
$_SESSION['logged_in'] = true;                  // Flag de login (BOOLEAN)
```

### ⚠️ IMPORTANTE: Campos de sessão padronizados para uso no código
```php
// Use sempre estas constantes:
const SESSION_USER_ID = 'user_id';
const SESSION_USER_NAME = 'user_name';
const SESSION_USER_EMAIL = 'user_email';
const SESSION_LOGGED_IN = 'logged_in';

// Exemplo de uso:
$userId = $_SESSION[SESSION_USER_ID];
$userName = $_SESSION[SESSION_USER_NAME];
```

---

## 📋 TABELAS NECESSÁRIAS PARA O CRUD (REMOVIDA - JÁ EXISTEM!)

**✅ TODAS AS TABELAS DO CRUD JÁ EXISTEM NO BANCO DE DADOS!**

As tabelas `universidades`, `cursos`, `disciplinas`, `topicos` e `universidade_cursos` já foram criadas e estão funcionais no banco de dados.

---

## 🛡️ ISOLAMENTO POR USUÁRIO - ANÁLISE COMPLETA ✅

### ✅ **VERIFICAÇÃO CONFIRMADA:**

1. **✅ Todas as tabelas têm `usuario_id`** - Garantindo isolamento
2. **✅ Todas as consultas devem filtrar por `usuario_id`** - Cada usuário vê apenas seus dados  
3. **✅ Usuários podem ter dados duplicados independentemente** - Mesma universidade/curso pode existir para usuários diferentes
4. **✅ Foreign keys com CASCADE** - Integridade referencial mantida
5. **✅ Constraint corrigido** - `universidade_cursos` agora inclui `usuario_id` na unique key

### 🔧 **CORREÇÃO APLICADA:**

**Problema identificado e corrigido:**
- ❌ **Antes:** `UNIQUE KEY unique_universidade_curso (universidade_id, curso_id)`
- ✅ **Depois:** `UNIQUE KEY unique_universidade_curso_usuario (universidade_id, curso_id, usuario_id)`

**Resultado:** Agora múltiplos usuários podem associar os mesmos cursos às mesmas universidades independentemente.

### 📊 **CENÁRIO DE TESTE VÁLIDO:**

```sql
-- Usuário 1 pode ter:
INSERT INTO universidades (nome, sigla, cidade, estado, usuario_id) VALUES ('USP', 'USP', 'São Paulo', 'SP', 1);
INSERT INTO cursos (nome, usuario_id) VALUES ('Medicina', 1);

-- Usuário 2 pode ter exatamente o mesmo:
INSERT INTO universidades (nome, sigla, cidade, estado, usuario_id) VALUES ('USP', 'USP', 'São Paulo', 'SP', 2);  
INSERT INTO cursos (nome, usuario_id) VALUES ('Medicina', 2);

-- Ambos podem associar USP + Medicina sem conflito!
```

---

## 📝 PRÓXIMOS PASSOS

1. ✅ Documentar estrutura atual
2. ✅ Tabelas do CRUD já existem no banco!
3. ⏳ Verificar se há services básicos funcionais
4. ⏳ Implementar CRUD de universidades (tabela já existe)
5. ⏳ Implementar CRUD de cursos (tabela já existe)
6. ⏳ Implementar CRUD de disciplinas (tabela já existe)
7. ⏳ Implementar CRUD de tópicos (tabela já existe)

---

## 🔧 COMANDOS ÚTEIS

### Verificar estrutura do banco
```bash
/opt/lampp/bin/mysql -u root capivaralearn
SHOW TABLES;
DESCRIBE usuarios;
```

### Sincronizar código
```bash
cd /home/carlos/Documents/GitHub/CapivaraLearn
./sync_to_xampp.sh
```

### Ver logs
```bash
tail -f /opt/lampp/htdocs/CapivaraLearn/logs/sistema.log
```
