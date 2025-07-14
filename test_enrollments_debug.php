<?php
// Teste de debug para enrollments_simple.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/Medoo.php';
require_once __DIR__ . '/includes/logger_config.php';

use Medoo\Medoo;

// Iniciar sessão e validar autenticação
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simular usuário logado se não estiver
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Teste com usuário ID 1
}

// Configurar Medoo
$database = new Medoo([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'capivaralearn',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
]);

$user_id = $_SESSION['user_id'];

echo "1. Testando conexão com database...<br>";

try {
    // Teste 1: Buscar cursos simples
    echo "2. Testando busca de cursos simples...<br>";
    $cursos_simples = $database->select('cursos', ['id', 'nome'], [
        'usuario_id' => $user_id,
        'ORDER' => ['nome' => 'ASC']
    ]);
    echo "Cursos encontrados: " . count($cursos_simples) . "<br>";
    
    // Teste 2: Buscar cursos com JOIN
    echo "3. Testando busca de cursos com JOIN...<br>";
    $cursos_join = $database->select('cursos', [
        '[>]universidades' => ['universidade_id' => 'id']
    ], [
        'cursos.id',
        'cursos.nome',
        'universidades.nome(universidade_nome)'
    ], [
        'cursos.usuario_id' => $user_id,
        'ORDER' => ['universidades.nome' => 'ASC', 'cursos.nome' => 'ASC']
    ]);
    echo "Cursos com JOIN encontrados: " . count($cursos_join) . "<br>";
    
    // Teste 3: Buscar matrículas simples
    echo "4. Testando busca de matrículas simples...<br>";
    $matriculas_simples = $database->select('matriculas', [
        '[>]cursos' => ['curso_id' => 'id']
    ], [
        'matriculas.id',
        'cursos.nome(curso_nome)',
        'matriculas.status',
        'matriculas.data_matricula',
        'matriculas.data_conclusao'
    ], [
        'matriculas.usuario_id' => $user_id,
        'ORDER' => ['cursos.nome' => 'ASC']
    ]);
    echo "Matrículas simples encontradas: " . count($matriculas_simples) . "<br>";
    
    // Teste 4: Buscar matrículas com JOIN duplo
    echo "5. Testando busca de matrículas com JOIN duplo...<br>";
    $matriculas_join = $database->select('matriculas', [
        '[>]cursos' => ['curso_id' => 'id'],
        '[>]universidades' => ['cursos.universidade_id' => 'universidades.id']
    ], [
        'matriculas.id',
        'cursos.nome(curso_nome)',
        'universidades.nome(universidade_nome)',
        'matriculas.status',
        'matriculas.data_matricula',
        'matriculas.data_conclusao'
    ], [
        'matriculas.usuario_id' => $user_id,
        'ORDER' => ['universidades.nome' => 'ASC', 'cursos.nome' => 'ASC']
    ]);
    echo "Matrículas com JOIN duplo encontradas: " . count($matriculas_join) . "<br>";
    
    echo "6. Teste concluído com sucesso!<br>";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "<br>";
    echo "Arquivo: " . $e->getFile() . "<br>";
    echo "Linha: " . $e->getLine() . "<br>";
}
?>
