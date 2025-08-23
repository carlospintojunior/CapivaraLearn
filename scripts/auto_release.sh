#!/bin/bash

# CapivaraLearn - Automated Release Script
# Automatiza completamente o processo de criação de releases

set -e  # Exit on any error

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para log colorido
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Verificar se estamos no diretório correto
if [ ! -f "dashboard.php" ] || [ ! -d ".git" ]; then
    log_error "Este script deve ser executado no diretório raiz do CapivaraLearn"
    exit 1
fi

# Verificar se Python 3 está disponível
if ! command -v python3 &> /dev/null; then
    log_error "Python 3 é necessário para executar este script"
    exit 1
fi

# Instalar dependências Python se necessário
log_info "Verificando dependências Python..."
if ! python3 -c "import requests" 2>/dev/null; then
    log_warning "Instalando dependência 'requests'..."
    pip3 install requests --user
fi

# Criar diretório scripts se não existir
mkdir -p scripts

log_info "🚀 Iniciando processo automatizado de release..."

# 1. Verificar status do Git
log_info "Verificando status do repositório Git..."
if [ -n "$(git status --porcelain)" ]; then
    log_warning "Existem mudanças não commitadas. Deseja continuar? (y/n)"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        log_info "Operação cancelada pelo usuário."
        exit 0
    fi
fi

# 2. Fazer pull das últimas mudanças
log_info "Sincronizando com repositório remoto..."
git fetch origin
git pull origin "$(git branch --show-current)"

# 3. Executar gerador de release
log_info "Gerando release notes automaticamente..."
python3 scripts/generate_release.py

# Verificar se o arquivo foi gerado
if [ ! -f "RELEASE_NOTES.md" ]; then
    log_error "Falha ao gerar release notes"
    exit 1
fi

log_success "Release notes geradas com sucesso!"

# 4. Mostrar preview do release
echo ""
echo "================================ PREVIEW DO RELEASE ================================"
cat RELEASE_NOTES.md
echo "=================================================================================="
echo ""

# 5. Confirmar criação do release
log_warning "Deseja criar o release com estas informações? (y/n)"
read -r response
if [[ ! "$response" =~ ^[Yy]$ ]]; then
    log_info "Operação cancelada pelo usuário."
    log_info "Release notes salvas em RELEASE_NOTES.md para revisão."
    exit 0
fi

# 6. Extrair versão do arquivo de release notes
VERSION=$(grep -m 1 "# CapivaraLearn" RELEASE_NOTES.md | sed 's/# CapivaraLearn //')
if [ -z "$VERSION" ]; then
    log_error "Não foi possível extrair a versão do release notes"
    exit 1
fi

log_info "Versão identificada: $VERSION"

# 7. Commit das release notes
log_info "Commitando release notes..."
git add RELEASE_NOTES.md
git add scripts/
git commit -m "docs: prepare release notes for $VERSION" || log_warning "Nenhuma mudança para commit"

# 8. Criar tag
log_info "Criando tag $VERSION..."
if git tag -l | grep -q "^$VERSION$"; then
    log_warning "Tag $VERSION já existe. Removendo..."
    git tag -d "$VERSION"
    git push origin ":refs/tags/$VERSION" 2>/dev/null || true
fi

# Criar tag com release notes
git tag -a "$VERSION" -F RELEASE_NOTES.md

# 9. Push das mudanças
log_info "Enviando mudanças para o repositório..."
git push origin "$(git branch --show-current)"
git push origin "$VERSION"

# 10. Criar release no GitHub (se gh CLI estiver disponível)
if command -v gh &> /dev/null; then
    log_info "Criando release no GitHub..."
    gh release create "$VERSION" \
        --title "CapivaraLearn $VERSION" \
        --notes-file RELEASE_NOTES.md \
        --latest
    
    log_success "Release $VERSION criado com sucesso no GitHub!"
else
    log_warning "GitHub CLI (gh) não encontrado."
    log_info "Você pode instalar com: apt install gh"
    log_info "Ou criar o release manualmente em:"
    log_info "https://github.com/carlospintojunior/CapivaraLearn/releases/new?tag=$VERSION"
fi

# 11. Limpar arquivos temporários
log_info "Limpando arquivos temporários..."
# Manter RELEASE_NOTES.md para referência

# 12. Mostrar resumo final
echo ""
log_success "🎉 Release $VERSION criado com sucesso!"
echo ""
echo "📋 Resumo do que foi feito:"
echo "   ✅ Release notes geradas automaticamente"
echo "   ✅ Tag $VERSION criada"
echo "   ✅ Mudanças enviadas para o repositório"
if command -v gh &> /dev/null; then
    echo "   ✅ Release criado no GitHub"
else
    echo "   ⚠️  Release precisa ser criado manualmente no GitHub"
fi
echo ""
echo "🔗 Links úteis:"
echo "   📄 Release notes: ./RELEASE_NOTES.md"
echo "   🏷️  Tag: https://github.com/carlospintojunior/CapivaraLearn/tree/$VERSION"
echo "   📦 Releases: https://github.com/carlospintojunior/CapivaraLearn/releases"
echo ""

# 13. Sugerir próximos passos
log_info "💡 Próximos passos sugeridos:"
echo "   1. Verificar o release no GitHub"
echo "   2. Testar o deployment em produção"
echo "   3. Comunicar o release aos usuários"
echo "   4. Planejar próximas features"

log_success "Processo automatizado de release concluído! 🚀"
