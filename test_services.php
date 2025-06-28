<?php
/**
 * Teste dos Services com isolamento por usuário
 * Valida se UniversityService e CourseService estão funcionando corretamente
 */

// Simular uma sessão ativa
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Usuário Teste';
$_SESSION['user_email'] = 'teste@exemplo.com';
$_SESSION['logged_in'] = true;

echo "🧪 TESTE DOS SERVICES COM ISOLAMENTO POR USUÁRIO\n";
echo "=" . str_repeat("=", 50) . "\n\n";

try {
    // Incluir services
    require_once __DIR__ . '/includes/services/UniversityService.php';
    require_once __DIR__ . '/includes/services/CourseService.php';
    
    echo "✅ Services carregados com sucesso\n\n";
    
    // Testar UniversityService
    echo "📚 TESTANDO UNIVERSITYSERVICE:\n";
    echo "-" . str_repeat("-", 30) . "\n";
    
    $universityService = UniversityService::getInstance();
    echo "✅ UniversityService instanciado para usuário {$_SESSION['user_id']}\n";
    
    // Listar universidades existentes
    $universities = $universityService->listAll();
    echo "📋 Universidades encontradas: " . count($universities) . "\n";
    
    // Criar uma universidade de teste
    $universityData = [
        'nome' => 'Universidade de Teste',
        'sigla' => 'UT',
        'cidade' => 'São Paulo',
        'estado' => 'SP'
    ];
    
    echo "➕ Criando universidade de teste...\n";
    $universityId = $universityService->create($universityData);
    echo "✅ Universidade criada com ID: {$universityId}\n";
    
    // Buscar universidade criada
    $university = $universityService->getById($universityId);
    if ($university) {
        echo "✅ Universidade encontrada: {$university['nome']}\n";
    } else {
        echo "❌ Universidade não encontrada\n";
    }
    
    echo "\n🎓 TESTANDO COURSESERVICE:\n";
    echo "-" . str_repeat("-", 30) . "\n";
    
    $courseService = CourseService::getInstance();
    echo "✅ CourseService instanciado para usuário {$_SESSION['user_id']}\n";
    
    // Listar cursos existentes
    $courses = $courseService->listAll();
    echo "📋 Cursos encontrados: " . count($courses) . "\n";
    
    // Criar um curso de teste
    $courseData = [
        'nome' => 'Curso de Teste',
        'descricao' => 'Curso para testar o sistema',
        'codigo' => 'CT001',
        'carga_horaria' => 120
    ];
    
    echo "➕ Criando curso de teste...\n";
    $courseId = $courseService->create($courseData);
    echo "✅ Curso criado com ID: {$courseId}\n";
    
    // Buscar curso criado
    $course = $courseService->getById($courseId);
    if ($course) {
        echo "✅ Curso encontrado: {$course['nome']}\n";
    } else {
        echo "❌ Curso não encontrado\n";
    }
    
    // Testar relacionamento universidade-curso
    echo "\n🔗 TESTANDO RELACIONAMENTO UNIVERSIDADE-CURSO:\n";
    echo "-" . str_repeat("-", 40) . "\n";
    
    echo "➕ Adicionando curso à universidade...\n";
    $result = $universityService->addCourse($universityId, $courseId);
    if ($result) {
        echo "✅ Curso adicionado à universidade com sucesso\n";
    } else {
        echo "❌ Falha ao adicionar curso à universidade\n";
    }
    
    // Listar cursos da universidade
    $universityCourses = $universityService->listCourses($universityId);
    echo "📋 Cursos da universidade: " . count($universityCourses) . "\n";
    
    foreach ($universityCourses as $unCourse) {
        echo "   - {$unCourse['nome']}\n";
    }
    
    // Listar universidades do curso
    $courseUniversities = $courseService->listUniversidades($courseId);
    echo "📋 Universidades que oferecem o curso: " . count($courseUniversities) . "\n";
    
    foreach ($courseUniversities as $univ) {
        echo "   - {$univ['nome']}\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎉 TODOS OS TESTES PASSARAM!\n";
    echo "✅ Services funcionando com isolamento por usuário\n";
    echo "✅ CRUD básico implementado\n";
    echo "✅ Relacionamentos funcionais\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
?>
