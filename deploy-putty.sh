#!/bin/bash

# CapivaraLearn - Deploy usando PuTTY Tools (pscp/plink)
# Para Windows/Linux com PuTTY tools instalados

set -e

# Configurações
PPK_KEY="/home/carlos/Nextcloud/Documents/ppk/capivaralearn.ppk"
SERVER="root@198.23.132.15"
LOCAL_PATH="/home/carlos/Documents/GitHub/CapivaraLearn"
REMOTE_PATH="/var/www/capivaralearn"

# Cores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

print_status() { echo -e "${BLUE}[DEPLOY]${NC} $1"; }
print_success() { echo -e "${GREEN}[OK]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[AVISO]${NC} $1"; }
print_error() { echo -e "${RED}[ERRO]${NC} $1"; }

# Verificar se PuTTY tools estão disponíveis
check_putty_tools() {
    if ! command -v pscp &> /dev/null; then
        print_error "pscp não encontrado. Instale PuTTY tools."
        exit 1
    fi
    
    if ! command -v plink &> /dev/null; then
        print_error "plink não encontrado. Instale PuTTY tools."
        exit 1
    fi
    
    print_success "PuTTY tools encontrados."
}

# Verificar chave SSH
check_key() {
    if [[ ! -f "$PPK_KEY" ]]; then
        print_error "Chave PPK não encontrada: $PPK_KEY"
        exit 1
    fi
    print_success "Chave PPK encontrada."
}

# Testar conexão
test_connection() {
    print_status "Testando conexão..."
    if plink -batch -i "$PPK_KEY" "$SERVER" "echo 'Conexão OK'" >/dev/null 2>&1; then
        print_success "Conexão estabelecida."
    else
        print_error "Falha na conexão. Verifique credenciais."
        exit 1
    fi
}

# Criar backup no servidor
backup_server() {
    print_status "Criando backup no servidor..."
    plink -batch -i "$PPK_KEY" "$SERVER" << 'EOF'
        if [[ -d /var/www/capivaralearn ]]; then
            mkdir -p /var/backups/capivaralearn-deploy
            BACKUP_NAME="backup-$(date +%Y%m%d_%H%M%S)"
            tar -czf "/var/backups/capivaralearn-deploy/${BACKUP_NAME}.tar.gz" -C /var/www capivaralearn
            echo "Backup: ${BACKUP_NAME}.tar.gz"
        fi
        exit
EOF
    print_success "Backup criado."
}

# Preparar arquivos locais
prepare_files() {
    print_status "Preparando arquivos para upload..."
    
    cd "$LOCAL_PATH"
    
    # Criar arquivo temporário com lista de arquivos a enviar
    find . -type f \
        ! -path './.git/*' \
        ! -path './vendor/*' \
        ! -path './cache/*' \
        ! -path './logs/*' \
        ! -path './.vscode/*' \
        ! -name '*.log' \
        > /tmp/capivaralearn_files.txt
    
    print_success "Lista de arquivos preparada: $(wc -l < /tmp/capivaralearn_files.txt) arquivos."
}

# Upload de arquivos
upload_files() {
    print_status "Fazendo upload dos arquivos..."
    
    # Criar diretório remoto
    plink -batch -i "$PPK_KEY" "$SERVER" "mkdir -p $REMOTE_PATH"
    
    # Upload usando pscp
    cd "$LOCAL_PATH"
    
    # Upload de arquivos principais
    print_status "Uploading arquivos PHP..."
    pscp -batch -i "$PPK_KEY" -r -q \
        *.php *.md *.json *.lock *.html *.txt *.sh \
        "$SERVER:$REMOTE_PATH/" 2>/dev/null || true
    
    # Upload de diretórios
    for dir in includes public src templates ajax api assets config crud sql; do
        if [[ -d "$dir" ]]; then
            print_status "Uploading diretório: $dir"
            pscp -batch -i "$PPK_KEY" -r -q "$dir" "$SERVER:$REMOTE_PATH/" 2>/dev/null || true
        fi
    done
    
    print_success "Upload concluído."
}

# Configurar permissões
set_permissions() {
    print_status "Configurando permissões..."
    plink -batch -i "$PPK_KEY" "$SERVER" << 'EOF'
        cd /var/www/capivaralearn
        
        # Definir dono
        chown -R www-data:www-data .
        
        # Permissões
        find . -type f -exec chmod 644 {} \;
        find . -type d -exec chmod 755 {} \;
        
        # Scripts executáveis
        chmod +x *.sh
        
        # Diretórios especiais
        mkdir -p logs cache backup
        chmod -R 777 logs cache backup
        
        echo "Permissões configuradas."
        exit
EOF
    print_success "Permissões definidas."
}

# Instalar dependências
install_dependencies() {
    print_status "Instalando dependências..."
    plink -batch -i "$PPK_KEY" "$SERVER" << 'EOF'
        cd /var/www/capivaralearn
        
        # Instalar Composer se necessário
        if [[ ! -f /usr/local/bin/composer ]]; then
            curl -sS https://getcomposer.org/installer | php
            mv composer.phar /usr/local/bin/composer
            chmod +x /usr/local/bin/composer
        fi
        
        # Instalar dependências PHP
        composer install --no-dev --optimize-autoloader --no-interaction
        
        echo "Dependências instaladas."
        exit
EOF
    print_success "Dependências instaladas."
}

# Reiniciar serviços
restart_services() {
    print_status "Reiniciando serviços..."
    plink -batch -i "$PPK_KEY" "$SERVER" << 'EOF'
        systemctl restart php8.2-fpm nginx
        echo "Serviços reiniciados."
        exit
EOF
    print_success "Serviços reiniciados."
}

# Verificar deployment
verify_deploy() {
    print_status "Verificando deployment..."
    RESULT=$(plink -batch -i "$PPK_KEY" "$SERVER" "curl -s -o /dev/null -w '%{http_code}' http://localhost/ || echo 'ERROR'")
    
    if [[ "$RESULT" == "200" ]]; then
        print_success "Site funcionando (HTTP 200)."
    else
        print_error "Problema no site. Código: $RESULT"
    fi
}

# Executar instalação se necessário
run_installation() {
    print_status "Verificando necessidade de instalação..."
    
    NEEDS_INSTALL=$(plink -batch -i "$PPK_KEY" "$SERVER" << 'EOF'
        if [[ ! -f /var/www/capivaralearn/includes/config.php ]]; then
            echo "YES"
        else
            echo "NO"
        fi
        exit
EOF
    )
    
    if [[ "$NEEDS_INSTALL" == "YES" ]]; then
        print_warning "Sistema precisa ser instalado."
        read -p "Executar instalação automatizada? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            plink -batch -i "$PPK_KEY" "$SERVER" << 'EOF'
                cd /var/www/capivaralearn
                chmod +x install-ubuntu.sh
                ./install-ubuntu.sh
                exit
EOF
        fi
    else
        print_success "Sistema já configurado."
    fi
}

# Limpeza completa do servidor
clean_server() {
    print_warning "=== LIMPEZA COMPLETA DO SERVIDOR ==="
    print_warning "Isso irá APAGAR todos os arquivos do CapivaraLearn no servidor!"
    read -p "Confirma a limpeza completa? (digite 'CONFIRMO'): " confirm
    
    if [[ "$confirm" != "CONFIRMO" ]]; then
        print_error "Operação cancelada."
        exit 1
    fi
    
    print_status "Fazendo backup antes da limpeza..."
    backup_server
    
    print_status "Removendo arquivos do servidor..."
    plink -batch -i "$PPK_KEY" "$SERVER" << 'EOF'
        cd /var/www
        rm -rf capivaralearn
        mkdir -p capivaralearn
        chown www-data:www-data capivaralearn
        echo "Diretório limpo e recriado."
        exit
EOF
    print_success "Limpeza completa realizada."
}

# Deploy completo
full_deploy() {
    print_status "=== Deploy Completo do CapivaraLearn ==="
    echo
    
    check_putty_tools
    check_key
    test_connection
    backup_server
    prepare_files
    upload_files
    set_permissions
    install_dependencies
    restart_services
    verify_deploy
    
    echo
    print_success "======================================="
    print_success "  Deploy concluído com sucesso!"
    print_success "======================================="
    echo
    echo "🌐 Site: http://198.23.132.15"
    echo "🔑 SSH: plink -i $PPK_KEY $SERVER"
    echo
}

# Menu
case "${1:-menu}" in
    "full"|"deploy")
        full_deploy
        ;;
    "upload")
        check_putty_tools && check_key && test_connection
        upload_files && set_permissions
        ;;
    "install")
        check_putty_tools && check_key && test_connection
        run_installation
        ;;
    "restart")
        check_putty_tools && check_key && test_connection
        restart_services
        ;;
    "clean")
        check_putty_tools && check_key && test_connection
        clean_server
        ;;
    "fresh")
        check_putty_tools && check_key && test_connection
        clean_server
        upload_files && set_permissions && install_dependencies
        print_success "Deploy fresh concluído!"
        ;;
    "verify")
        check_putty_tools && check_key && test_connection
        verify_deploy
        ;;
    *)
        echo "CapivaraLearn Deploy (PuTTY Version)"
        echo "Uso: $0 [comando]"
        echo
        echo "Comandos:"
        echo "  full     - Deploy completo"
        echo "  upload   - Apenas upload de arquivos"
        echo "  clean    - Limpeza completa do servidor"
        echo "  fresh    - Limpeza + upload completo"
        echo "  install  - Executar instalação"
        echo "  restart  - Reiniciar serviços"
        echo "  verify   - Verificar status"
        echo
        echo "Servidor: 198.23.132.15"
        echo "Chave: $PPK_KEY"
        ;;
esac
