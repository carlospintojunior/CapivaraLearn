@echo off
echo.
echo ================================
echo 🐾 CAPIVARA LEARN - SETUP
echo ================================
echo.
echo Criando estrutura do projeto...
echo.

REM Criar diretório principal
mkdir CapivaraLearn
cd CapivaraLearn

REM Criar estrutura de pastas
mkdir src
mkdir src\css
mkdir src\js
mkdir src\components
mkdir src\assets
mkdir src\assets\icons
mkdir src\assets\images
mkdir public
mkdir docs
mkdir tests
mkdir .vscode

echo ✅ Estrutura de pastas criada!

REM Criar package.json
echo {> package.json
echo   "name": "capivara-learn",>> package.json
echo   "version": "1.0.0",>> package.json
echo   "description": "Sistema de planejamento de estudos modulares - CapivaraLearn",>> package.json
echo   "main": "src/js/app.js",>> package.json
echo   "scripts": {>> package.json
echo     "dev": "live-server public --port=3000",>> package.json
echo     "start": "live-server public --port=3000",>> package.json
echo     "build": "npm run minify-css && npm run minify-js",>> package.json
echo     "test": "jest",>> package.json
echo     "lint": "eslint src/js/**/*.js",>> package.json
echo     "format": "prettier --write src/**/*">> package.json
echo   },>> package.json
echo   "keywords": ["estudos", "planejamento", "fisioterapia", "educação", "capivara"],>> package.json
echo   "author": "Equipe CapivaraLearn",>> package.json
echo   "license": "MIT",>> package.json
echo   "devDependencies": {>> package.json
echo     "live-server": "^1.2.2",>> package.json
echo     "eslint": "^8.0.0",>> package.json
echo     "prettier": "^2.8.0",>> package.json
echo     "jest": "^29.0.0">> package.json
echo   }>> package.json
echo }>> package.json

echo ✅ package.json criado!

REM Criar .gitignore
echo node_modules/> .gitignore
echo .env>> .gitignore
echo dist/>> .gitignore
echo build/>> .gitignore
echo .DS_Store>> .gitignore
echo Thumbs.db>> .gitignore
echo .vscode/settings.json>> .gitignore

echo ✅ .gitignore criado!

REM Criar README.md
echo # 🐾 CapivaraLearn> README.md
echo.>> README.md
echo Sistema de planejamento de estudos modulares para fisioterapia>> README.md
echo.>> README.md
echo ## 🚀 Como usar>> README.md
echo.>> README.md
echo ```bash>> README.md
echo # Instalar dependências>> README.md
echo npm install>> README.md
echo.>> README.md
echo # Executar em modo desenvolvimento>> README.md
echo npm run dev>> README.md
echo ```>> README.md
echo.>> README.md
echo ## 📱 Funcionalidades>> README.md
echo.>> README.md
echo - ✅ Gestão de módulos de estudo>> README.md
echo - ✅ Controle de tópicos com datas>> README.md
echo - ✅ Acompanhamento de progresso>> README.md
echo - ✅ Export/Import de dados>> README.md
echo - ✅ Interface responsiva>> README.md
echo.>> README.md
echo ## 🤝 Contribuindo>> README.md
echo.>> README.md
echo 1. Fork o projeto>> README.md
echo 2. Crie sua branch: `git checkout -b feature/nova-funcionalidade`>> README.md
echo 3. Commit suas mudanças: `git commit -m 'Adicionar nova funcionalidade'`>> README.md
echo 4. Push para a branch: `git push origin feature/nova-funcionalidade`>> README.md
echo 5. Abra um Pull Request>> README.md

echo ✅ README.md criado!

REM Criar configuração do VSCode
echo {> .vscode\settings.json
echo   "editor.formatOnSave": true,>> .vscode\settings.json
echo   "editor.defaultFormatter": "esbenp.prettier-vscode",>> .vscode\settings.json
echo   "editor.codeActionsOnSave": {>> .vscode\settings.json
echo     "source.fixAll.eslint": true>> .vscode\settings.json
echo   },>> .vscode\settings.json
echo   "liveServer.settings.port": 3000,>> .vscode\settings.json
echo   "emmet.includeLanguages": {>> .vscode\settings.json
echo     "javascript": "html">> .vscode\settings.json
echo   }>> .vscode\settings.json
echo }>> .vscode\settings.json

echo ✅ Configuração VSCode criada!

REM Criar extensões recomendadas
echo {> .vscode\extensions.json
echo   "recommendations": [>> .vscode\extensions.json
echo     "ritwickdey.liveserver",>> .vscode\extensions.json
echo     "esbenp.prettier-vscode",>> .vscode\extensions.json
echo     "dbaeumer.vscode-eslint",>> .vscode\extensions.json
echo     "formulahendry.auto-rename-tag",>> .vscode\extensions.json
echo     "zignd.html-css-class-completion",>> .vscode\extensions.json
echo     "bradlc.vscode-tailwindcss">> .vscode\extensions.json
echo   ]>> .vscode\extensions.json
echo }>> .vscode\extensions.json

echo ✅ Extensões recomendadas configuradas!

REM Criar index.html básico
echo ^<!DOCTYPE html^>> public\index.html
echo ^<html lang="pt-BR"^>>> public\index.html
echo ^<head^>>> public\index.html
echo     ^<meta charset="UTF-8"^>>> public\index.html
echo     ^<meta name="viewport" content="width=device-width, initial-scale=1.0"^>>> public\index.html
echo     ^<title^>🐾 CapivaraLearn - Planejador de Estudos^</title^>>> public\index.html
echo     ^<link rel="stylesheet" href="../src/css/style.css"^>>> public\index.html
echo     ^<link rel="stylesheet" href="../src/css/components.css"^>>> public\index.html
echo     ^<link rel="stylesheet" href="../src/css/responsive.css"^>>> public\index.html
echo     ^<link rel="icon" href="data:image/svg+xml,%%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%%3E%%3Ctext y='.9em' font-size='90'%%3E🐾%%3C/text%%3E%%3C/svg%%3E"^>>> public\index.html
echo ^</head^>>> public\index.html
echo ^<body^>>> public\index.html
echo     ^<div class="container"^>>> public\index.html
echo         ^<div class="header"^>>> public\index.html
echo             ^<h1^>🐾 CapivaraLearn^</h1^>>> public\index.html
echo             ^<p^>Organize seus estudos de fisioterapia de forma eficiente^</p^>>> public\index.html
echo         ^</div^>>> public\index.html
echo         ^<div class="main-content"^>>> public\index.html
echo             ^<div id="app"^>Carregando...^</div^>>> public\index.html
echo         ^</div^>>> public\index.html
echo     ^</div^>>> public\index.html
echo.>> public\index.html
echo     ^<script src="../src/js/utils.js"^>^</script^>>> public\index.html
echo     ^<script src="../src/js/storage.js"^>^</script^>>> public\index.html
echo     ^<script src="../src/js/modules.js"^>^</script^>>> public\index.html
echo     ^<script src="../src/js/app.js"^>^</script^>>> public\index.html
echo ^</body^>>> public\index.html
echo ^</html^>>> public\index.html

echo ✅ index.html criado!

REM Criar arquivos JS básicos
echo // CapivaraLearn - Utils> src\js\utils.js
echo console.log('🐾 CapivaraLearn Utils carregado!');>> src\js\utils.js

echo // CapivaraLearn - Storage> src\js\storage.js
echo console.log('🐾 CapivaraLearn Storage carregado!');>> src\js\storage.js

echo // CapivaraLearn - Modules> src\js\modules.js
echo console.log('🐾 CapivaraLearn Modules carregado!');>> src\js\modules.js

echo // CapivaraLearn - App Principal> src\js\app.js
echo console.log('🐾 CapivaraLearn inicializado!');>> src\js\app.js
echo document.getElementById('app').innerHTML = '^<h2^>CapivaraLearn funcionando!^</h2^>^<p^>Projeto criado com sucesso! 🎉^</p^>';>> src\js\app.js

echo ✅ Arquivos JavaScript criados!

REM Criar CSS básico
echo /* CapivaraLearn - Estilos Principais */> src\css\style.css
echo * {>> src\css\style.css
echo   margin: 0;>> src\css\style.css
echo   padding: 0;>> src\css\style.css
echo   box-sizing: border-box;>> src\css\style.css
echo }>> src\css\style.css
echo.>> src\css\style.css
echo body {>> src\css\style.css
echo   font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;>> src\css\style.css
echo   background: linear-gradient(135deg, #667eea 0%%, #764ba2 100%%);>> src\css\style.css
echo   min-height: 100vh;>> src\css\style.css
echo   padding: 20px;>> src\css\style.css
echo }>> src\css\style.css

echo /* CapivaraLearn - Componentes */> src\css\components.css
echo .container {>> src\css\components.css
echo   max-width: 1200px;>> src\css\components.css
echo   margin: 0 auto;>> src\css\components.css
echo   background: white;>> src\css\components.css
echo   border-radius: 20px;>> src\css\components.css
echo   box-shadow: 0 20px 40px rgba(0,0,0,0.1);>> src\css\components.css
echo   overflow: hidden;>> src\css\components.css
echo }>> src\css\components.css

echo /* CapivaraLearn - Responsivo */> src\css\responsive.css
echo @media (max-width: 768px) {>> src\css\responsive.css
echo   .container {>> src\css\responsive.css
echo     margin: 10px;>> src\css\responsive.css
echo     border-radius: 15px;>> src\css\responsive.css
echo   }>> src\css\responsive.css
echo }>> src\css\responsive.css

echo ✅ Arquivos CSS criados!

REM Criar LICENSE
echo MIT License> LICENSE
echo.>> LICENSE
echo Copyright (c) 2025 CapivaraLearn>> LICENSE
echo.>> LICENSE
echo Permission is hereby granted, free of charge, to any person obtaining a copy>> LICENSE
echo of this software and associated documentation files (the "Software"), to deal>> LICENSE
echo in the Software without restriction, including without limitation the rights>> LICENSE
echo to use, copy, modify, merge, publish, distribute, sublicense, and/or sell>> LICENSE
echo copies of the Software, and to permit persons to whom the Software is>> LICENSE
echo furnished to do so, subject to the following conditions:>> LICENSE
echo.>> LICENSE
echo The above copyright notice and this permission notice shall be included in all>> LICENSE
echo copies or substantial portions of the Software.>> LICENSE

echo ✅ Licença MIT criada!

echo.
echo ================================
echo 🎉 PROJETO CAPIVARA LEARN CRIADO!
echo ================================
echo.
echo 📁 Estrutura completa criada em: CapivaraLearn\
echo.
echo 🚀 Próximos passos:
echo    1. cd CapivaraLearn
echo    2. npm install
echo    3. code . (abrir no VSCode)
echo    4. npm run dev (executar servidor)
echo.
echo 🌐 Acesse: http://localhost:3000
echo.
echo 🐾 Boa sorte com o CapivaraLearn!
echo.
pause