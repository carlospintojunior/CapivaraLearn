<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/services/CourseService.php';

// Verificar se usuário está logado
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['enrollment_id']) || !is_numeric($_GET['enrollment_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID da matrícula inválido']);
    exit;
}

try {
    $courseService = CourseService::getInstance();
    $history = $courseService->getEnrollmentHistory($_GET['enrollment_id']);
    echo json_encode($history);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
