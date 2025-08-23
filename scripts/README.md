# 🚀 Sistema Automatizado de Releases - CapivaraLearn

Este sistema automatiza completamente o processo de criação de releases, baseado nas issues fechadas desde o último release, seguindo o padrão do ThingsBoard.

## ✨ Funcionalidades

- 📋 **Análise automática** de issues fechadas
- 🏷️ **Categorização inteligente** (features, bugs, melhorias, documentação)
- 📝 **Geração automática** de changelog no formato ThingsBoard
- 🔢 **Versionamento automático** (semântico)
- 📊 **Estatísticas detalhadas** do release
- 🏷️ **Criação automática** de tags Git
- 🐙 **Criação automática** de releases no GitHub

## 🚀 Como Usar

### Método 1: Comando Simples (Recomendado)

```bash
# Executar no diretório raiz do CapivaraLearn
./create_release.sh
```

### Método 2: Script Completo

```bash
# Tornar executável (primeira vez)
chmod +x scripts/auto_release.sh

# Executar
./scripts/auto_release.sh
```

### Método 3: Manual (Para customização)

```bash
# 1. Gerar apenas as release notes
python3 scripts/generate_release.py

# 2. Revisar RELEASE_NOTES.md

# 3. Criar release manualmente
git add RELEASE_NOTES.md
git commit -m "docs: prepare release notes"
git tag -a v0.8.0 -F RELEASE_NOTES.md
git push origin v0.8.0
```

## 📋 Processo Automatizado

O sistema executa as seguintes etapas:

1. **🔍 Análise**: Busca issues fechadas desde o último release
2. **📂 Categorização**: Classifica issues automaticamente
3. **📝 Geração**: Cria changelog no formato ThingsBoard
4. **🔢 Versionamento**: Incrementa versão automaticamente
5. **✅ Commit**: Adiciona release notes ao repositório
6. **🏷️ Tag**: Cria tag Git com a nova versão
7. **📤 Push**: Envia tudo para o repositório
8. **🐙 GitHub**: Cria release automaticamente (se gh CLI disponível)

## 🏷️ Categorização Automática

### 🚀 New Features
- Palavras-chave: `feature`, `adicionar`, `implementar`, `criar`, `new`, `feat`
- Issues com labels: `feature`, `new-feature`, `feat`

### 🐛 Bug Fixes  
- Palavras-chave: `bug`, `fix`, `erro`, `corrigir`, `correção`, `hotfix`
- Issues com labels: `bug`, `fix`, `hotfix`, `bugfix`

### ⚡ Improvements
- Palavras-chave: `melhorar`, `otimizar`, `aprimorar`, `improvement`, `enhance`
- Issues com labels: `enhancement`, `improvement`, `refactor`

### 📚 Documentation
- Palavras-chave: `documentação`, `docs`, `readme`, `documentation`
- Issues com labels: `documentation`, `docs`

## 📊 Exemplo de Changelog Gerado

```markdown
# CapivaraLearn v0.8.0

*Release Date: August 23, 2025*

## 📋 Release Overview

This release includes 3 resolved issues with significant improvements to user experience, new features, and important bug fixes.

## 🚀 New Features

- **Acesso rápido a UAs** (#52) - Click on learning units in dashboard to edit directly

## ⚡ Improvements

- **Enhanced Grade Display** - Show grades for all units (except incomplete with zero grade)
- **Visual Status Updates** - Pending status now displayed in orange for better visibility

## 🐛 Bug Fixes

- **Fixed singular/plural text display** - Corrected deadline text formatting

## 📊 Release Statistics

- **Issues Resolved**: 3
- **Files Modified**: 5+ files  
- **Commits**: 12+ commits
- **Contributors**: 1

## 🔗 Useful Links

- [Full Changelog](https://github.com/carlospintojunior/CapivaraLearn/compare/v0.7.1...v0.8.0)
- [Documentation](https://github.com/carlospintojunior/CapivaraLearn#readme)
- [Issues](https://github.com/carlospintojunior/CapivaraLearn/issues)
```

## ⚙️ Configuração

Edite `scripts/release_config.ini` para personalizar:

- Estratégia de versionamento
- Palavras-chave para categorização
- Incluir/excluir seções do changelog
- Configurações de deployment automático

## 📦 Dependências

- **Python 3** com `requests`
- **Git** configurado
- **GitHub CLI** (opcional, para criação automática de releases)

### Instalar GitHub CLI (Opcional)

```bash
# Ubuntu/Debian
sudo apt install gh

# Ou via snap
sudo snap install gh

# Autenticar
gh auth login
```

## 🔧 Resolução de Problemas

### Erro: "requests module not found"
```bash
pip3 install requests --user
```

### Erro: "GitHub CLI not found"
- O sistema funcionará sem gh CLI
- Release será criado como tag, precisando ser convertido manualmente no GitHub

### Erro: "Permission denied"
```bash
chmod +x create_release.sh
chmod +x scripts/auto_release.sh
```

## 🚀 Próximos Passos Após Release

1. ✅ Verificar release no GitHub
2. 🔄 Fazer deploy em produção  
3. 📢 Comunicar release aos usuários
4. 📋 Planejar próximas features
5. 🏷️ Fechar milestone (se usar)

## 💡 Dicas

- Execute sempre que fechar várias issues
- Revise o changelog antes de confirmar
- Use labels nas issues para melhor categorização
- Mantenha títulos de issues descritivos
- Feche issues com commits: `git commit -m "feat: nova funcionalidade - closes #52"`

---

**🐾 CapivaraLearn** - Sistema de planejamento de estudos modulares para EaD
