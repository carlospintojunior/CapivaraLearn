<?php
/**
 * Serviço para gerenciamento de cursos
 */
class CourseService {
    private $db;
    private static $instance = null;

    private function __construct() {
        $this->db = Database::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Lista todos os cursos ativos
     */
    public function listAll() {
        return $this->db->select(
            "SELECT * FROM cursos WHERE ativo = 1 ORDER BY nome"
        );
    }

    /**
     * Busca um curso por ID
     */
    public function getById($id) {
        $result = $this->db->select(
            "SELECT * FROM cursos WHERE id = ? AND ativo = 1",
            [$id]
        );
        return $result[0] ?? null;
    }

    /**
     * Lista todos os cursos de uma universidade que um usuário está matriculado
     */
    public function listByUserAndUniversity($userId, $universityId) {
        return $this->db->select(
            "SELECT c.* FROM cursos c
             INNER JOIN usuario_curso_universidade ucu ON c.id = ucu.curso_id
             WHERE ucu.usuario_id = ? AND ucu.universidade_id = ? AND c.ativo = 1
             ORDER BY c.nome",
            [$userId, $universityId]
        );
    }

    /**
     * Cria um novo curso
     */
    public function create($data) {
        return $this->db->insert(
            "cursos",
            [
                'nome' => $data['nome'],
                'area' => $data['area'],
                'nivel' => $data['nivel']
            ]
        );
    }

    /**
     * Atualiza um curso existente
     */
    public function update($id, $data) {
        return $this->db->update(
            "cursos",
            [
                'nome' => $data['nome'],
                'area' => $data['area'],
                'nivel' => $data['nivel']
            ],
            "id = ?",
            [$id]
        );
    }

    /**
     * Remove (soft delete) um curso
     */
    public function delete($id) {
        return $this->db->update(
            "cursos",
            ['ativo' => 0],
            "id = ?",
            [$id]
        );
    }

    /**
     * Vincula um usuário a um curso em uma universidade
     */
    public function enrollUser($userId, $courseId, $universityId, $data) {
        return $this->db->insert(
            "usuario_curso_universidade",
            [
                'usuario_id' => $userId,
                'curso_id' => $courseId,
                'universidade_id' => $universityId,
                'data_inicio' => $data['data_inicio'] ?? date('Y-m-d'),
                'data_fim' => $data['data_fim'] ?? null,
                'situacao' => $data['situacao'] ?? 'cursando'
            ]
        );
    }

    /**
     * Atualiza vínculo de um usuário com um curso
     */
    public function updateEnrollment($userId, $courseId, $universityId, $data) {
        return $this->db->update(
            "usuario_curso_universidade",
            [
                'data_inicio' => $data['data_inicio'],
                'data_fim' => $data['data_fim'],
                'situacao' => $data['situacao']
            ],
            "usuario_id = ? AND curso_id = ? AND universidade_id = ?",
            [$userId, $courseId, $universityId]
        );
    }

    /**
     * Lista todas as matrículas com informações detalhadas
     */
    public function listEnrollments() {
        return $this->db->select(
            "SELECT 
                ucu.id,
                ucu.usuario_id,
                ucu.curso_id,
                ucu.universidade_id,
                ucu.data_inicio as data_matricula,
                ucu.data_fim,
                ucu.situacao,
                u.nome as nome_aluno,
                c.nome as nome_curso,
                univ.nome as nome_universidade,
                univ.sigla as universidade_sigla
            FROM usuario_curso_universidade ucu
            JOIN usuarios u ON ucu.usuario_id = u.id
            JOIN cursos c ON ucu.curso_id = c.id
            JOIN universidades univ ON ucu.universidade_id = univ.id
            ORDER BY ucu.data_inicio DESC"
        );
    }

    /**
     * Lista alunos disponíveis para matrícula
     */
    public function listAvailableStudents() {
        return $this->db->select(
            "SELECT id, nome, email FROM usuarios WHERE ativo = 1 ORDER BY nome"
        );
    }

    /**
     * Matricula um aluno em um curso
     */
    public function enrollStudent($userId, $courseId, $universityId, $data = []) {
        // Verificar se já existe matrícula
        $existingEnrollment = $this->db->select(
            "SELECT 1 FROM usuario_curso_universidade 
             WHERE usuario_id = ? AND curso_id = ? AND universidade_id = ?",
            [$userId, $courseId, $universityId]
        );

        if (!empty($existingEnrollment)) {
            throw new Exception("Aluno já está matriculado neste curso");
        }

        return $this->db->execute(
            "INSERT INTO usuario_curso_universidade 
             (usuario_id, curso_id, universidade_id, data_inicio, data_fim, situacao) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $courseId,
                $universityId,
                $data['data_inicio'] ?? date('Y-m-d'),
                $data['data_fim'] ?? null,
                $data['situacao'] ?? 'cursando'
            ]
        );
    }

    /**
     * Cancela a matrícula de um aluno
     */
    public function unenrollStudent($userId, $courseId, $universityId) {
        return $this->db->execute(
            "UPDATE usuario_curso_universidade 
             SET situacao = 'abandonado', data_fim = CURRENT_DATE 
             WHERE usuario_id = ? AND curso_id = ? AND universidade_id = ?",
            [$userId, $courseId, $universityId]
        );
    }

    /**
     * Atualiza a situação da matrícula
     */
    public function updateEnrollmentStatus($userId, $courseId, $universityId, $situacao, $dataFim = null, $motivo = null) {
        // Get current enrollment
        $stmt = $this->db->prepare(
            "SELECT id, data_inicio FROM usuario_curso_universidade 
             WHERE usuario_id = ? AND curso_id = ? AND universidade_id = ?"
        );
        $stmt->execute([$userId, $courseId, $universityId]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment) {
            throw new Exception("Matrícula não encontrada");
        }

        // Update enrollment status
        $success = $this->db->execute(
            "UPDATE usuario_curso_universidade 
             SET situacao = ?, data_fim = ? 
             WHERE id = ?",
            [$situacao, $dataFim, $enrollment['id']]
        );

        if ($success) {
            // Log the change in history
            $this->logEnrollmentHistory(
                $enrollment['id'],
                $userId,
                $courseId,
                $universityId,
                $situacao,
                $enrollment['data_inicio'],
                $dataFim,
                $motivo
            );
        }

        return $success;
    }

    /**
     * Verifica se um aluno está matriculado em um curso
     */
    public function isStudentEnrolled($userId, $courseId, $universityId) {
        $result = $this->db->select(
            "SELECT situacao FROM usuario_curso_universidade 
             WHERE usuario_id = ? AND curso_id = ? AND universidade_id = ?",
            [$userId, $courseId, $universityId]
        );
        return !empty($result) ? $result[0]['situacao'] : false;
    }

    /**
     * Lista os cursos de uma determinada universidade
     * @param int $universityId ID da universidade
     * @return array Lista de cursos da universidade
     */
    public function listByUniversity($universityId) {
        $sql = "SELECT c.* FROM cursos c
                INNER JOIN universidade_cursos uc ON c.id = uc.curso_id
                WHERE uc.universidade_id = :university_id
                ORDER BY c.nome";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':university_id', $universityId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra uma alteração no histórico de matrículas
     */
    private function logEnrollmentHistory($matriculaId, $userId, $courseId, $universityId, $status, $startDate, $endDate = null, $reason = null) {
        $sql = "INSERT INTO matricula_historico 
                (matricula_id, user_id, curso_id, universidade_id, situacao, data_inicio, data_fim, motivo)
                VALUES (:matricula_id, :user_id, :course_id, :university_id, :status, :start_date, :end_date, :reason)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':matricula_id', $matriculaId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
        $stmt->bindParam(':university_id', $universityId, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
        $stmt->bindParam(':reason', $reason, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Obtém o histórico de uma matrícula específica
     */
    public function getEnrollmentHistory($matriculaId) {
        $sql = "SELECT h.*, 
                u.nome as nome_aluno,
                c.nome as nome_curso,
                univ.nome as nome_universidade
                FROM matricula_historico h
                INNER JOIN users u ON h.user_id = u.id
                INNER JOIN cursos c ON h.curso_id = c.id
                INNER JOIN universidades univ ON h.universidade_id = univ.id
                WHERE h.matricula_id = :matricula_id
                ORDER BY h.data_alteracao DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':matricula_id', $matriculaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém estatísticas de matrículas
     */
    public function getEnrollmentStats() {
        $result = $this->db->select(
            "SELECT 
                COUNT(*) as total_matriculas,
                SUM(CASE WHEN situacao = 'cursando' THEN 1 ELSE 0 END) as total_cursando,
                SUM(CASE WHEN situacao = 'concluido' THEN 1 ELSE 0 END) as total_concluidos,
                SUM(CASE WHEN situacao = 'trancado' THEN 1 ELSE 0 END) as total_trancados,
                COUNT(DISTINCT usuario_id) as total_alunos,
                COUNT(DISTINCT curso_id) as total_cursos,
                COUNT(DISTINCT universidade_id) as total_universidades
            FROM usuario_curso_universidade"
        );

        return $result[0] ?? [
            'total_matriculas' => 0,
            'total_cursando' => 0,
            'total_concluidos' => 0,
            'total_trancados' => 0,
            'total_alunos' => 0,
            'total_cursos' => 0,
            'total_universidades' => 0
        ];
    }
}
