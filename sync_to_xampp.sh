#!/bin/bash
# CapivaraLearn - Script de Sincronização Desenvolvimento → Produção
# Este script sincroniza o diretório de desenvolvimento para o XAMPP
# PRESERVANDO arquivos críticos (logs, configurações, uploads)

echo "🔄 Iniciando sincronização CapivaraLearn..."
echo ""

# Verificar se estamos no diretório correto
if [ ! -f "README.md" ] || [ ! -d "includes" ]; then
    echo "❌ Erro: Execute este script no diretório raiz do CapivaraLearn"
    echo "📁 Diretório atual: $(pwd)"
    echo "📁 Esperado: /home/carlos/Documents/GitHub/CapivaraLearn"
    exit 1
fi

# ===== OPÇÕES DE PRESERVAÇÃO =====
echo "📋 Opções de sincronização:"
echo ""
echo "Deseja preservar arquivos de configuração do XAMPP?"
echo "  - includes/config.php (configurações do banco de dados)"
echo "  - includes/environment.ini (variáveis de ambiente)"
echo ""
read -p "Preservar configurações? (S/n): " PRESERVE_CONFIG
PRESERVE_CONFIG=${PRESERVE_CONFIG:-S}  # Default: S (Sim)

echo ""
echo "Deseja preservar arquivos de usuário?"
echo "  - backup/ (backups de dados)"
echo "  - cache/ (cache do sistema)"
echo ""
read -p "Preservar dados de usuário? (S/n): " PRESERVE_USER_DATA
PRESERVE_USER_DATA=${PRESERVE_USER_DATA:-S}  # Default: S (Sim)

echo ""

# Navegar para o diretório de desenvolvimento
cd /home/carlos/Documents/GitHub/CapivaraLearn

echo "📂 Diretório de desenvolvimento: $(pwd)"
echo ""

# ===== BACKUP DE ARQUIVOS A PRESERVAR =====
BACKUP_DIR="/tmp/capivaralearn_sync_backup_$$"
mkdir -p "$BACKUP_DIR"

# PRESERVAR LOGS (sempre)
if [ -d "/opt/lampp/htdocs/CapivaraLearn/logs" ]; then
    echo "💾 Fazendo backup dos logs existentes..."
    sudo cp -r /opt/lampp/htdocs/CapivaraLearn/logs "$BACKUP_DIR/"
    echo "✅ Logs salvos"
fi

# PRESERVAR CONFIGURAÇÕES (se solicitado)
if [[ "$PRESERVE_CONFIG" =~ ^[Ss]$ ]]; then
    echo "💾 Fazendo backup das configurações..."
    
    if [ -f "/opt/lampp/htdocs/CapivaraLearn/includes/config.php" ]; then
        sudo cp /opt/lampp/htdocs/CapivaraLearn/includes/config.php "$BACKUP_DIR/config.php"
        echo "  ✓ config.php salvo"
    fi
    
    if [ -f "/opt/lampp/htdocs/CapivaraLearn/includes/environment.ini" ]; then
        sudo cp /opt/lampp/htdocs/CapivaraLearn/includes/environment.ini "$BACKUP_DIR/environment.ini"
        echo "  ✓ environment.ini salvo"
    fi
fi

# PRESERVAR DADOS DE USUÁRIO (se solicitado)
if [[ "$PRESERVE_USER_DATA" =~ ^[Ss]$ ]]; then
    echo "💾 Fazendo backup dos dados de usuário..."
    
    if [ -d "/opt/lampp/htdocs/CapivaraLearn/backup" ]; then
        sudo cp -r /opt/lampp/htdocs/CapivaraLearn/backup "$BACKUP_DIR/"
        echo "  ✓ backup/ salvo"
    fi
    
    if [ -d "/opt/lampp/htdocs/CapivaraLearn/cache" ]; then
        sudo cp -r /opt/lampp/htdocs/CapivaraLearn/cache "$BACKUP_DIR/"
        echo "  ✓ cache/ salvo"
    fi
fi

echo ""

echo "🗑️  Removendo instalação anterior..."
sudo rm -rf /opt/lampp/htdocs/CapivaraLearn

echo "📋 Copiando arquivos para XAMPP..."
sudo cp -r . /opt/lampp/htdocs/CapivaraLearn

echo ""
echo "🔄 Restaurando arquivos preservados..."

# RESTAURAR LOGS (sempre)
if [ -d "$BACKUP_DIR/logs" ]; then
    echo "  ↩️  Restaurando logs..."
    sudo cp -r "$BACKUP_DIR/logs" /opt/lampp/htdocs/CapivaraLearn/
    echo "  ✅ Logs restaurados"
fi

# RESTAURAR CONFIGURAÇÕES (se foram preservadas)
if [[ "$PRESERVE_CONFIG" =~ ^[Ss]$ ]]; then
    echo "  ↩️  Restaurando configurações..."
    
    if [ -f "$BACKUP_DIR/config.php" ]; then
        sudo cp "$BACKUP_DIR/config.php" /opt/lampp/htdocs/CapivaraLearn/includes/config.php
        echo "    ✓ config.php restaurado"
    fi
    
    if [ -f "$BACKUP_DIR/environment.ini" ]; then
        sudo cp "$BACKUP_DIR/environment.ini" /opt/lampp/htdocs/CapivaraLearn/includes/environment.ini
        echo "    ✓ environment.ini restaurado"
    fi
fi

# RESTAURAR DADOS DE USUÁRIO (se foram preservados)
if [[ "$PRESERVE_USER_DATA" =~ ^[Ss]$ ]]; then
    echo "  ↩️  Restaurando dados de usuário..."
    
    if [ -d "$BACKUP_DIR/backup" ]; then
        sudo cp -r "$BACKUP_DIR/backup" /opt/lampp/htdocs/CapivaraLearn/
        echo "    ✓ backup/ restaurado"
    fi
    
    if [ -d "$BACKUP_DIR/cache" ]; then
        sudo cp -r "$BACKUP_DIR/cache" /opt/lampp/htdocs/CapivaraLearn/
        echo "    ✓ cache/ restaurado"
    fi
fi

# Limpar diretório temporário de backup
sudo rm -rf "$BACKUP_DIR"
echo ""

echo "🔐 Configurando proprietário (daemon:daemon)..."
sudo chown -R daemon:daemon /opt/lampp/htdocs/CapivaraLearn 

echo "📄 Configurando permissões de arquivos (644)..."
sudo chmod -R 644 /opt/lampp/htdocs/CapivaraLearn 

echo "📁 Configurando permissões de diretórios (755)..."
sudo find /opt/lampp/htdocs/CapivaraLearn -type d -exec chmod 755 {} \;

echo "📝 Criando diretório de logs..."
sudo mkdir -p /opt/lampp/htdocs/CapivaraLearn/logs

echo "🔓 Configurando permissões do diretório de logs (777)..."
sudo chmod 777 /opt/lampp/htdocs/CapivaraLearn/logs

echo "📄 Criando arquivo de log de erros PHP..."
sudo touch /opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log

echo "🔓 Configurando permissões do arquivo de log (666)..."
sudo chmod 666 /opt/lampp/htdocs/CapivaraLearn/logs/php_errors.log

# Verificações adicionais
if [ -d "vendor" ]; then
    echo "📦 Verificando dependências do Composer..."
    if [ ! -d "/opt/lampp/htdocs/CapivaraLearn/vendor/phpmailer" ]; then
        echo "⚠️  PHPMailer não encontrado, pode ser necessário executar 'composer install'"
    fi
fi

echo ""
echo "✅ Sincronização concluída com sucesso!"
echo ""
echo "📊 Resumo:"
echo "   • Arquivos copiados: ✓"
echo "   • Logs preservados: ✓"
if [[ "$PRESERVE_CONFIG" =~ ^[Ss]$ ]]; then
    echo "   • Configurações preservadas: ✓"
else
    echo "   • Configurações: ⚠️  SOBRESCRITAS (use install.php se necessário)"
fi
if [[ "$PRESERVE_USER_DATA" =~ ^[Ss]$ ]]; then
    echo "   • Dados de usuário preservados: ✓"
fi
echo ""
echo "🌐 Acesse: http://localhost/CapivaraLearn/"
if [[ ! "$PRESERVE_CONFIG" =~ ^[Ss]$ ]]; then
    echo "🔧 Reconfigurar: http://localhost/CapivaraLearn/install.php"
fi
echo ""
echo "📊 Permissões aplicadas:"
echo "   • Arquivos: 644 (rw-r--r--)"  
echo "   • Diretórios: 755 (rwxr-xr-x)"
echo "   • Diretório logs: 777 (rwxrwxrwx)"
echo "   • Arquivo logs: 666 (rw-rw-rw-)"
echo "   • Proprietário: daemon:daemon"
echo ""
