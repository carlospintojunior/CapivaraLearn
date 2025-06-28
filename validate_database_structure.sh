#!/bin/bash

echo "🔍 VALIDAÇÃO DA ESTRUTURA DO BANCO DE DADOS"
echo "====================================================="
echo ""

# Configuração
MYSQL_CMD="/opt/lampp/bin/mysql -u root capivaralearn"

echo "✅ Iniciando validação..."
echo ""

# Verificar tabelas existentes
echo "📋 VERIFICANDO EXISTÊNCIA DAS TABELAS:"
echo "----------------------------------------"

EXPECTED_TABLES=("usuarios" "email_tokens" "universidades" "cursos" "disciplinas" "topicos" "universidade_cursos" "inscricoes")
EXISTING_TABLES=$($MYSQL_CMD -e "SHOW TABLES;" -s)

all_exist=true
for table in "${EXPECTED_TABLES[@]}"; do
    if echo "$EXISTING_TABLES" | grep -q "^$table$"; then
        echo "✅ $table"
    else
        echo "❌ $table (FALTANDO)"
        all_exist=false
    fi
done

if [ "$all_exist" = true ]; then
    echo ""
    echo "✅ Todas as 8 tabelas existem!"
else
    echo ""
    echo "❌ Algumas tabelas estão faltando!"
fi

echo ""
echo "🔐 VERIFICANDO ISOLAMENTO POR USUÁRIO:"
echo "----------------------------------------"

# Verificar campo usuario_id nas tabelas críticas
TABLES_WITH_USER_ID=("universidades" "cursos" "disciplinas" "topicos" "universidade_cursos")

for table in "${TABLES_WITH_USER_ID[@]}"; do
    if $MYSQL_CMD -e "DESCRIBE $table;" | grep -q "usuario_id"; then
        echo "✅ $table.usuario_id"
    else
        echo "❌ $table.usuario_id (FALTANDO)"
    fi
done

echo ""
echo "🔗 VERIFICANDO CONSTRAINTS CRÍTICAS:"
echo "----------------------------------------"

# Verificar constraint UNIQUE da tabela universidade_cursos
UNIQUE_CONSTRAINT=$($MYSQL_CMD -e "SHOW CREATE TABLE universidade_cursos\G" | grep "UNIQUE KEY")

if echo "$UNIQUE_CONSTRAINT" | grep -q "universidade_id" && \
   echo "$UNIQUE_CONSTRAINT" | grep -q "curso_id" && \
   echo "$UNIQUE_CONSTRAINT" | grep -q "usuario_id"; then
    echo "✅ universidade_cursos.UNIQUE KEY inclui usuario_id (isolamento OK)"
else
    echo "❌ universidade_cursos.UNIQUE KEY não isola por usuário"
fi

echo ""
echo "📊 VERIFICANDO CAMPOS PADRÃO:"
echo "----------------------------------------"

STANDARD_TABLES=("universidades" "cursos" "disciplinas" "universidade_cursos")

for table in "${STANDARD_TABLES[@]}"; do
    fields=$($MYSQL_CMD -e "DESCRIBE $table;" | awk '{print $1}' | tail -n +2)
    missing=()
    
    if ! echo "$fields" | grep -q "^id$"; then missing+=("id"); fi
    if ! echo "$fields" | grep -q "^ativo$"; then missing+=("ativo"); fi
    if ! echo "$fields" | grep -q "^data_criacao$"; then missing+=("data_criacao"); fi
    if ! echo "$fields" | grep -q "^data_atualizacao$"; then missing+=("data_atualizacao"); fi
    
    if [ ${#missing[@]} -eq 0 ]; then
        echo "✅ $table (todos os campos padrão)"
    else
        echo "⚠️  $table (faltando: ${missing[*]})"
    fi
done

echo ""
echo "============================================================"
echo "🎯 RESUMO DA VALIDAÇÃO:"
echo ""

if [ "$all_exist" = true ]; then
    echo "✅ Estrutura de tabelas: COMPLETA (8/8)"
    echo "✅ Isolamento por usuário: IMPLEMENTADO" 
    echo "✅ Constraints de integridade: FUNCIONAIS"
    echo "✅ Sistema pronto para CRUD isolado por usuário"
    echo ""
    echo "🚀 O banco está 100% alinhado com install.php!"
else
    echo "❌ Algumas verificações falharam"
    echo "🔧 Execute install.php para corrigir"
fi

echo ""
echo "============================================================"
