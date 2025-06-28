#!/bin/bash

# Script para testar a instalação do CapivaraLearn
# Cria um banco de testes e valida a estrutura

echo "🧪 Testando instalação do CapivaraLearn..."
echo "========================================"

# Verificar se o MySQL está rodando
if ! systemctl is-active --quiet mysql; then
    echo "❌ MySQL não está rodando. Iniciando..."
    sudo systemctl start mysql
    sleep 2
fi

# Remover banco de teste se existir
echo "🗑️  Removendo banco de teste anterior (se existir)..."
mysql -u root -e "DROP DATABASE IF EXISTS capivaralearn_test" 2>/dev/null

# Criar banco de teste
echo "🏗️  Criando banco de teste..."
mysql -u root -e "CREATE DATABASE capivaralearn_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# Simular instalação via POST (usando curl se disponível)
if command -v curl &> /dev/null; then
    echo "📡 Testando instalação via web..."
    
    # Iniciar servidor PHP temporário
    echo "🚀 Iniciando servidor PHP temporário na porta 8080..."
    php -S localhost:8080 -t . > /dev/null 2>&1 &
    PHP_PID=$!
    sleep 2
    
    # Fazer POST para o install.php
    curl -s -X POST \
         -d "host=localhost" \
         -d "user=root" \
         -d "pass=" \
         -d "dbname=capivaralearn_test" \
         http://localhost:8080/install.php > install_test_output.html
    
    # Parar servidor PHP
    kill $PHP_PID 2>/dev/null
    
    echo "📄 Resultado da instalação salvo em: install_test_output.html"
    
    # Verificar se foi criado arquivo de configuração de teste
    if [ -f "includes/config.php" ]; then
        echo "✅ Arquivo de configuração criado"
        
        # Fazer backup do config original se existir
        if [ -f "includes/config.php.backup" ]; then
            cp includes/config.php.backup includes/config.php
        else
            mv includes/config.php includes/config.php.test
        fi
    fi
else
    echo "⚠️  curl não disponível, testando instalação diretamente no banco..."
    
    # Executar SQLs de criação manualmente
    mysql -u root capivaralearn_test << 'EOF'
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    email_verificado BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS universidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    sigla VARCHAR(20),
    cidade VARCHAR(100),
    estado VARCHAR(50),
    usuario_id INT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_nome (usuario_id, nome),
    INDEX idx_usuario_sigla (usuario_id, sigla)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS universidade_cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    universidade_id INT NOT NULL,
    curso_id INT NOT NULL,
    usuario_id INT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (universidade_id) REFERENCES universidades(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_univ_curso (universidade_id, curso_id, usuario_id)
) ENGINE=InnoDB;
EOF
fi

# Verificar estrutura criada
echo "🔍 Verificando estrutura criada..."
mysql -u root -e "USE capivaralearn_test; SHOW TABLES;" 2>/dev/null

echo ""
echo "📊 Tabelas criadas no banco de teste:"
mysql -u root -e "USE capivaralearn_test; SHOW TABLES;" 2>/dev/null | tail -n +2 | while read table; do
    echo "  ✅ $table"
done

# Verificar constraint específica
echo ""
echo "🔒 Verificando constraint da tabela universidade_cursos..."
if mysql -u root -e "USE capivaralearn_test; SHOW CREATE TABLE universidade_cursos\G" 2>/dev/null | grep -q "unique_user_univ_curso"; then
    echo "  ✅ Constraint com usuario_id presente"
else
    echo "  ❌ Constraint com usuario_id ausente"
fi

echo ""
echo "🧹 Limpando..."
mysql -u root -e "DROP DATABASE IF EXISTS capivaralearn_test" 2>/dev/null

echo "✅ Teste concluído!"
echo ""
echo "📝 Próximos passos:"
echo "  1. Execute o install.php no navegador para instalar o sistema"
echo "  2. Use php test_install_structure.php para validar a estrutura"
echo "  3. Teste o CRUD com usuários diferentes para verificar isolamento"
