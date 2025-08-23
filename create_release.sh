#!/bin/bash

# CapivaraLearn - Quick Release Command
# Comando simples para gerar release: ./create_release.sh

echo "🐾 CapivaraLearn - Automated Release Generator"
echo "=============================================="

# Verificar se estamos no diretório correto
if [ ! -f "dashboard.php" ]; then
    echo "❌ Execute este script no diretório raiz do CapivaraLearn"
    exit 1
fi

# Tornar scripts executáveis
chmod +x scripts/auto_release.sh

# Executar script principal
./scripts/auto_release.sh
