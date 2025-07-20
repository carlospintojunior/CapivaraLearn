<?php
require_once 'Medoo.php';

// Configuração do banco
$database = new Medoo\Medoo([
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'capivaralearn',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
]);

echo "<h1>Verificação dos Dados Restaurados</h1>";

// Verificar universidades
$universidades = $database->select("universidades", "*", ["usuario_id" => 1]);
echo "<h2>Universidades (Total: " . count($universidades) . ")</h2>";
echo "<pre>" . print_r($universidades, true) . "</pre>";

// Verificar cursos
$cursos = $database->select("cursos", "*", ["usuario_id" => 1]);
echo "<h2>Cursos (Total: " . count($cursos) . ")</h2>";
echo "<pre>" . print_r($cursos, true) . "</pre>";

// Verificar disciplinas
$disciplinas = $database->select("disciplinas", "*", ["usuario_id" => 1]);
echo "<h2>Disciplinas (Total: " . count($disciplinas) . ")</h2>";
echo "<pre>" . print_r($disciplinas, true) . "</pre>";

// Verificar tópicos
$topicos = $database->select("topicos", "*", ["usuario_id" => 1]);
echo "<h2>Tópicos (Total: " . count($topicos) . ")</h2>";
echo "<pre>" . print_r($topicos, true) . "</pre>";

// Verificar unidades de aprendizagem
$unidades = $database->select("unidades_aprendizagem", "*", ["usuario_id" => 1]);
echo "<h2>Unidades de Aprendizagem (Total: " . count($unidades) . ")</h2>";
echo "<pre>" . print_r($unidades, true) . "</pre>";

// Verificar matrículas
$matriculas = $database->select("matriculas", "*", ["usuario_id" => 1]);
echo "<h2>Matrículas (Total: " . count($matriculas) . ")</h2>";
echo "<pre>" . print_r($matriculas, true) . "</pre>";

// Verificar subscription financeira
$subscription = $database->select("user_subscriptions", "*", ["user_id" => 1]);
echo "<h2>Subscription Financeira (Total: " . count($subscription) . ")</h2>";
echo "<pre>" . print_r($subscription, true) . "</pre>";

echo "<h2>Resumo</h2>";
echo "<ul>";
echo "<li>Universidades: " . count($universidades) . "</li>";
echo "<li>Cursos: " . count($cursos) . "</li>";
echo "<li>Disciplinas: " . count($disciplinas) . "</li>";
echo "<li>Tópicos: " . count($topicos) . "</li>";
echo "<li>Unidades: " . count($unidades) . "</li>";
echo "<li>Matrículas: " . count($matriculas) . "</li>";
echo "<li>Subscription: " . count($subscription) . "</li>";
echo "</ul>";
?>
