<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/services/CourseService.php';

// Verificar se usuário está logado
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['university_id']) || !is_numeric($_GET['university_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID da universidade inválido']);
    exit;
}

try {
    $courseService = CourseService::getInstance();
    $courses = $courseService->listByUniversity($_GET['university_id']);
    echo json_encode($courses);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
