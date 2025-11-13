# 🔄 Quick Sync Guide - CapivaraLearn

## Fluxo de Trabalho Recomendado

### 1. Desenvolvimento
- Trabalhe sempre no diretório: `/home/carlos/Documents/GitHub/CapivaraLearn`
- Faça suas modificações nos arquivos de desenvolvimento
- Teste localmente quando possível

### 2. Sincronização para Produção (XAMPP)

#### 🚀 Método Recomendado - Script Interativo

Execute o script de sincronização:

```bash
cd /home/carlos/Documents/GitHub/CapivaraLearn
./sync_to_xampp.sh
```

O script irá perguntar:

1. **Preservar configurações?** (S/n)
   - `includes/config.php` - Configurações do banco de dados
   - `includes/environment.ini` - Variáveis de ambiente
   - **Recomendado: S** (se você já configurou o XAMPP)

2. **Preservar dados de usuário?** (S/n)
   - `backup/` - Backups de dados
   - `cache/` - Cache do sistema
   - **Recomendado: S** (se você tem dados importantes)

**Arquivos SEMPRE preservados:**
- `logs/` - Todos os logs do sistema

#### 📋 Exemplos de Uso

**Primeira sincronização (tudo novo):**
```bash
./sync_to_xampp.sh
# Responda: n, n (não preservar nada, copiar tudo)
```

**Atualização de código (manter config):**
```bash
./sync_to_xampp.sh
# Responda: S, S (preservar configurações e dados)
```

**Atualização forçada (resetar tudo):**
```bash
./sync_to_xampp.sh
# Responda: n, n (sobrescrever tudo)
```

**OU** use os comandos manuais (sem preservar configurações):
```bash
cd /home/carlos/Documents/GitHub/CapivaraLearn

sudo rm -rf /opt/lampp/htdocs/CapivaraLearn
sudo cp -r . /opt/lampp/htdocs/CapivaraLearn
sudo chown -R daemon:daemon /opt/lampp/htdocs/CapivaraLearn 
sudo chmod -R 644 /opt/lampp/htdocs/CapivaraLearn 
sudo find /opt/lampp/htdocs/CapivaraLearn -type d -exec chmod 755 {} \;
sudo mkdir -p /opt/lampp/htdocs/CapivaraLearn/logs
sudo chmod 777 /opt/lampp/htdocs/CapivaraLearn/logs
sudo touch /opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log
sudo chmod 666 /opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log
```

### 2. Sincronização para Produção (Servidor Público)

#### 🚀 Script Interativo para Produção

Requisitos:
- `pscp` e `plink` (PuTTY) disponíveis no `PATH`
- Chave privada `.ppk` com acesso ao servidor configurada no caminho padrão:
  `/home/carlos/Nextcloud/Documents/ppk/capivaralearn.ppk`
- Variáveis opcionais para personalizar: `SERVER_HOST`, `SERVER_PATH`, `SSH_KEY`, `REMOTE_OWNER`, `REMOTE_GROUP`

Execute o script:

```bash
cd /home/carlos/Documents/GitHub/CapivaraLearn
./sync_to_production.sh
```

O fluxo de perguntas é o mesmo do XAMPP:

1. **Preservar configurações?** (S/n)
   - `includes/config.php`
   - `includes/environment.ini`

2. **Preservar dados de usuário?** (S/n)
   - `backup/`
   - `cache/`

**O script sempre preserva:**
- `logs/`

#### 📋 Exemplos de Uso

**Deploy padrão (manter config e dados):**
```bash
./sync_to_production.sh
# Responda: S, S
```

**Deploy limpo (substitui tudo):**
```bash
./sync_to_production.sh
# Responda: n, n
```

#### 🔧 Personalizando credenciais

Você pode sobrescrever os valores padrão exportando variáveis antes de rodar o script:

```bash
export SERVER_HOST="deploy@198.23.132.15"
export SERVER_PATH="/var/www/html/CapivaraLearn"
export SSH_KEY="$HOME/.ssh/capivaralearn.ppk"
./sync_to_production.sh
```

**Dica:** Após o deploy, limpe o cache/CDN e revalide as páginas públicas:
`https://capivaralearn.com.br`.

### 3. Verificação
- Acesse: http://localhost/CapivaraLearn/
- Teste as funcionalidades modificadas
- Verifique logs em caso de erro: `tail -f /opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log`

## 🔐 Arquivos Preserváveis

### Sempre Preservados:
- ✅ **logs/** - Histórico de logs do sistema

### Opcionalmente Preservados:

#### Configurações:
- 🔧 **includes/config.php** - Credenciais de banco de dados, constantes do sistema
- 🔧 **includes/environment.ini** - Variáveis de ambiente (SMTP, etc)

#### Dados de Usuário:
- 💾 **backup/** - Backups manuais ou automáticos
- 🗂️ **cache/** - Cache do sistema

### ⚠️ Quando NÃO Preservar Configurações:

- Primeira instalação no XAMPP
- Mudanças no esquema do banco de dados
- Atualização de versão que afeta configurações
- Problemas de compatibilidade após atualização

### ✅ Quando Preservar Configurações:

- Atualizações normais de código
- Correções de bugs
- Novas funcionalidades (sem mudança de schema)
- Deploy de features já testadas

## 📁 Estrutura do Banco de Dados

**IMPORTANTE:** O banco de dados MySQL/MariaDB **NÃO é afetado** pela sincronização!

- O banco fica em: `/opt/lampp/var/mysql/`
- Apenas os arquivos PHP são sincronizados
- Para backup do banco, use: `mysqldump` ou interface web do phpMyAdmin



## ✅ Status do Sistema

- **Erro "Database::insert() undefined"**: ✅ CORRIGIDO
- **Sincronização desenvolvimento→XAMPP**: ✅ FUNCIONANDO
- **Sincronização para produção pública**: ✅ FUNCIONANDO (`sync_to_production.sh`)
- **Scripts automáticos**: ✅ DISPONÍVEIS (`sync_to_xampp.sh`, `sync_to_production.sh`)
- **Preservação de configurações**: ✅ IMPLEMENTADO
- **Preservação de dados**: ✅ IMPLEMENTADO
- **Permissões XAMPP**: ✅ CONFIGURADAS
- **Database CRUD**: ✅ FUNCIONANDO
- **UniversityService**: ✅ FUNCIONANDO

## 🚨 Importante

**SEMPRE** execute a sincronização após modificar arquivos no diretório de desenvolvimento.

**NUNCA** edite arquivos diretamente em `/opt/lampp/htdocs/CapivaraLearn/` - suas alterações serão perdidas na próxima sincronização (exceto arquivos preservados).

## 🆘 Solução de Problemas

### Erro: "config.php não encontrado"
```bash
# Rode o instalador para recriar o config.php
http://localhost/CapivaraLearn/install.php
```

### Erro: "Permissões negadas"
```bash
# Reconfigure as permissões
sudo chown -R daemon:daemon /opt/lampp/htdocs/CapivaraLearn
sudo chmod 777 /opt/lampp/htdocs/CapivaraLearn/logs
```

### Erro: "Não consegue conectar ao banco"
```bash
# Verifique se o MySQL está rodando
sudo /opt/lampp/lampp startmysql

# Verifique as credenciais em includes/environment.ini
```

---
*Última atualização: 12 Nov 2025*
