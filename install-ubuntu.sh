#!/bin/bash

# CapivaraLearn - Script de Instalação Automatizada
# Ubuntu 24.04.2 LTS - Otimizado para 2GB RAM
# Versão 0.7.0 - Community Model

set -e  # Exit on any error

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funções utilitárias
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCESSO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[AVISO]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERRO]${NC} $1"
}

# Verificar se está sendo executado como root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        print_error "Este script não deve ser executado como root. Use sudo quando necessário."
        exit 1
    fi
}

# Verificar sistema Ubuntu
check_ubuntu() {
    if [[ ! -f /etc/os-release ]] || ! grep -q "Ubuntu" /etc/os-release; then
        print_error "Este script é específico para Ubuntu. Sistema não suportado."
        exit 1
    fi
    
    VERSION=$(grep VERSION_ID /etc/os-release | cut -d'"' -f2)
    if [[ ! "$VERSION" =~ ^24\. ]]; then
        print_warning "Este script foi testado no Ubuntu 24.04. Versão detectada: $VERSION"
        read -p "Deseja continuar? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

# Coletar informações do usuário
collect_info() {
    echo "=== Configuração do CapivaraLearn ==="
    echo
    
    # Senha do root do MySQL
    while true; do
        read -s -p "Digite uma senha para o root do MariaDB: " MYSQL_ROOT_PASS
        echo
        read -s -p "Confirme a senha: " MYSQL_ROOT_PASS_CONFIRM
        echo
        if [[ "$MYSQL_ROOT_PASS" = "$MYSQL_ROOT_PASS_CONFIRM" ]]; then
            break
        else
            print_error "Senhas não coincidem. Tente novamente."
        fi
    done
    
    # Senha do usuário do banco
    while true; do
        read -s -p "Digite uma senha para o usuário 'capivaralearn' do banco: " DB_PASS
        echo
        read -s -p "Confirme a senha: " DB_PASS_CONFIRM
        echo
        if [[ "$DB_PASS" = "$DB_PASS_CONFIRM" ]]; then
            break
        else
            print_error "Senhas não coincidem. Tente novamente."
        fi
    done
    
    # Domínio/IP do servidor
    read -p "Digite o domínio ou IP do servidor (ex: meusite.com): " DOMAIN
    if [[ -z "$DOMAIN" ]]; then
        DOMAIN="localhost"
        print_warning "Usando 'localhost' como domínio padrão."
    fi
    
    # Confirmação
    echo
    echo "=== Resumo da Configuração ==="
    echo "Domínio: $DOMAIN"
    echo "Usuário do banco: capivaralearn"
    echo "Nome do banco: capivaralearn"
    echo
    read -p "Deseja continuar com esta configuração? (Y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Nn]$ ]]; then
        print_error "Instalação cancelada pelo usuário."
        exit 1
    fi
}

# Verificar recursos do sistema
check_resources() {
    print_status "Verificando recursos do sistema..."
    
    # Verificar RAM
    RAM_MB=$(free -m | awk 'NR==2{printf "%.0f", $2}')
    if [[ $RAM_MB -lt 1800 ]]; then
        print_error "Sistema com menos de 2GB de RAM detectado ($RAM_MB MB). Instalação pode falhar."
        read -p "Deseja continuar mesmo assim? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    else
        print_success "RAM disponível: ${RAM_MB}MB"
    fi
    
    # Verificar espaço em disco
    DISK_GB=$(df / | awk 'NR==2 {printf "%.1f", $4/1024/1024}')
    if (( $(echo "$DISK_GB < 8.0" | bc -l) )); then
        print_error "Menos de 8GB de espaço livre disponível ($DISK_GB GB)."
        exit 1
    else
        print_success "Espaço em disco disponível: ${DISK_GB}GB"
    fi
}

# Atualizar sistema
update_system() {
    print_status "Atualizando sistema..."
    sudo apt update -qq
    sudo apt upgrade -y -qq
    sudo apt install -y -qq curl wget git unzip software-properties-common bc
    print_success "Sistema atualizado com sucesso."
}

# Instalar PHP
install_php() {
    print_status "Instalando PHP 8.2..."
    
    sudo add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1
    sudo apt update -qq
    
    sudo apt install -y -qq php8.2 php8.2-fpm php8.2-mysql php8.2-xml \
        php8.2-gd php8.2-curl php8.2-zip php8.2-mbstring php8.2-json \
        php8.2-bcmath php8.2-intl php8.2-readline php8.2-cli
    
    # Configurar PHP para ambientes com pouca RAM
    sudo sed -i 's/memory_limit = .*/memory_limit = 128M/' /etc/php/8.2/fpm/php.ini
    sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.2/fpm/php.ini
    sudo sed -i 's/max_input_vars = .*/max_input_vars = 3000/' /etc/php/8.2/fpm/php.ini
    sudo sed -i 's/post_max_size = .*/post_max_size = 64M/' /etc/php/8.2/fpm/php.ini
    sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 32M/' /etc/php/8.2/fpm/php.ini
    
    # Configurar PHP-FPM para 2GB RAM
    sudo sed -i 's/pm.max_children = .*/pm.max_children = 8/' /etc/php/8.2/fpm/pool.d/www.conf
    sudo sed -i 's/pm.start_servers = .*/pm.start_servers = 2/' /etc/php/8.2/fpm/pool.d/www.conf
    sudo sed -i 's/pm.min_spare_servers = .*/pm.min_spare_servers = 1/' /etc/php/8.2/fpm/pool.d/www.conf
    sudo sed -i 's/pm.max_spare_servers = .*/pm.max_spare_servers = 3/' /etc/php/8.2/fpm/pool.d/www.conf
    sudo sed -i 's/;pm.max_requests = .*/pm.max_requests = 500/' /etc/php/8.2/fpm/pool.d/www.conf
    
    sudo systemctl restart php8.2-fpm
    print_success "PHP 8.2 instalado e configurado."
}

# Instalar MariaDB
install_mariadb() {
    print_status "Instalando MariaDB..."
    
    sudo apt install -y -qq mariadb-server mariadb-client
    sudo systemctl start mariadb
    sudo systemctl enable mariadb
    
    # Configuração automática de segurança
    sudo mysql -e "UPDATE mysql.user SET Password=PASSWORD('$MYSQL_ROOT_PASS') WHERE User='root';"
    sudo mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    sudo mysql -e "DELETE FROM mysql.user WHERE User='';"
    sudo mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
    sudo mysql -e "FLUSH PRIVILEGES;"
    
    # Configurar MariaDB para 2GB RAM
    sudo tee /etc/mysql/mariadb.conf.d/99-capivaralearn.cnf > /dev/null <<EOF
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
EOF
    
    sudo systemctl restart mariadb
    print_success "MariaDB instalado e configurado."
}

# Instalar Nginx
install_nginx() {
    print_status "Instalando Nginx..."
    
    sudo apt install -y -qq nginx
    sudo systemctl start nginx
    sudo systemctl enable nginx
    
    print_success "Nginx instalado."
}

# Configurar banco de dados
setup_database() {
    print_status "Configurando banco de dados..."
    
    mysql -u root -p"$MYSQL_ROOT_PASS" <<EOF
CREATE DATABASE IF NOT EXISTS capivaralearn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'capivaralearn'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON capivaralearn.* TO 'capivaralearn'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    print_success "Banco de dados configurado."
}

# Baixar e configurar CapivaraLearn
setup_capivaralearn() {
    print_status "Baixando CapivaraLearn..."
    
    sudo mkdir -p /var/www/capivaralearn
    cd /tmp
    git clone https://github.com/carlospintojunior/CapivaraLearn.git
    
    sudo cp -r CapivaraLearn/* /var/www/capivaralearn/
    sudo chown -R www-data:www-data /var/www/capivaralearn
    sudo chmod -R 755 /var/www/capivaralearn
    
    # Criar diretórios necessários
    sudo mkdir -p /var/www/capivaralearn/{logs,cache,backup}
    sudo chmod -R 777 /var/www/capivaralearn/{logs,cache,backup}
    
    print_success "CapivaraLearn baixado e configurado."
}

# Instalar Composer e dependências
install_composer() {
    print_status "Instalando Composer e dependências..."
    
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    
    cd /var/www/capivaralearn
    sudo -u www-data composer install --no-dev --optimize-autoloader --quiet
    
    print_success "Composer e dependências instaladas."
}

# Configurar arquivo de configuração
setup_config() {
    print_status "Configurando arquivo de configuração..."
    
    cd /var/www/capivaralearn
    
    # Criar arquivo de configuração
    sudo tee includes/config.php > /dev/null <<EOF
<?php
// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'capivaralearn');
define('DB_USER', 'capivaralearn');
define('DB_PASS', '$DB_PASS');
define('DB_PORT', '3306');

// URL do Sistema
define('BASE_URL', 'http://$DOMAIN');

// Configurações de Email (configure posteriormente se necessário)
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');

// Modo de Produção
define('PRODUCTION_MODE', true);
define('DEBUG_MODE', false);

// Configurações de Segurança
define('HASH_SECRET', '$(openssl rand -base64 32)');
define('SESSION_TIMEOUT', 3600);
?>
EOF
    
    sudo chown www-data:www-data includes/config.php
    sudo chmod 644 includes/config.php
    
    print_success "Arquivo de configuração criado."
}

# Configurar Nginx
setup_nginx() {
    print_status "Configurando Nginx..."
    
    sudo tee /etc/nginx/sites-available/capivaralearn > /dev/null <<EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    root /var/www/capivaralearn;
    index index.php index.html index.htm;

    # Configurações de segurança
    server_tokens off;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    # Logs
    access_log /var/log/nginx/capivaralearn_access.log;
    error_log /var/log/nginx/capivaralearn_error.log;

    # Configurações PHP
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    # Arquivos estáticos
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Segurança - bloquear acesso a arquivos sensíveis
    location ~ /\. {
        deny all;
    }

    location ~ /(config|includes|vendor|logs|backup)/ {
        deny all;
    }

    # Redirecionamento para index.php
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
}
EOF
    
    sudo ln -sf /etc/nginx/sites-available/capivaralearn /etc/nginx/sites-enabled/
    sudo rm -f /etc/nginx/sites-enabled/default
    
    sudo nginx -t
    sudo systemctl restart nginx
    
    print_success "Nginx configurado."
}

# Inicializar banco de dados
init_database() {
    print_status "Inicializando banco de dados..."
    
    cd /var/www/capivaralearn
    
    # Verificar se existe script de instalação
    if [[ -f install.php ]]; then
        php install.php
    elif [[ -f sql/create_database.sql ]]; then
        mysql -u capivaralearn -p"$DB_PASS" capivaralearn < sql/create_database.sql
    else
        print_warning "Script de inicialização do banco não encontrado. Execute manualmente após a instalação."
    fi
    
    print_success "Banco de dados inicializado."
}

# Configurar swap
setup_swap() {
    print_status "Configurando arquivo de swap..."
    
    if [[ ! -f /swapfile ]]; then
        sudo fallocate -l 1G /swapfile
        sudo chmod 600 /swapfile
        sudo mkswap /swapfile >/dev/null
        sudo swapon /swapfile
        
        # Tornar permanente
        if ! grep -q "/swapfile" /etc/fstab; then
            echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab >/dev/null
        fi
        
        print_success "Arquivo de swap de 1GB criado."
    else
        print_warning "Arquivo de swap já existe."
    fi
}

# Configurar firewall
setup_firewall() {
    print_status "Configurando firewall..."
    
    sudo ufw --force enable >/dev/null 2>&1
    sudo ufw allow ssh >/dev/null 2>&1
    sudo ufw allow 'Nginx Full' >/dev/null 2>&1
    
    print_success "Firewall configurado."
}

# Configurar backup automático
setup_backup() {
    print_status "Configurando backup automático..."
    
    sudo mkdir -p /var/backups/capivaralearn
    
    sudo tee /usr/local/bin/backup-capivaralearn.sh > /dev/null <<EOF
#!/bin/bash
BACKUP_DIR="/var/backups/capivaralearn"
DATE=\$(date +%Y%m%d_%H%M%S)

mkdir -p \$BACKUP_DIR

# Backup do banco de dados
mysqldump -u capivaralearn -p'$DB_PASS' capivaralearn > \$BACKUP_DIR/db_\$DATE.sql

# Backup dos arquivos
tar -czf \$BACKUP_DIR/files_\$DATE.tar.gz -C /var/www/capivaralearn \\
    --exclude='vendor' --exclude='cache' --exclude='logs' .

# Manter apenas últimos 7 backups
find \$BACKUP_DIR -name "*.sql" -mtime +7 -delete
find \$BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
EOF
    
    sudo chmod +x /usr/local/bin/backup-capivaralearn.sh
    
    # Agendar backup diário
    (sudo crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup-capivaralearn.sh") | sudo crontab -
    
    print_success "Backup automático configurado (diário às 02:00)."
}

# Função de teste
test_installation() {
    print_status "Testando instalação..."
    
    # Verificar serviços
    if ! sudo systemctl is-active --quiet nginx; then
        print_error "Nginx não está rodando."
        return 1
    fi
    
    if ! sudo systemctl is-active --quiet php8.2-fpm; then
        print_error "PHP-FPM não está rodando."
        return 1
    fi
    
    if ! sudo systemctl is-active --quiet mariadb; then
        print_error "MariaDB não está rodando."
        return 1
    fi
    
    # Testar conexão com banco
    if ! mysql -u capivaralearn -p"$DB_PASS" -e "SELECT 1;" >/dev/null 2>&1; then
        print_error "Não foi possível conectar ao banco de dados."
        return 1
    fi
    
    print_success "Todos os serviços estão funcionando."
    return 0
}

# Função principal
main() {
    echo "=================================="
    echo "  CapivaraLearn - Instalação"
    echo "  Versão 0.7.0 - Community Model"
    echo "  Ubuntu 24.04.2 LTS"
    echo "=================================="
    echo
    
    check_root
    check_ubuntu
    collect_info
    check_resources
    
    echo
    print_status "Iniciando instalação..."
    echo
    
    update_system
    install_php
    install_mariadb
    install_nginx
    setup_database
    setup_capivaralearn
    install_composer
    setup_config
    setup_nginx
    init_database
    setup_swap
    setup_firewall
    setup_backup
    
    echo
    if test_installation; then
        print_success "============================================"
        print_success "  CapivaraLearn instalado com sucesso!"
        print_success "============================================"
        echo
        echo "Acesse seu sistema em: http://$DOMAIN"
        echo
        echo "Informações importantes:"
        echo "- Diretório: /var/www/capivaralearn"
        echo "- Logs: /var/www/capivaralearn/logs/"
        echo "- Backup automático: configurado (diário às 02:00)"
        echo "- Banco: capivaralearn"
        echo "- Usuário do banco: capivaralearn"
        echo
        echo "Para atualizações futuras:"
        echo "cd /var/www/capivaralearn && sudo git pull"
        echo
        print_success "Sistema pronto para uso! 🎉"
    else
        print_error "Instalação completada com erros. Verifique os logs."
        exit 1
    fi
}

# Executar instalação
main "$@"
