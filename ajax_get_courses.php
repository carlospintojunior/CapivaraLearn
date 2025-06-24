<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/services/UniversityService.php';

header('Content-Type: application/json');

// Requer login para acesso
try {
    requireLogin();
} catch (Exception $e) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Acesso negado. Por favor, faça o login.']);
    exit;
}

$universityId = $_GET['university_id'] ?? null;

if (!$universityId) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID da universidade não fornecido.']);
    exit;
}

try {
    $universityService = UniversityService::getInstance();
    $courses = $universityService->listCourses((int)$universityId);
    echo json_encode($courses);
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    // Em um ambiente de produção, logue o erro em vez de exibi-lo
    echo json_encode(['error' => 'Erro ao buscar cursos: ' . $e->getMessage()]);
}
