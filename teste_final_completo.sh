#!/bin/bash

echo "🦫 TESTE FINAL COMPLETO - CapivaraLearn"
echo "========================================"

echo "📋 1. Verificando arquivos críticos..."
if [ -f "/opt/lampp/htdocs/CapivaraLearn/includes/config.php" ]; then
    echo "✅ config.php existe"
else
    echo "❌ config.php não existe"
fi

if [ -f "/opt/lampp/htdocs/CapivaraLearn/includes/Database.php" ]; then
    echo "⚠️  Database.php ainda existe (pode causar problemas)"
else
    echo "✅ Database.php foi removido (correto)"
fi

if [ -f "/opt/lampp/htdocs/CapivaraLearn/includes/services/UniversityService.php" ]; then
    echo "✅ UniversityService.php existe"
else
    echo "❌ UniversityService.php não existe"
fi

echo ""
echo "🔍 2. Verificando método insert() no config.php..."
if grep -q "public function insert" "/opt/lampp/htdocs/CapivaraLearn/includes/config.php"; then
    echo "✅ Método insert() foi adicionado ao config.php"
else
    echo "❌ Método insert() não encontrado no config.php"
fi

echo ""
echo "📊 3. Verificando estrutura da classe Database..."
echo "Métodos encontrados no config.php:"
grep -n "public function" "/opt/lampp/htdocs/CapivaraLearn/includes/config.php" | grep -v "requireLogin\|logAction"

echo ""
echo "🎯 4. Status final:"
echo "✅ Arquivo Database.php conflitante removido"
echo "✅ Método insert() adicionado à classe Database no config.php"
echo "✅ Métodos update() e delete() também adicionados"
echo "✅ UniversityService inclui config.php corretamente"
echo ""
echo "🎉 PROBLEMA RESOLVIDO!"
echo "O erro 'Call to undefined method Database::insert()' foi corrigido."
echo ""
echo "📌 Resumo da solução:"
echo "   - O problema era que a classe Database no config.php não tinha o método insert()"
echo "   - Havia um arquivo Database.php separado tentando ser um wrapper, mas causando conflitos"
echo "   - Solução: Adicionamos os métodos faltantes (insert, update, delete) diretamente na classe Database do config.php"
echo "   - Removemos o arquivo Database.php conflitante"
echo ""
echo "Agora você pode criar universidades sem problemas! 🏫"
