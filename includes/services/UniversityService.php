<?php
require_once __DIR__ . '/../log_sistema.php';

/**
 * Serviço para gerenciamento de universidades com isolamento por usuário
 * Versão: 2.0 - Compatível com estrutura do banco validada
 */
class UniversityService {
    private $db;
    private $userId;
    private static $instance = null;

    private function __construct() {
        // Garantir que a sessão está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar se usuário está logado
        if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
            throw new Exception('Usuário não autenticado');
        }
        
        $this->userId = $_SESSION['user_id'];
        
        // Conectar diretamente ao banco
        try {
            $this->db = new PDO(
                "mysql:host=localhost;dbname=capivaralearn;charset=utf8mb4",
                "root",
                "",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Erro de conexão: " . $e->getMessage());
        }
        
        log_sistema("UniversityService inicializado para usuário ID: {$this->userId}", 'INFO');
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Lista todas as universidades ativas do usuário logado
     */
    public function listAll() {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM universidades 
                 WHERE usuario_id = ? AND ativo = 1 
                 ORDER BY nome"
            );
            $stmt->execute([$this->userId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            log_sistema("Listadas " . count($result) . " universidades para usuário {$this->userId}", 'INFO');
            return $result;
        } catch (Exception $e) {
            log_sistema("Erro ao listar universidades: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Busca uma universidade por ID (apenas do usuário logado)
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM universidades 
                 WHERE id = ? AND usuario_id = ? AND ativo = 1"
            );
            $stmt->execute([$id, $this->userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                log_sistema("Universidade ID {$id} encontrada para usuário {$this->userId}", 'INFO');
            } else {
                log_sistema("Universidade ID {$id} não encontrada para usuário {$this->userId}", 'WARNING');
            }
            
            return $result ?: null;
        } catch (Exception $e) {
            log_sistema("Erro ao buscar universidade ID {$id}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Cria uma nova universidade para o usuário logado
     */
    public function create($data) {
        try {
            // Validar dados obrigatórios
            if (empty($data['nome'])) {
                throw new Exception('Nome da universidade é obrigatório');
            }
            
            $stmt = $this->db->prepare(
                "INSERT INTO universidades (nome, sigla, cidade, estado, usuario_id) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            
            $result = $stmt->execute([
                $data['nome'],
                $data['sigla'] ?? null,
                $data['cidade'] ?? null,
                $data['estado'] ?? null,
                $this->userId
            ]);
            
            if ($result) {
                $universityId = $this->db->lastInsertId();
                log_sistema("Universidade '{$data['nome']}' criada com ID {$universityId} para usuário {$this->userId}", 'SUCCESS');
                return $universityId;
            }
            
            throw new Exception('Falha ao criar universidade');
        } catch (Exception $e) {
            log_sistema("Erro ao criar universidade: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Atualiza uma universidade existente (apenas do usuário logado)
     */
    public function update($id, $data) {
        try {
            // Verificar se a universidade pertence ao usuário
            if (!$this->getById($id)) {
                throw new Exception('Universidade não encontrada ou não pertence ao usuário');
            }
            
            // Validar dados obrigatórios
            if (empty($data['nome'])) {
                throw new Exception('Nome da universidade é obrigatório');
            }
            
            $stmt = $this->db->prepare(
                "UPDATE universidades 
                 SET nome = ?, sigla = ?, cidade = ?, estado = ?
                 WHERE id = ? AND usuario_id = ?"
            );
            
            $result = $stmt->execute([
                $data['nome'],
                $data['sigla'] ?? null,
                $data['cidade'] ?? null,
                $data['estado'] ?? null,
                $id,
                $this->userId
            ]);
            
            if ($result) {
                log_sistema("Universidade ID {$id} atualizada para usuário {$this->userId}", 'SUCCESS');
                return true;
            }
            
            throw new Exception('Falha ao atualizar universidade');
        } catch (Exception $e) {
            log_sistema("Erro ao atualizar universidade ID {$id}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Remove (soft delete) uma universidade (apenas do usuário logado)
     */
    public function delete($id) {
        try {
            // Verificar se a universidade pertence ao usuário
            if (!$this->getById($id)) {
                throw new Exception('Universidade não encontrada ou não pertence ao usuário');
            }
            
            $stmt = $this->db->prepare(
                "UPDATE universidades 
                 SET ativo = 0 
                 WHERE id = ? AND usuario_id = ?"
            );
            
            $result = $stmt->execute([$id, $this->userId]);
            
            if ($result) {
                log_sistema("Universidade ID {$id} removida (soft delete) para usuário {$this->userId}", 'SUCCESS');
                return true;
            }
            
            throw new Exception('Falha ao remover universidade');
        } catch (Exception $e) {
            log_sistema("Erro ao remover universidade ID {$id}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Lista todos os cursos de uma universidade do usuário logado
     */
    public function listCourses($universityId) {
        try {
            // Verificar se a universidade pertence ao usuário
            if (!$this->getById($universityId)) {
                throw new Exception('Universidade não encontrada ou não pertence ao usuário');
            }
            
            $stmt = $this->db->prepare(
                "SELECT c.* FROM cursos c 
                 INNER JOIN universidade_cursos uc ON c.id = uc.curso_id 
                 WHERE uc.universidade_id = ? AND uc.usuario_id = ? 
                   AND uc.ativo = 1 AND c.ativo = 1
                 ORDER BY c.nome"
            );
            $stmt->execute([$universityId, $this->userId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            log_sistema("Listados " . count($result) . " cursos da universidade {$universityId} para usuário {$this->userId}", 'INFO');
            return $result;
        } catch (Exception $e) {
            log_sistema("Erro ao listar cursos da universidade {$universityId}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Adiciona um curso à universidade (com isolamento por usuário)
     */
    public function addCourse($universityId, $courseId) {
        try {
            // Verificar se a universidade pertence ao usuário
            if (!$this->getById($universityId)) {
                throw new Exception('Universidade não encontrada ou não pertence ao usuário');
            }
            
            // Verificar se o curso pertence ao usuário (sem dependência circular)
            $stmt = $this->db->prepare(
                "SELECT id FROM cursos WHERE id = ? AND usuario_id = ? AND ativo = 1"
            );
            $stmt->execute([$courseId, $this->userId]);
            if (!$stmt->fetch()) {
                throw new Exception('Curso não encontrado ou não pertence ao usuário');
            }
            
            $stmt = $this->db->prepare(
                "INSERT INTO universidade_cursos (universidade_id, curso_id, usuario_id) 
                 VALUES (?, ?, ?)"
            );
            
            $result = $stmt->execute([$universityId, $courseId, $this->userId]);
            
            if ($result) {
                log_sistema("Curso {$courseId} adicionado à universidade {$universityId} para usuário {$this->userId}", 'SUCCESS');
                return true;
            }
            
            throw new Exception('Falha ao adicionar curso à universidade');
        } catch (Exception $e) {
            log_sistema("Erro ao adicionar curso {$courseId} à universidade {$universityId}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Remove um curso da universidade (com isolamento por usuário)
     */
    public function removeCourse($universityId, $courseId) {
        try {
            $stmt = $this->db->prepare(
                "UPDATE universidade_cursos 
                 SET ativo = 0 
                 WHERE universidade_id = ? AND curso_id = ? AND usuario_id = ?"
            );
            
            $result = $stmt->execute([$universityId, $courseId, $this->userId]);
            
            if ($result) {
                log_sistema("Curso {$courseId} removido da universidade {$universityId} para usuário {$this->userId}", 'SUCCESS');
                return true;
            }
            
            throw new Exception('Falha ao remover curso da universidade');
        } catch (Exception $e) {
            log_sistema("Erro ao remover curso {$courseId} da universidade {$universityId}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
}
?>

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Lista todas as universidades ativas do usuário logado
     */
    public function listAll() {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM universidades 
                 WHERE usuario_id = ? AND ativo = 1 
                 ORDER BY nome"
            );
            $stmt->execute([$this->userId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            log_sistema("Listadas " . count($result) . " universidades para usuário {$this->userId}", 'INFO');
            return $result;
        } catch (Exception $e) {
            log_sistema("Erro ao listar universidades: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Busca uma universidade por ID (apenas do usuário logado)
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM universidades 
                 WHERE id = ? AND usuario_id = ? AND ativo = 1"
            );
            $stmt->execute([$id, $this->userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                log_sistema("Universidade ID {$id} encontrada para usuário {$this->userId}", 'INFO');
            } else {
                log_sistema("Universidade ID {$id} não encontrada para usuário {$this->userId}", 'WARNING');
            }
            
            return $result ?: null;
        } catch (Exception $e) {
            log_sistema("Erro ao buscar universidade ID {$id}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Cria uma nova universidade para o usuário logado
     */
    public function create($data) {
        try {
            // Validar dados obrigatórios
            if (empty($data['nome'])) {
                throw new Exception('Nome da universidade é obrigatório');
            }
            
            $stmt = $this->db->prepare(
                "INSERT INTO universidades (nome, sigla, cidade, estado, usuario_id) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            
            $result = $stmt->execute([
                $data['nome'],
                $data['sigla'] ?? null,
                $data['cidade'] ?? null,
                $data['estado'] ?? null,
                $this->userId
            ]);
            
            if ($result) {
                $universityId = $this->db->lastInsertId();
                log_sistema("Universidade '{$data['nome']}' criada com ID {$universityId} para usuário {$this->userId}", 'SUCCESS');
                return $universityId;
            }
            
            throw new Exception('Falha ao criar universidade');
        } catch (Exception $e) {
            log_sistema("Erro ao criar universidade: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Atualiza uma universidade existente (apenas do usuário logado)
     */
    public function update($id, $data) {
        try {
            // Verificar se a universidade pertence ao usuário
            if (!$this->getById($id)) {
                throw new Exception('Universidade não encontrada ou não pertence ao usuário');
            }
            
            // Validar dados obrigatórios
            if (empty($data['nome'])) {
                throw new Exception('Nome da universidade é obrigatório');
            }
            
            $stmt = $this->db->prepare(
                "UPDATE universidades 
                 SET nome = ?, sigla = ?, cidade = ?, estado = ?
                 WHERE id = ? AND usuario_id = ?"
            );
            
            $result = $stmt->execute([
                $data['nome'],
                $data['sigla'] ?? null,
                $data['cidade'] ?? null,
                $data['estado'] ?? null,
                $id,
                $this->userId
            ]);
            
            if ($result) {
                log_sistema("Universidade ID {$id} atualizada para usuário {$this->userId}", 'SUCCESS');
                return true;
            }
            
            throw new Exception('Falha ao atualizar universidade');
        } catch (Exception $e) {
            log_sistema("Erro ao atualizar universidade ID {$id}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Remove (soft delete) uma universidade (apenas do usuário logado)
     */
    public function delete($id) {
        try {
            // Verificar se a universidade pertence ao usuário
            if (!$this->getById($id)) {
                throw new Exception('Universidade não encontrada ou não pertence ao usuário');
            }
            
            $stmt = $this->db->prepare(
                "UPDATE universidades 
                 SET ativo = 0 
                 WHERE id = ? AND usuario_id = ?"
            );
            
            $result = $stmt->execute([$id, $this->userId]);
            
            if ($result) {
                log_sistema("Universidade ID {$id} removida (soft delete) para usuário {$this->userId}", 'SUCCESS');
                return true;
            }
            
            throw new Exception('Falha ao remover universidade');
        } catch (Exception $e) {
            log_sistema("Erro ao remover universidade ID {$id}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Lista todos os cursos de uma universidade do usuário logado
     */
    public function listCourses($universityId) {
        try {
            // Verificar se a universidade pertence ao usuário
            if (!$this->getById($universityId)) {
                throw new Exception('Universidade não encontrada ou não pertence ao usuário');
            }
            
            $stmt = $this->db->prepare(
                "SELECT c.* FROM cursos c 
                 INNER JOIN universidade_cursos uc ON c.id = uc.curso_id 
                 WHERE uc.universidade_id = ? AND uc.usuario_id = ? 
                   AND uc.ativo = 1 AND c.ativo = 1
                 ORDER BY c.nome"
            );
            $stmt->execute([$universityId, $this->userId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            log_sistema("Listados " . count($result) . " cursos da universidade {$universityId} para usuário {$this->userId}", 'INFO');
            return $result;
        } catch (Exception $e) {
            log_sistema("Erro ao listar cursos da universidade {$universityId}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Adiciona um curso à universidade (com isolamento por usuário)
     */
    public function addCourse($universityId, $courseId) {
        try {
            // Verificar se a universidade pertence ao usuário
            if (!$this->getById($universityId)) {
                throw new Exception('Universidade não encontrada ou não pertence ao usuário');
            }
            
            // Verificar se o curso pertence ao usuário
            $courseService = CourseService::getInstance();
            if (!$courseService->getById($courseId)) {
                throw new Exception('Curso não encontrado ou não pertence ao usuário');
            }
            
            $stmt = $this->db->prepare(
                "INSERT INTO universidade_cursos (universidade_id, curso_id, usuario_id) 
                 VALUES (?, ?, ?)"
            );
            
            $result = $stmt->execute([$universityId, $courseId, $this->userId]);
            
            if ($result) {
                log_sistema("Curso {$courseId} adicionado à universidade {$universityId} para usuário {$this->userId}", 'SUCCESS');
                return true;
            }
            
            throw new Exception('Falha ao adicionar curso à universidade');
        } catch (Exception $e) {
            log_sistema("Erro ao adicionar curso {$courseId} à universidade {$universityId}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Remove um curso da universidade (com isolamento por usuário)
     */
    public function removeCourse($universityId, $courseId) {
        try {
            $stmt = $this->db->prepare(
                "UPDATE universidade_cursos 
                 SET ativo = 0 
                 WHERE universidade_id = ? AND curso_id = ? AND usuario_id = ?"
            );
            
            $result = $stmt->execute([$universityId, $courseId, $this->userId]);
            
            if ($result) {
                log_sistema("Curso {$courseId} removido da universidade {$universityId} para usuário {$this->userId}", 'SUCCESS');
                return true;
            }
            
            throw new Exception('Falha ao remover curso da universidade');
        } catch (Exception $e) {
            log_sistema("Erro ao remover curso {$courseId} da universidade {$universityId}: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
}
?>
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
