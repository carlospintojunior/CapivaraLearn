<?php
/**
 * Script para criar a tabela de unidades de aprendizagem
 */

require_once __DIR__ . '/Medoo.php';

use Medoo\Medoo;

try {
    $database = new Medoo([
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => 'capivaralearn',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ]);

    $sql = "CREATE TABLE IF NOT EXISTS unidades_aprendizagem (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        descricao TEXT,
        topico_id INT NOT NULL,
        usuario_id INT NOT NULL,
        ordem INT DEFAULT 0,
        nota DECIMAL(3,1) DEFAULT 0.0 CHECK (nota >= 0.0 AND nota <= 10.0),
        ativo BOOLEAN DEFAULT TRUE,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (topico_id) REFERENCES topicos(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        INDEX idx_topico_ordem (topico_id, ordem),
        INDEX idx_usuario_nome (usuario_id, nome),
        INDEX idx_nota (nota)
    ) ENGINE=InnoDB";

    $database->query($sql);
    
    echo "✅ Tabela 'unidades_aprendizagem' criada com sucesso!<br>";
    
    // Verificar se a tabela foi criada
    $count = $database->count("unidades_aprendizagem");
    echo "📊 Registros na tabela: $count<br>";
    
    echo "<br><a href='dashboard.php'>🏠 Voltar ao Dashboard</a><br>";
    echo "<a href='crud/learning_units_simple.php'>🧩 Acessar CRUD Unidades de Aprendizagem</a>";
    
} catch (Exception $e) {
    echo "❌ Erro ao criar tabela: " . $e->getMessage();
}
?>
