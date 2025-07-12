#!/bin/bash
# CapivaraLearn - Script de Sincronização Desenvolvimento → Produção
# Este script sincroniza o diretório de desenvolvimento para o XAMPP
# PRESERVANDO OS LOGS existentes

echo "🔄 Iniciando sincronização CapivaraLearn..."

# Verificar se estamos no diretório correto
if [ ! -f "README.md" ] || [ ! -d "includes" ]; then
    echo "❌ Erro: Execute este script no diretório raiz do CapivaraLearn"
    echo "📁 Diretório atual: $(pwd)"
    echo "📁 Esperado: /home/carlos/Documents/GitHub/CapivaraLearn"
    exit 1
fi

# Navegar para o diretório de desenvolvimento
cd /home/carlos/Documents/GitHub/CapivaraLearn

echo "📂 Diretório de desenvolvimento: $(pwd)"

# PRESERVAR LOGS: Fazer backup dos logs se existirem
if [ -d "/opt/lampp/htdocs/CapivaraLearn/logs" ]; then
    echo "💾 Fazendo backup dos logs existentes..."
    sudo cp -r /opt/lampp/htdocs/CapivaraLearn/logs /tmp/capivaralearn_logs_backup
    echo "✅ Logs salvos em /tmp/capivaralearn_logs_backup"
fi

echo "🗑️  Removendo instalação anterior..."
sudo rm -r /opt/lampp/htdocs/CapivaraLearn

echo "📋 Copiando arquivos para XAMPP..."
sudo cp -r . /opt/lampp/htdocs/CapivaraLearn

# RESTAURAR LOGS: Restaurar os logs se existir backup
if [ -d "/tmp/capivaralearn_logs_backup" ]; then
    echo "🔄 Restaurando logs preservados..."
    sudo cp -r /tmp/capivaralearn_logs_backup/* /opt/lampp/htdocs/CapivaraLearn/logs/
    sudo rm -r /tmp/capivaralearn_logs_backup
    echo "✅ Logs restaurados com sucesso!"
fi

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
echo "🌐 Acesse: http://localhost/CapivaraLearn/"
echo "🔧 Instalar: http://localhost/CapivaraLearn/install.php"
echo ""
echo "📊 Resumo das permissões aplicadas:"
echo "   • Arquivos: 644 (rw-r--r--)"  
echo "   • Diretórios: 755 (rwxr-xr-x)"
echo "   • Diretório logs: 777 (rwxrwxrwx)"
echo "   • Arquivo logs: 666 (rw-rw-rw-)"
echo "   • Proprietário: daemon:daemon"
echo ""
