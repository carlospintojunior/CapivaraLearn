#!/bin/bash
# CapivaraLearn - Deploy script (Desenvolvimento → Produção)
# Replica o comportamento do sync_to_xampp.sh, mas enviando para o servidor público

set -euo pipefail

# ===== Configurações padrão (pode sobrescrever via variáveis de ambiente) =====
SERVER_HOST="${SERVER_HOST:-root@198.23.132.15}"
SERVER_PATH="${SERVER_PATH:-/var/www/capivaralearn}"
SSH_KEY="${SSH_KEY:-/home/carlos/Nextcloud/Documents/ppk/capivaralearn.ppk}"
REMOTE_OWNER="${REMOTE_OWNER:-www-data}"
REMOTE_GROUP="${REMOTE_GROUP:-www-data}"
REMOTE_FILE_MODE="${REMOTE_FILE_MODE:-644}"
REMOTE_DIR_MODE="${REMOTE_DIR_MODE:-755}"
REMOTE_LOG_DIR_MODE="${REMOTE_LOG_DIR_MODE:-775}"
REMOTE_LOG_FILE_MODE="${REMOTE_LOG_FILE_MODE:-664}"

PACKAGE_PATH="/tmp/capivaralearn_prod_sync_$$.tar.gz"
REMOTE_BACKUP="/tmp/capivaralearn_preserve_$$"

PSCP_CMD=(pscp -batch -i "$SSH_KEY")
PLINK_CMD=(plink -batch -i "$SSH_KEY")

cleanup() {
    rm -f "$PACKAGE_PATH" 2>/dev/null || true
    if command -v plink >/dev/null 2>&1; then
        plink -batch -i "$SSH_KEY" "$SERVER_HOST" "rm -rf '$REMOTE_BACKUP'" >/dev/null 2>&1 || true
    fi
}
trap cleanup EXIT

# ===== Funções auxiliares =====
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

run_remote() {
    "${PLINK_CMD[@]}" "$SERVER_HOST" "$1"
}

backup_remote_dir() {
    local remote_subpath="$1"
    local label="${2:-$remote_subpath}"
    local remote_dir
    remote_dir=$(dirname "$remote_subpath")
    if [ "$remote_dir" = "." ]; then
        remote_dir=""
    fi

    if run_remote "[ -d '$SERVER_PATH/$remote_subpath' ]"; then
        echo "  ✓ Salvando $label"
        run_remote "mkdir -p '$REMOTE_BACKUP/$remote_dir' && mv '$SERVER_PATH/$remote_subpath' '$REMOTE_BACKUP/$remote_subpath'"
    fi
}

backup_remote_file() {
    local remote_subpath="$1"
    local label="${2:-$remote_subpath}"
    local remote_dir
    remote_dir=$(dirname "$remote_subpath")
    if [ "$remote_dir" = "." ]; then
        remote_dir=""
    fi

    if run_remote "[ -f '$SERVER_PATH/$remote_subpath' ]"; then
        echo "  ✓ Salvando $label"
        run_remote "mkdir -p '$REMOTE_BACKUP/$remote_dir' && mv '$SERVER_PATH/$remote_subpath' '$REMOTE_BACKUP/$remote_subpath'"
    fi
}

restore_remote_dir() {
    local remote_subpath="$1"
    local label="${2:-$remote_subpath}"
    local target_dir
    target_dir=$(dirname "$remote_subpath")
    if [ "$target_dir" = "." ]; then
        target_dir=""
    fi

    if run_remote "[ -d '$REMOTE_BACKUP/$remote_subpath' ]"; then
        echo "  ↩️  Restaurando $label"
        run_remote "mkdir -p '$SERVER_PATH/$target_dir' && mv '$REMOTE_BACKUP/$remote_subpath' '$SERVER_PATH/$remote_subpath'"
    fi
}

restore_remote_file() {
    local remote_subpath="$1"
    local label="${2:-$remote_subpath}"
    local target_dir
    target_dir=$(dirname "$remote_subpath")
    if [ "$target_dir" = "." ]; then
        target_dir=""
    fi

    if run_remote "[ -f '$REMOTE_BACKUP/$remote_subpath' ]"; then
        echo "  ↩️  Restaurando $label"
        run_remote "mkdir -p '$SERVER_PATH/$target_dir' && mv '$REMOTE_BACKUP/$remote_subpath' '$SERVER_PATH/$remote_subpath'"
    fi
}

# ===== Pré-checagens =====
if [ ! -f "README.md" ] || [ ! -d "includes" ]; then
    echo "❌ Execute este script a partir da raiz do projeto (CapivaraLearn)."
    exit 1
fi

if ! command_exists pscp; then
    echo "❌ pscp não encontrado (necessário PuTTY). Adicione-o ao PATH."
    exit 1
fi

if ! command_exists plink; then
    echo "❌ plink não encontrado (necessário PuTTY). Adicione-o ao PATH."
    exit 1
fi

echo "🔄 Iniciando sincronização CapivaraLearn → Produção"
echo "  Host: $SERVER_HOST"
echo "  Caminho remoto: $SERVER_PATH"
echo ""

# Confirmar acesso ao servidor
echo "🔐 Validando acesso ao servidor..."
run_remote "echo 'Conexão estabelecida com ' \$(hostname)" || {
    echo "❌ Não foi possível conectar ao servidor ($SERVER_HOST)."
    exit 1
}

echo "📋 Opções de sincronização:"
read -p "Preservar variáveis de ambiente (environment.ini)? (S/n): " PRESERVE_ENVIRONMENT
PRESERVE_ENVIRONMENT=${PRESERVE_ENVIRONMENT:-S}

read -p "Preservar dados de usuário (backup/, cache/, mídias de testes)? (S/n): " PRESERVE_USER_DATA
PRESERVE_USER_DATA=${PRESERVE_USER_DATA:-S}

echo ""
echo "💾 Realizando backup remoto antes da sincronização..."

run_remote "rm -rf '$REMOTE_BACKUP' && mkdir -p '$REMOTE_BACKUP'"
backup_remote_dir "logs"

if [[ "$PRESERVE_ENVIRONMENT" =~ ^[Ss]$ ]]; then
    echo "- Variáveis de ambiente:"
    backup_remote_file "includes/environment.ini"
fi

if [[ "$PRESERVE_USER_DATA" =~ ^[Ss]$ ]]; then
    echo "- Dados de usuário:"
    backup_remote_dir "backup"
    backup_remote_dir "cache"
    backup_remote_dir "public/assets/videos/testes_especiais" "vídeos de testes especiais"
    backup_remote_dir "public/assets/images/testes_especiais" "imagens de testes especiais"
fi

echo ""
echo "📦 Empacotando projeto local..."
tar -czf "$PACKAGE_PATH" \
    --exclude=".git" \
    --exclude=".github" \
    --exclude=".gitignore" \
    --exclude=".DS_Store" \
    --exclude="logs" \
    --exclude="*.log" \
    .

if [ ! -s "$PACKAGE_PATH" ]; then
    echo "❌ Falha ao criar pacote local."
    exit 1
fi

echo "🚚 Enviando pacote para o servidor..."
"${PSCP_CMD[@]}" "$PACKAGE_PATH" "$SERVER_HOST:/tmp/capivaralearn_prod_sync.tar.gz" >/dev/null

echo "🗑️  Limpando diretório remoto atual..."
run_remote "rm -rf '$SERVER_PATH' && mkdir -p '$SERVER_PATH'"

echo "📥 Extraindo pacote no servidor..."
run_remote "tar -xzf /tmp/capivaralearn_prod_sync.tar.gz -C '$SERVER_PATH' --strip-components=1"
run_remote "rm -f /tmp/capivaralearn_prod_sync.tar.gz"

echo ""
echo "🔄 Restaurando arquivos preservados..."
restore_remote_dir "logs"

if [[ "$PRESERVE_ENVIRONMENT" =~ ^[Ss]$ ]]; then
    restore_remote_file "includes/environment.ini"
fi

if [[ "$PRESERVE_USER_DATA" =~ ^[Ss]$ ]]; then
    restore_remote_dir "backup"
    restore_remote_dir "cache"
    restore_remote_dir "public/assets/videos/testes_especiais" "vídeos de testes especiais"
    restore_remote_dir "public/assets/images/testes_especiais" "imagens de testes especiais"
fi

run_remote "rm -rf '$REMOTE_BACKUP'"

echo ""
echo "🔧 Ajustando permissões no servidor..."
run_remote "chown -R '$REMOTE_OWNER:$REMOTE_GROUP' '$SERVER_PATH'"
run_remote "find '$SERVER_PATH' -type d -exec chmod $REMOTE_DIR_MODE {} \;"
run_remote "find '$SERVER_PATH' -type f -exec chmod $REMOTE_FILE_MODE {} \;"
run_remote "chmod -R $REMOTE_LOG_DIR_MODE '$SERVER_PATH/logs' 2>/dev/null || true"
run_remote "find '$SERVER_PATH/logs' -type f -exec chmod $REMOTE_LOG_FILE_MODE {} \; 2>/dev/null || true"

echo ""
echo "✅ Sincronização concluída com sucesso!"
echo "📊 Resumo:"
echo "   • Arquivos copiados para produção"
echo "   • environment.ini preservado: $([[ "$PRESERVE_ENVIRONMENT" =~ ^[Ss]$ ]] && echo "Sim" || echo "Não")"
echo "   • Dados de usuário preservados: $([[ "$PRESERVE_USER_DATA" =~ ^[Ss]$ ]] && echo "Sim" || echo "Não")"
echo ""
echo "🌐 Verifique em: https://capivaralearn.com.br"
