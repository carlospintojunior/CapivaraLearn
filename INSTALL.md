# CapivaraLearn - Guia de Instalação
## Sistema de Gestão Educacional 100% Gratuito

**Versão:** 0.7.0 - Community Model  
**Data:** Julho 2025  
**Ambiente:** Ubuntu 24.04.2 LTS  
**Requisitos Mínimos:** 2GB RAM, 10GB disco

---

## 📋 Visão Geral

O CapivaraLearn é um sistema educacional 100% gratuito que funciona com modelo de contribuições voluntárias após 1 ano de uso. Esta instalação otimiza recursos para servidores com limitações de memória.

---

## 🔧 Pré-requisitos do Sistema

### Hardware Mínimo
- **RAM:** 2GB (4GB recomendado)
- **Disco:** 10GB livres
- **Processador:** Dual-core 1.5GHz+
- **Rede:** Conexão à internet estável

### Software Base
- Ubuntu 24.04.2 LTS (servidor ou desktop)
- Usuário com privilégios sudo
- SSH habilitado (para instalação remota)

---

## 🚀 Instalação Completa

### Passo 1: Atualização do Sistema

```bash
# Atualizar repositórios e sistema
sudo apt update && sudo apt upgrade -y

# Instalar ferramentas essenciais
sudo apt install -y curl wget git unzip software-properties-common
```

### Passo 2: Instalação do PHP 8.2

```bash
# Adicionar repositório PHP
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Instalar PHP e extensões necessárias
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-gd \
    php8.2-curl php8.2-zip php8.2-mbstring php8.2-json php8.2-bcmath \
    php8.2-intl php8.2-readline php8.2-cli

# Verificar instalação
php -v
```

### Passo 3: Instalação do MariaDB

```bash
# Instalar MariaDB (mais leve que MySQL)
sudo apt install -y mariadb-server mariadb-client

# Iniciar e habilitar MariaDB
sudo systemctl start mariadb
sudo systemctl enable mariadb

# Configuração de segurança
sudo mysql_secure_installation
```

**Durante mysql_secure_installation:**
- Enter current password: `ENTER` (vazio)
- Set root password: `Y` → Digite uma senha forte
- Remove anonymous users: `Y`
- Disallow root login remotely: `Y`
- Remove test database: `Y`
- Reload privilege tables: `Y`

### Passo 4: Instalação do Nginx

```bash
# Instalar Nginx (mais leve que Apache)
sudo apt install -y nginx

# Iniciar e habilitar Nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# Verificar status
sudo systemctl status nginx
```

### Passo 5: Configuração do Banco de Dados

```bash
# Conectar ao MariaDB
sudo mysql -u root -p

# Dentro do MySQL/MariaDB:
```

```sql
-- Criar banco de dados
CREATE DATABASE capivaralearn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Criar usuário específico
CREATE USER 'capivaralearn'@'localhost' IDENTIFIED BY 'SuaSenhaSegura123!';

-- Conceder privilégios
GRANT ALL PRIVILEGES ON capivaralearn.* TO 'capivaralearn'@'localhost';

-- Recarregar privilégios
FLUSH PRIVILEGES;

-- Sair
EXIT;
```

### Passo 6: Download e Configuração do CapivaraLearn

```bash
# Criar diretório para o projeto
sudo mkdir -p /var/www/capivaralearn
cd /var/www/capivaralearn

# Clonar repositório
sudo git clone https://github.com/carlospintojunior/CapivaraLearn.git .

# Definir permissões
sudo chown -R www-data:www-data /var/www/capivaralearn
sudo chmod -R 755 /var/www/capivaralearn

# Criar diretórios necessários
sudo mkdir -p logs cache backup
sudo chmod -R 777 logs cache backup
```

### Passo 7: Instalação do Composer

```bash
# Download e instalação do Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar dependências do projeto
cd /var/www/capivaralearn
sudo -u www-data composer install --no-dev --optimize-autoloader
```

### Passo 8: Configuração do Ambiente

```bash
# Copiar arquivo de configuração
cd /var/www/capivaralearn
sudo cp includes/config.php.test includes/config.php

# Editar configurações
sudo nano includes/config.php
```

**Configurar em config.php:**
```php
<?php
// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'capivaralearn');
define('DB_USER', 'capivaralearn');
define('DB_PASS', 'SuaSenhaSegura123!');
define('DB_PORT', '3306');

// URL do Sistema
define('BASE_URL', 'http://seu-servidor.com');

// Configurações de Email (opcional)
define('SMTP_HOST', 'seu-smtp.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu-email@dominio.com');
define('SMTP_PASS', 'sua-senha-email');

// Modo de Produção
define('PRODUCTION_MODE', true);
define('DEBUG_MODE', false);
?>
```

### Passo 9: Configuração do Nginx

```bash
# Criar configuração do site
sudo nano /etc/nginx/sites-available/capivaralearn
```

**Conteúdo do arquivo:**
```nginx
server {
    listen 80;
    server_name seu-dominio.com www.seu-dominio.com;
    root /var/www/capivaralearn;
    index index.php index.html index.htm;

    # Configurações de segurança
    server_tokens off;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    # Logs otimizados para pouca RAM
    access_log /var/log/nginx/capivaralearn_access.log;
    error_log /var/log/nginx/capivaralearn_error.log;

    # Configurações PHP
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Arquivos estáticos
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Segurança - bloquear acesso a arquivos sensíveis
    location ~ /\. {
        deny all;
    }

    location ~ /(config|includes|vendor)/ {
        deny all;
    }

    # Redirecionamento para index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

```bash
# Habilitar site
sudo ln -s /etc/nginx/sites-available/capivaralearn /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default

# Testar configuração
sudo nginx -t

# Reiniciar Nginx
sudo systemctl restart nginx
```

### Passo 10: Configuração do PHP-FPM (Otimização para 2GB RAM)

```bash
# Editar configuração do PHP-FPM
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

**Otimizações importantes:**
```ini
; Processo manager
pm = dynamic
pm.max_children = 8
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500

; Limites de memória
php_admin_value[memory_limit] = 128M
php_admin_value[max_execution_time] = 300
php_admin_value[max_input_vars] = 3000
```

```bash
# Editar configurações gerais do PHP
sudo nano /etc/php/8.2/fpm/php.ini
```

**Configurações importantes:**
```ini
memory_limit = 128M
max_execution_time = 300
max_input_vars = 3000
post_max_size = 64M
upload_max_filesize = 32M
```

```bash
# Reiniciar PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Passo 11: Inicialização do Banco de Dados

```bash
# Executar script de instalação
cd /var/www/capivaralearn
php install.php
```

**OU criar manualmente:**
```bash
# Importar estrutura do banco
mysql -u capivaralearn -p capivaralearn < sql/create_database.sql
```

### Passo 12: Configuração de Logs e Monitoramento

```bash
# Configurar logrotate para gerenciar logs
sudo nano /etc/logrotate.d/capivaralearn
```

```
/var/www/capivaralearn/logs/*.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    copytruncate
    create 644 www-data www-data
}
```

### Passo 13: Otimizações para 2GB RAM

#### MariaDB Otimizações:
```bash
sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
```

```ini
[mysqld]
# Otimizações para 2GB RAM
innodb_buffer_pool_size = 512M
innodb_log_file_size = 64M
innodb_log_buffer_size = 8M
max_connections = 50
table_open_cache = 400
query_cache_size = 32M
tmp_table_size = 32M
max_heap_table_size = 32M
```

#### Sistema de Swap:
```bash
# Criar arquivo swap de 1GB
sudo fallocate -l 1G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# Tornar permanente
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

### Passo 14: Configuração de Segurança

#### Firewall:
```bash
# Configurar UFW
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

#### SSL/HTTPS (Opcional com Let's Encrypt):
```bash
# Instalar Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obter certificado
sudo certbot --nginx -d seu-dominio.com -d www.seu-dominio.com
```

### Passo 15: Configuração de Backup Automático

```bash
# Criar script de backup
sudo nano /usr/local/bin/backup-capivaralearn.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/capivaralearn"
DATE=$(date +%Y%m%d_%H%M%S)

# Criar diretório de backup
mkdir -p $BACKUP_DIR

# Backup do banco de dados
mysqldump -u capivaralearn -p'SuaSenhaSegura123!' capivaralearn > $BACKUP_DIR/db_$DATE.sql

# Backup dos arquivos (excluindo vendor e cache)
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C /var/www/capivaralearn \
    --exclude='vendor' --exclude='cache' --exclude='logs' .

# Manter apenas últimos 7 backups
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

```bash
# Tornar executável
sudo chmod +x /usr/local/bin/backup-capivaralearn.sh

# Agendar backup diário
sudo crontab -e
```

Adicionar linha:
```
0 2 * * * /usr/local/bin/backup-capivaralearn.sh
```

---

## 🔍 Verificação da Instalação

### Testes Básicos:
```bash
# Verificar serviços
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mariadb

# Verificar conectividade do banco
mysql -u capivaralearn -p capivaralearn -e "SELECT VERSION();"

# Verificar PHP
php -v
```

### Teste Web:
1. Acesse: `http://seu-servidor.com`
2. Deve aparecer a página de login do CapivaraLearn
3. Teste o registro de novo usuário
4. Verifique os logs em `/var/www/capivaralearn/logs/`

---

## 📈 Monitoramento e Manutenção

### Comandos Úteis:
```bash
# Monitorar uso de RAM
free -h

# Monitorar processos PHP
sudo ps aux | grep php

# Verificar logs de erro
tail -f /var/log/nginx/capivaralearn_error.log
tail -f /var/www/capivaralearn/logs/sistema.log

# Limpar cache
sudo rm -rf /var/www/capivaralearn/cache/*
```

### Atualizações:
```bash
# Atualizar código
cd /var/www/capivaralearn
sudo git pull origin main
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo systemctl restart php8.2-fpm
```

---

## 🆘 Resolução de Problemas

### Problema: "Permission denied"
```bash
sudo chown -R www-data:www-data /var/www/capivaralearn
sudo chmod -R 755 /var/www/capivaralearn
sudo chmod -R 777 logs cache backup
```

### Problema: "Can't connect to database"
```bash
# Verificar serviço MariaDB
sudo systemctl status mariadb

# Testar conexão
mysql -u capivaralearn -p

# Verificar configurações em config.php
```

### Problema: Páginas PHP não carregam
```bash
# Verificar PHP-FPM
sudo systemctl status php8.2-fpm

# Verificar configuração Nginx
sudo nginx -t

# Verificar logs
tail -f /var/log/nginx/capivaralearn_error.log
```

### Problema: Pouca memória
```bash
# Verificar uso atual
free -h
top

# Reduzir processos PHP-FPM
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
# Reduzir pm.max_children para 4-6
```

---

## 📞 Suporte

- **Documentação:** [README.md](README.md)
- **Issues:** GitHub Issues
- **Modelo:** 100% Gratuito com Contribuições Voluntárias

---

## 📄 Licença

CapivaraLearn - Sistema Educacional Livre  
Licença: Open Source  
Modelo: Contribuição Voluntária após 1 ano de uso

---

---

## 📝 Registro de Instalação em Produção

### Instalação Realizada em: 21/07/2025
**Servidor:** Ubuntu 24.04.2 LTS - IP: 198.23.132.15

#### Etapa 1: Configuração do Servidor Base
```bash
# 1. Atualização do sistema
apt update && apt upgrade -y

# 2. Instalação do repositório PHP
add-apt-repository ppa:ondrej/php -y
apt update

# 3. Instalação do PHP 8.2 e extensões
apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-gd \
               php8.2-curl php8.2-zip php8.2-mbstring php8.2-bcmath \
               php8.2-intl php8.2-readline php8.2-cli

# 4. Instalação do Nginx e MariaDB
apt install -y nginx mariadb-server curl

# 5. Inicialização dos serviços
systemctl start mariadb nginx php8.2-fpm
systemctl enable mariadb nginx php8.2-fpm
```

#### Etapa 2: Configuração do Banco de Dados
```bash
# 1. Configuração da senha do root do MariaDB
mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY 'capivara2025'; FLUSH PRIVILEGES;"

# 2. Criação do banco e usuário da aplicação
mysql -u root -pcapivara2025 -e "
CREATE DATABASE capivaralearn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'capivaralearn'@'localhost' IDENTIFIED BY 'capivara2025';
GRANT ALL PRIVILEGES ON capivaralearn.* TO 'capivaralearn'@'localhost';
FLUSH PRIVILEGES;"
```

#### Etapa 3: Configuração do Nginx
```bash
# 1. Criação do virtual host
cat > /etc/nginx/sites-available/capivaralearn << 'EOF'
server {
    listen 80;
    server_name 198.23.132.15;
    root /var/www/capivaralearn;
    index index.php index.html index.htm install.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    # Proteger arquivos sensíveis
    location ~ /(vendor|logs|backup)/.*$ {
        deny all;
    }
}
EOF

# 2. Ativação do site
ln -sf /etc/nginx/sites-available/capivaralearn /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
```

#### Etapa 4: Instalação do Composer
```bash
cd /tmp
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

#### Etapa 5: Deploy da Aplicação

##### Método Utilizado: Script Automatizado `deploy-putty.sh`

**Configuração Local:**
```bash
# Configurações do script deploy-putty.sh
PPK_KEY="/home/carlos/Nextcloud/Documents/ppk/capivaralearn.ppk"
SERVER="root@198.23.132.15"
LOCAL_PATH="/home/carlos/Documents/GitHub/CapivaraLearn"
REMOTE_PATH="/var/www/capivaralearn"
```

**Comandos Executados:**
```bash
# 1. Deploy fresh (limpeza completa + upload + dependências)
./deploy-putty.sh fresh

# Processo executado pelo script:
# - Backup automático dos arquivos existentes
# - Remoção completa do diretório /var/www/capivaralearn
# - Recriação do diretório com permissões corretas
# - Upload de todos os arquivos via pscp
# - Configuração de permissões (www-data:www-data)
# - Instalação das dependências PHP via composer
```

#### Etapa 6: Correção de Configurações Hardcoded

**Problema Identificado:** Arquivos com configuração de banco hardcoded (`'username' => 'root'`)

**Solução Aplicada:**
```bash
# 1. Identificação dos arquivos problemáticos
find . -name "*.php" -not -path "./vendor/*" -exec grep -l "'username' => 'root'" {} \;

# 2. Correção em massa
find . -name "*.php" -not -path "./vendor/*" -exec grep -l "'username' => 'root'" {} \; | xargs sed -i "s/'username' => 'root',/'username' => DB_USER,/g"
find . -name "*.php" -not -path "./vendor/*" -exec grep -l "'password' => ''" {} \; | xargs sed -i "s/'password' => '',/'password' => DB_PASS,/g"
find . -name "*.php" -not -path "./vendor/*" -exec grep -l "'host' => 'localhost'" {} \; | xargs sed -i "s/'host' => 'localhost',/'host' => DB_HOST,/g"
find . -name "*.php" -not -path "./vendor/*" -exec grep -l "'database' => 'capivaralearn'" {} \; | xargs sed -i "s/'database' => 'capivaralearn',/'database' => DB_NAME,/g"

# 3. Nova sincronização
./deploy-putty.sh fresh
```

#### Etapa 7: Instalação Final das Dependências
```bash
# No servidor, instalação manual das dependências PHP
cd /var/www/capivaralearn
composer install --no-dev --optimize-autoloader
```

### Resultado Final

**URLs Funcionais:**
- ✅ Site Principal: http://198.23.132.15/
- ✅ Login: http://198.23.132.15/login.php
- ✅ Dashboard: http://198.23.132.15/dashboard.php (requer login)
- ✅ Instalação: http://198.23.132.15/install.php

**Credenciais do Banco:**
- Host: localhost
- Database: capivaralearn
- Username: capivaralearn
- Password: capivara2025

**Logs de Verificação:**
```
[22-Jul-2025 00:18:55 UTC] DASHBOARD: Usuário não logado, redirecionando
```
*(Comportamento correto - sistema funcional)*

### Lições Aprendidas

1. **Deploy Fresh é Essencial**: Quando há problemas de configuração, limpar completamente o servidor garante ambiente limpo
2. **Configurações Hardcoded**: Necessário verificar e corrigir TODOS os arquivos com configurações hardcoded antes do deploy
3. **Dependências Automáticas**: Script de deploy deve incluir instalação automática de dependências
4. **Verificação Sistemática**: Logs ajudam a identificar rapidamente problemas de configuração

### Melhorias Implementadas no Script de Deploy

```bash
# Comando "fresh" melhorado
./deploy-putty.sh fresh
# Agora inclui: clean_server + upload_files + set_permissions + install_dependencies
```

---

**🎉 Parabéns! Seu CapivaraLearn está instalado e funcionando!**

O sistema está configurado para funcionar de forma otimizada em servidores com recursos limitados. Lembre-se de monitorar regularmente o uso de recursos e fazer backups periódicos.
