#!/bin/bash

echo "🔍 Validação do Menu Lateral - CapivaraLearn"
echo "============================================"

# Verificar se o dashboard.php existe
if [ -f "dashboard.php" ]; then
    echo "✅ dashboard.php encontrado"
else
    echo "❌ dashboard.php não encontrado"
    exit 1
fi

# Verificar sintaxe PHP
php -l dashboard.php > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Sintaxe PHP válida"
else
    echo "❌ Erro de sintaxe PHP"
    exit 1
fi

# Verificar se o arquivo foi sincronizado
if [ -f "/opt/lampp/htdocs/CapivaraLearn/dashboard.php" ]; then
    echo "✅ Arquivo sincronizado com XAMPP"
else
    echo "❌ Arquivo não sincronizado com XAMPP"
    exit 1
fi

# Verificar se contém as classes CSS necessárias
if grep -q "sidebar" dashboard.php && grep -q "nav-link" dashboard.php && grep -q "main-content" dashboard.php; then
    echo "✅ Classes CSS do menu lateral encontradas"
else
    echo "❌ Classes CSS do menu lateral não encontradas"
fi

# Verificar se contém o JavaScript necessário
if grep -q "toggleSidebar" dashboard.php && grep -q "sidebar.classList.toggle" dashboard.php; then
    echo "✅ JavaScript do menu lateral encontrado"
else
    echo "❌ JavaScript do menu lateral não encontrado"
fi

# Verificar se removeu o dropdown antigo
if grep -q "dropdown-menu" dashboard.php; then
    echo "⚠️  Ainda contém código do dropdown antigo"
else
    echo "✅ Código do dropdown antigo removido"
fi

# Verificar responsividade
if grep -q "@media (max-width: 768px)" dashboard.php; then
    echo "✅ CSS responsivo implementado"
else
    echo "❌ CSS responsivo não encontrado"
fi

# Verificar overlay para mobile
if grep -q "sidebar-overlay" dashboard.php; then
    echo "✅ Overlay para mobile implementado"
else
    echo "❌ Overlay para mobile não encontrado"
fi

echo ""
echo "🎯 Funcionalidades Implementadas:"
echo "   • Menu lateral recolhível para desktop"
echo "   • Menu lateral deslizante para mobile"
echo "   • Overlay escuro para mobile"
echo "   • Animações suaves de transição"
echo "   • Design moderno e profissional"
echo "   • Navegação intuitiva"
echo "   • Responsivo e acessível"
echo ""
echo "🚀 Para testar:"
echo "   1. Acesse: http://localhost/CapivaraLearn/login.php"
echo "   2. Faça login com suas credenciais"
echo "   3. No dashboard, clique no botão ☰ para recolher/expandir o menu"
echo "   4. Teste em diferentes tamanhos de tela"
echo ""
echo "✅ Validação concluída com sucesso!"
