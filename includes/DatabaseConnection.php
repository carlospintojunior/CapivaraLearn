<?php
require_once __DIR__ . '/DatabaseConnection.php';

use CapivaraLearn\DatabaseConnection;

/**
 * Serviço para gerenciamento de universidades
 */
class UniversityService {
    private $db;
    private static $instance = null;

    private function __construct() {
        $this->db = DatabaseConnection::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Lista todas as universidades ativas
     */
    public function listAll() {
        return $this->db->select(
            "SELECT * FROM universidades WHERE ativo = 1 ORDER BY nome"
        );
    }

    /**
     * Busca uma universidade por ID
     */
    public function getById($id) {
        $result = $this->db->select(
            "SELECT * FROM universidades WHERE id = ? AND ativo = 1",
            [$id]
        );
        return $result[0] ?? null;
    }

    /**
     * Cria uma nova universidade
     */
    public function create($data) {
        return $this->db->insert(
            "universidades",
            [
                'nome' => $data['nome'],
                'sigla' => $data['sigla'],
                'cidade' => $data['cidade'],
                'estado' => $data['estado']
            ]
        );
    }

    /**
     * Atualiza uma universidade existente
     */
    public function update($id, $data) {
        return $this->db->update(
            "universidades",
            [
                'nome' => $data['nome'],
                'sigla' => $data['sigla'],
                'cidade' => $data['cidade'],
                'estado' => $data['estado']
            ],
            "id = ?",
            [$id]
        );
    }

    /**
     * Remove (soft delete) uma universidade
     */
    public function delete($id) {
        return $this->db->update(
            "universidades",
            ['ativo' => 0],
            "id = ?",
            [$id]
        );
    }

    /**
     * Lista todos os cursos de uma universidade
     */
    public function listCourses($universityId) {
        return $this->db->select(
            "SELECT c.* FROM cursos c 
            INNER JOIN universidade_curso uc ON c.id = uc.curso_id 
            WHERE uc.universidade_id = ? AND uc.ativo = 1 AND c.ativo = 1
            ORDER BY c.nome",
            [$universityId]
        );
    }

    /**
     * Adiciona um curso à universidade
     */
    public function addCourse($universityId, $courseId) {
        return $this->db->execute(
            "INSERT IGNORE INTO universidade_curso (universidade_id, curso_id) VALUES (?, ?)",
            [$universityId, $courseId]
        );
    }

    /**
     * Remove um curso da universidade
     */
    public function removeCourse($universityId, $courseId) {
        return $this->db->execute(
            "UPDATE universidade_curso SET ativo = 0 WHERE universidade_id = ? AND curso_id = ?",
            [$universityId, $courseId]
        );
    }

    /**
     * Verifica se um curso está associado a uma universidade
     */
    public function hasCourse($universityId, $courseId) {
        $result = $this->db->select(
            "SELECT 1 FROM universidade_curso 
            WHERE universidade_id = ? AND curso_id = ? AND ativo = 1",
            [$universityId, $courseId]
        );
        return !empty($result);
    }

    /**
     * Lista universidades que oferecem um determinado curso
     */
    public function listUniversitiesByCourse($courseId) {
        return $this->db->select(
            "SELECT u.* FROM universidades u 
            INNER JOIN universidade_curso uc ON u.id = uc.universidade_id 
            WHERE uc.curso_id = ? AND uc.ativo = 1 AND u.ativo = 1
            ORDER BY u.nome",
            [$courseId]
        );
    }
}