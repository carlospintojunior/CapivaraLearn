#!/bin/bash

# CapivaraLearn - Script de Deploy para Produção
# Servidor: 198.23.132.15
# Usuário: root
# Chave SSH: /home/carlos/Nextcloud/Documents/ppk/capivaralearn.ppk

set -e  # Exit on any error

# Configurações do servidor
SSH_KEY="/home/carlos/Nextcloud/Documents/ppk/capivaralearn.ppk"
SERVER_USER="root"
SERVER_HOST="198.23.132.15"
SERVER_PATH="/var/www/capivaralearn"
LOCAL_PATH="/home/carlos/Documents/GitHub/CapivaraLearn"

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[DEPLOY]${NC} $1"
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

# Verificar se a chave SSH existe
check_ssh_key() {
    if [[ ! -f "$SSH_KEY" ]]; then
        print_error "Chave SSH não encontrada: $SSH_KEY"
        exit 1
    fi
    print_success "Chave SSH encontrada."
}

# Verificar conectividade com o servidor
test_connection() {
    print_status "Testando conexão SSH..."
    if ssh -i "$SSH_KEY" -o ConnectTimeout=10 -o StrictHostKeyChecking=no "$SERVER_USER@$SERVER_HOST" "echo 'Conexão OK'" >/dev/null 2>&1; then
        print_success "Conexão SSH estabelecida com sucesso."
    else
        print_error "Falha na conexão SSH. Verifique as credenciais e conectividade."
        exit 1
    fi
}

# Criar backup no servidor antes do deploy
create_server_backup() {
    print_status "Criando backup no servidor..."
    ssh -i "$SSH_KEY" "$SERVER_USER@$SERVER_HOST" << 'EOF'
        if [[ -d /var/www/capivaralearn ]]; then
            mkdir -p /var/backups/capivaralearn-deploy
            BACKUP_NAME="capivaralearn-pre-deploy-$(date +%Y%m%d_%H%M%S)"
            tar -czf "/var/backups/capivaralearn-deploy/${BACKUP_NAME}.tar.gz" -C /var/www capivaralearn
            echo "Backup criado: ${BACKUP_NAME}.tar.gz"
        else
            echo "Primeira instalação - sem backup necessário"
        fi
EOF
    print_success "Backup do servidor criado."
}

# Sincronizar arquivos para o servidor
sync_files() {
    print_status "Sincronizando arquivos para o servidor..."
    
    # Criar diretório no servidor se não existir
    ssh -i "$SSH_KEY" "$SERVER_USER@$SERVER_HOST" "mkdir -p $SERVER_PATH"
    
    # Usar rsync para sincronização eficiente
    rsync -avz --delete \
        --exclude='.git/' \
        --exclude='vendor/' \
        --exclude='cache/' \
        --exclude='logs/' \
        --exclude='.vscode/' \
        --exclude='*.log' \
        -e "ssh -i $SSH_KEY -o StrictHostKeyChecking=no" \
        "$LOCAL_PATH/" "$SERVER_USER@$SERVER_HOST:$SERVER_PATH/"
    
    print_success "Arquivos sincronizados com sucesso."
}

# Configurar permissões no servidor
set_permissions() {
    print_status "Configurando permissões..."
    ssh -i "$SSH_KEY" "$SERVER_USER@$SERVER_HOST" << 'EOF'
        cd /var/www/capivaralearn
        
        # Definir dono como www-data
        chown -R www-data:www-data .
        
        # Permissões gerais
        find . -type f -exec chmod 644 {} \;
        find . -type d -exec chmod 755 {} \;
        
        # Scripts executáveis
        chmod +x install-ubuntu.sh
        
        # Diretórios especiais
        mkdir -p logs cache backup
        chmod -R 777 logs cache backup
        
        # Arquivos de configuração
        if [[ -f includes/config.php ]]; then
            chmod 600 includes/config.php
        fi
EOF
    print_success "Permissões configuradas."
}

# Executar instalação no servidor (se necessário)
run_installation() {
    print_status "Verificando se é necessário executar instalação..."
    
    # Verificar se já existe configuração
    NEEDS_INSTALL=$(ssh -i "$SSH_KEY" "$SERVER_USER@$SERVER_HOST" << 'EOF'
        if [[ ! -f /var/www/capivaralearn/includes/config.php ]] || [[ ! -d /var/www/capivaralearn/vendor ]]; then
            echo "YES"
        else
            echo "NO"
        fi
EOF
    )
    
    if [[ "$NEEDS_INSTALL" == "YES" ]]; then
        print_warning "Sistema não configurado. Executando instalação..."
        
        read -p "Deseja executar a instalação automatizada no servidor? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            ssh -i "$SSH_KEY" "$SERVER_USER@$SERVER_HOST" << 'EOF'
                cd /var/www/capivaralearn
                chmod +x install-ubuntu.sh
                ./install-ubuntu.sh
EOF
        else
            print_warning "Instalação manual necessária. Execute:"
            print_warning "ssh -i $SSH_KEY $SERVER_USER@$SERVER_HOST"
            print_warning "cd /var/www/capivaralearn && ./install-ubuntu.sh"
        fi
    else
        print_success "Sistema já instalado e configurado."
    fi
}

# Atualizar dependências
update_dependencies() {
    print_status "Atualizando dependências..."
    ssh -i "$SSH_KEY" "$SERVER_USER@$SERVER_HOST" << 'EOF'
        cd /var/www/capivaralearn
        
        # Instalar/atualizar Composer se necessário
        if [[ ! -f /usr/local/bin/composer ]]; then
            curl -sS https://getcomposer.org/installer | php
            mv composer.phar /usr/local/bin/composer
        fi
        
        # Atualizar dependências
        sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction
EOF
    print_success "Dependências atualizadas."
}

# Reiniciar serviços
restart_services() {
    print_status "Reiniciando serviços..."
    ssh -i "$SSH_KEY" "$SERVER_USER@$SERVER_HOST" << 'EOF'
        systemctl restart php8.2-fpm nginx
        systemctl status php8.2-fpm nginx --no-pager
EOF
    print_success "Serviços reiniciados."
}

# Verificar status do deploy
verify_deployment() {
    print_status "Verificando deployment..."
    
    # Verificar se o site está respondendo
    RESPONSE=$(ssh -i "$SSH_KEY" "$SERVER_USER@$SERVER_HOST" "curl -s -o /dev/null -w '%{http_code}' http://localhost/ || echo 'ERROR'")
    
    if [[ "$RESPONSE" == "200" ]]; then
        print_success "Site está respondendo corretamente (HTTP 200)."
    else
        print_error "Site não está respondendo adequadamente. Código: $RESPONSE"
        print_warning "Verifique os logs:"
        ssh -i "$SSH_KEY" "$SERVER_USER@$SERVER_HOST" "tail -20 /var/log/nginx/error.log"
    fi
    
    # Verificar logs de erro
    print_status "Verificando logs recentes..."
    ssh -i "$SSH_KEY" "$SERVER_USER@$SERVER_HOST" << 'EOF'
        echo "=== Últimos erros Nginx ==="
        tail -5 /var/log/nginx/error.log 2>/dev/null || echo "Nenhum erro no Nginx"
        
        echo "=== Últimos erros PHP ==="
        tail -5 /var/log/php8.2-fpm.log 2>/dev/null || echo "Nenhum erro no PHP-FPM"
        
        echo "=== Status dos serviços ==="
        systemctl is-active nginx php8.2-fpm mariadb
EOF
}

# Menu principal
show_menu() {
    echo "=================================="
    echo "  CapivaraLearn - Deploy Script"
    echo "  Servidor: $SERVER_HOST"
    echo "=================================="
    echo
    echo "1) Deploy completo (recomendado)"
    echo "2) Apenas sincronizar arquivos"
    echo "3) Apenas atualizar dependências"
    echo "4) Apenas reiniciar serviços"
    echo "5) Verificar status"
    echo "6) Executar instalação"
    echo "0) Sair"
    echo
    read -p "Escolha uma opção: " choice
    
    case $choice in
        1) full_deploy ;;
        2) sync_files && set_permissions ;;
        3) update_dependencies ;;
        4) restart_services ;;
        5) verify_deployment ;;
        6) run_installation ;;
        0) exit 0 ;;
        *) print_error "Opção inválida!" && show_menu ;;
    esac
}

# Deploy completo
full_deploy() {
    print_status "Iniciando deploy completo..."
    echo
    
    check_ssh_key
    test_connection
    create_server_backup
    sync_files
    set_permissions
    update_dependencies
    restart_services
    verify_deployment
    
    echo
    print_success "================================================"
    print_success "  Deploy do CapivaraLearn concluído!"
    print_success "================================================"
    echo
    echo "🌐 Acesse: http://$SERVER_HOST"
    echo "📊 Logs: ssh -i $SSH_KEY $SERVER_USER@$SERVER_HOST 'tail -f /var/www/capivaralearn/logs/sistema.log'"
    echo "🔧 SSH: ssh -i $SSH_KEY $SERVER_USER@$SERVER_HOST"
    echo
}

# Verificar argumentos da linha de comando
if [[ $# -eq 0 ]]; then
    show_menu
else
    case $1 in
        "full"|"deploy") full_deploy ;;
        "sync") sync_files && set_permissions ;;
        "deps") update_dependencies ;;
        "restart") restart_services ;;
        "verify") verify_deployment ;;
        "install") run_installation ;;
        *) 
            echo "Uso: $0 [full|sync|deps|restart|verify|install]"
            echo "  full     - Deploy completo"
            echo "  sync     - Sincronizar arquivos"
            echo "  deps     - Atualizar dependências"
            echo "  restart  - Reiniciar serviços"
            echo "  verify   - Verificar status"
            echo "  install  - Executar instalação"
            exit 1
        ;;
    esac
fi
