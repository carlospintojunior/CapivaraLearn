# CapivaraLearn Setup Script
# Execute como Administrador ou ajuste a política: Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser

Write-Host ""
Write-Host "================================" -ForegroundColor Cyan
Write-Host "🐾 CAPIVARA LEARN - SETUP" -ForegroundColor Yellow
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Verificar se Node.js está instalado
try {
    $nodeVersion = node --version
    Write-Host "✅ Node.js encontrado: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Node.js não encontrado! Instale primeiro: https://nodejs.org" -ForegroundColor Red
    Read-Host "Pressione Enter para continuar mesmo assim..."
}

# Verificar se Git está instalado
try {
    $gitVersion = git --version
    Write-Host "✅ Git encontrado: $gitVersion" -ForegroundColor Green
} catch {
    Write-Host "⚠️ Git não encontrado. Recomendamos instalar: https://git-scm.com" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Criando estrutura do projeto CapivaraLearn..." -ForegroundColor Blue

# Criar diretório principal
New-Item -ItemType Directory -Name "CapivaraLearn" -Force | Out-Null
Set-Location "CapivaraLearn"

# Criar estrutura de pastas
$folders = @(
    "src",
    "src/css",
    "src/js", 
    "src/components",
    "src/assets",
    "src/assets/icons",
    "src/assets/images",
    "public",
    "docs",
    "tests",
    ".vscode"
)

foreach ($folder in $folders) {
    New-Item -ItemType Directory -Path $folder -Force | Out-Null
}

Write-Host "✅ Estrutura de pastas criada!" -ForegroundColor Green

# Criar package.json
$packageJson = @"
{
  "name": "capivara-learn",
  "version": "1.0.0",
  "description": "Sistema de planejamento de estudos modulares - CapivaraLearn 🐾",
  "main": "src/js/app.js",
  "scripts": {
    "dev": "live-server public --port=3000 --open=/",
    "start": "live-server public --port=3000",
    "build": "echo 'Build não configurado ainda'",
    "test": "echo 'Testes não configurados ainda'",
    "lint": "echo 'ESLint não configurado ainda'",
    "format": "echo 'Prettier não configurado ainda'",
    "setup": "npm install && echo 'CapivaraLearn configurado! Execute: npm run dev'"
  },
  "keywords": ["estudos", "planejamento", "fisioterapia", "educação", "capivara", "modular"],
  "author": "Equipe CapivaraLearn",
  "license": "MIT",
  "repository": {
    "type": "git",
    "url": "https://github.com/seu-usuario/capivara-learn.git"
  },
  "devDependencies": {
    "live-server": "^1.2.2"
  },
  "engines": {
    "node": ">=14.0.0"
  }
}
"@

$packageJson | Out-File -FilePath "package.json" -Encoding UTF8
Write-Host "✅ package.json criado!" -ForegroundColor Green

# Criar .gitignore
$gitignore = @"
# Dependências
node_modules/
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# Arquivos de ambiente
.env
.env.local
.env.development.local
.env.test.local
.env.production.local

# Build
dist/
build/
*.tgz
*.tar.gz

# Sistema Operacional
.DS_Store
.DS_Store?
._*
.Spotlight-V100
.Trashes
ehthumbs.db
Thumbs.db

# IDEs
.vscode/settings.json
.idea/
*.swp
*.swo
*~

# Logs
logs
*.log

# Temporary files
*.tmp
*.temp
"@

$gitignore | Out-File -FilePath ".gitignore" -Encoding UTF8
Write-Host "✅ .gitignore criado!" -ForegroundColor Green

# Criar README.md
$readme = @"
# 🐾 CapivaraLearn

**Sistema de planejamento de estudos modulares para fisioterapia**

[![MIT License](https://img.shields.io/badge/License-MIT-green.svg)](https://choosealicense.com/licenses/mit/)
[![Node.js](https://img.shields.io/badge/Node.js-14+-green.svg)](https://nodejs.org/)

## 🎯 Sobre o Projeto

O CapivaraLearn é uma ferramenta desenvolvida especificamente para estudantes de fisioterapia que precisam organizar seus estudos em formato modular. Cada módulo possui tópicos com datas específicas de abertura e fechamento, facilitando o acompanhamento do cronograma acadêmico.

## ✨ Funcionalidades

- ✅ **Gestão de Módulos**: Crie e organize módulos de estudo
- ✅ **Controle de Tópicos**: Adicione tópicos com datas e conteúdo
- ✅ **Acompanhamento Visual**: Status colorido para cada tópico
- ✅ **Progresso**: Marque tópicos como concluídos
- ✅ **Export/Import**: Compartilhe dados com colegas
- ✅ **Responsivo**: Funciona em desktop e mobile
- ✅ **Offline**: Dados salvos localmente no navegador

## 🚀 Como Usar

### Pré-requisitos
- Node.js 14+ instalado
- Navegador moderno (Chrome, Firefox, Safari, Edge)

### Instalação
```bash
# 1. Clone ou baixe o projeto
git clone https://github.com/seu-usuario/capivara-learn.git

# 2. Entre na pasta
cd capivara-learn

# 3. Instale dependências e configure
npm run setup

# 4. Execute o servidor de desenvolvimento
npm run dev
```

### Acesso
Abra seu navegador em: `http://localhost:3000`

## 📖 Como Organizar seus Estudos

1. **Criar Módulo**: Clique em "Novo Módulo" e defina nome e período
2. **Adicionar Tópicos**: Dentro de cada módulo, adicione os 4 tópicos
3. **Definir Datas**: Configure datas de abertura e fechamento
4. **Acompanhar**: Marque como concluído conforme avança
5. **Compartilhar**: Use Export/Import para compartilhar com colegas

## 🎨 Interface

- **🟢 Verde**: Tópico ativo (dentro do prazo)
- **🔴 Vermelho**: Tópico atrasado
- **🟡 Amarelo**: Tópico futuro
- **🔵 Azul**: Tópico concluído

## 🤝 Contribuição

Contribuições são bem-vindas! Para contribuir:

1. Fork o projeto
2. Crie sua branch: `git checkout -b feature/nova-funcionalidade`
3. Commit suas mudanças: `git commit -m 'feat: adicionar nova funcionalidade'`
4. Push para a branch: `git push origin feature/nova-funcionalidade`
5. Abra um Pull Request

## 📝 Roadmap

- [ ] Sistema de notificações
- [ ] Calendário visual
- [ ] Estatísticas de progresso
- [ ] Modo escuro
- [ ] PWA (Progressive Web App)
- [ ] Sincronização na nuvem

## 🐾 Por que CapivaraLearn?

As capivaras são conhecidas por sua tranquilidade e organização social. Assim como elas, queremos que seus estudos sejam organizados, tranquilos e eficientes!

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 👥 Equipe

Desenvolvido com ❤️ por estudantes de fisioterapia para estudantes de fisioterapia.

---

**🐾 Estude com a tranquilidade de uma capivara!**
"@

$readme | Out-File -FilePath "README.md" -Encoding UTF8
Write-Host "✅ README.md criado!" -ForegroundColor Green

# Criar configurações do VSCode
$vscodeSettings = @"
{
  "editor.formatOnSave": true,
  "editor.defaultFormatter": "esbenp.prettier-vscode",
  "editor.codeActionsOnSave": {
    "source.fixAll.eslint": true
  },
  "liveServer.settings.port": 3000,
  "liveServer.settings.root": "/public",
  "emmet.includeLanguages": {
    "javascript": "html"
  },
  "files.associations": {
    "*.js": "javascript"
  },
  "editor.suggestSelection": "first",
  "editor.tabSize": 2,
  "editor.insertSpaces": true
}
"@

$vscodeSettings | Out-File -FilePath ".vscode/settings.json" -Encoding UTF8

$vscodeExtensions = @"
{
  "recommendations": [
    "ritwickdey.liveserver",
    "esbenp.prettier-vscode",
    "dbaeumer.vscode-eslint",
    "formulahendry.auto-rename-tag",
    "zignd.html-css-class-completion",
    "ms-vscode.vscode-json",
    "streetsidesoftware.code-spell-checker"
  ]
}
"@

$vscodeExtensions | Out-File -FilePath ".vscode/extensions.json" -Encoding UTF8
Write-Host "✅ Configurações VSCode criadas!" -ForegroundColor Green

# Criar index.html
$indexHtml = @"
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🐾 CapivaraLearn - Planejador de Estudos</title>
    <meta name="description" content="Sistema de planejamento de estudos modulares para fisioterapia">
    <link rel="stylesheet" href="../src/css/style.css">
    <link rel="stylesheet" href="../src/css/components.css">
    <link rel="stylesheet" href="../src/css/responsive.css">
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E🐾%3C/text%3E%3C/svg%3E">
    
    <!-- Meta tags para compartilhamento -->
    <meta property="og:title" content="CapivaraLearn - Planejador de Estudos">
    <meta property="og:description" content="Organize seus estudos de fisioterapia de forma eficiente">
    <meta property="og:type" content="website">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🐾 CapivaraLearn</h1>
            <p>Organize seus estudos de fisioterapia com a tranquilidade de uma capivara</p>
            <div class="version">v1.0.0</div>
        </div>
        
        <div class="main-content">
            <div id="app">
                <div class="loading">
                    <h2>🐾 Carregando CapivaraLearn...</h2>
                    <p>Preparando seu ambiente de estudos!</p>
                </div>
            </div>
        </div>
        
        <footer class="footer">
            <p>Feito com ❤️ por estudantes de fisioterapia</p>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="../src/js/utils.js"></script>
    <script src="../src/js/storage.js"></script>
    <script src="../src/js/modules.js"></script>
    <script src="../src/js/app.js"></script>
</body>
</html>
"@

$indexHtml | Out-File -FilePath "public/index.html" -Encoding UTF8
Write-Host "✅ index.html criado!" -ForegroundColor Green

# Criar arquivos JavaScript básicos
$utilsJs = @"
// 🐾 CapivaraLearn - Utilities
// Funções utilitárias para o sistema

console.log('🐾 CapivaraLearn Utils carregado!');

class CapivaraUtils {
    static formatDate(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        return date.toLocaleDateString('pt-BR');
    }

    static showWelcome() {
        console.log(`
        🐾 CapivaraLearn v1.0.0
        ========================
        Bem-vindo ao seu planejador de estudos!
        Organize-se com a tranquilidade de uma capivara 🌿
        `);
    }
}

// Mostrar mensagem de boas-vindas
CapivaraUtils.showWelcome();
"@

$utilsJs | Out-File -FilePath "src/js/utils.js" -Encoding UTF8

$appJs = @"
// 🐾 CapivaraLearn - Aplicação Principal
// Sistema de planejamento de estudos modulares

console.log('🐾 CapivaraLearn inicializado!');

class CapivaraLearnApp {
    constructor() {
        this.version = '1.0.0';
        this.init();
    }

    init() {
        this.renderWelcome();
        console.log('🐾 CapivaraLearn pronto para uso!');
    }

    renderWelcome() {
        const app = document.getElementById('app');
        if (app) {
            app.innerHTML = `
                <div class="welcome-screen">
                    <h2>🎉 CapivaraLearn configurado com sucesso!</h2>
                    <p>Seu planejador de estudos está pronto para uso.</p>
                    <div class="next-steps">
                        <h3>📋 Próximos passos:</h3>
                        <ol>
                            <li>Copie o código do sistema completo</li>
                            <li>Cole nos arquivos JavaScript</li>
                            <li>Atualize o CSS com os estilos</li>
                            <li>Comece a organizar seus estudos!</li>
                        </ol>
                    </div>
                    <div class="capivara-quote">
                        <p><em>"Estude com calma, como uma capivara à beira do rio."</em> 🌊</p>
                    </div>
                </div>
            `;
        }
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    new CapivaraLearnApp();
});
"@

$appJs | Out-File -FilePath "src/js/app.js" -Encoding UTF8

# Criar arquivos CSS básicos
$stylesCss = @"
/* 🐾 CapivaraLearn - Estilos Principais */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
    line-height: 1.6;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    overflow: hidden;
    min-height: 80vh;
}

.header {
    background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    color: white;
    padding: 30px;
    text-align: center;
    position: relative;