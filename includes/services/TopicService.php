<?php
/**
 * Serviço para gerenciamento de tópicos
 */
class TopicService {
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
     * Lista todos os tópicos de um módulo
     */
    public function listByModule($moduleId, $userId) {
        return $this->db->select(
            "SELECT t.* FROM topicos t 
             INNER JOIN modulos m ON t.modulo_id = m.id
             WHERE t.modulo_id = ? AND m.usuario_id = ?
             ORDER BY t.data_inicio ASC",
            [$moduleId, $userId]
        );
    }

    /**
     * Lista tópicos próximos/ativos do usuário
     */
    public function listUpcoming($userId) {
        return $this->db->select(
            "SELECT t.*, m.nome as modulo_nome, m.codigo as modulo_codigo
             FROM topicos t
             JOIN modulos m ON t.modulo_id = m.id
             WHERE m.usuario_id = ? AND m.ativo = 1 
             AND (t.data_fim >= CURDATE() OR (t.data_fim < CURDATE() AND t.concluido = 0))
             ORDER BY t.data_inicio ASC",
            [$userId]
        );
    }

    /**
     * Busca um tópico por ID
     */
    public function getById($id, $userId) {
        $result = $this->db->select(
            "SELECT t.* FROM topicos t 
             INNER JOIN modulos m ON t.modulo_id = m.id
             WHERE t.id = ? AND m.usuario_id = ?",
            [$id, $userId]
        );
        return $result[0] ?? null;
    }

    /**
     * Cria um novo tópico
     */
    public function create($moduleId, $userId, $data) {
        // Verifica se o módulo pertence ao usuário
        $module = $this->db->select(
            "SELECT id FROM modulos WHERE id = ? AND usuario_id = ? AND ativo = 1",
            [$moduleId, $userId]
        );

        if (empty($module)) {
            throw new Exception("Módulo não encontrado ou sem permissão");
        }

        return $this->db->insert(
            "topicos",
            [
                'modulo_id' => $moduleId,
                'nome' => $data['nome'],
                'descricao' => $data['descricao'] ?? null,
                'data_inicio' => $data['data_inicio'],
                'data_fim' => $data['data_fim'],
                'ordem' => $data['ordem'] ?? 1
            ]
        );
    }

    /**
     * Atualiza um tópico existente
     */
    public function update($id, $userId, $data) {
        // Verifica se o tópico pertence ao usuário
        $topic = $this->getById($id, $userId);
        if (!$topic) {
            throw new Exception("Tópico não encontrado ou sem permissão");
        }

        return $this->db->update(
            "topicos",
            [
                'nome' => $data['nome'],
                'descricao' => $data['descricao'] ?? null,
                'data_inicio' => $data['data_inicio'],
                'data_fim' => $data['data_fim'],
                'ordem' => $data['ordem'] ?? 1
            ],
            "id = ?",
            [$id]
        );
    }

    /**
     * Marca um tópico como concluído/não concluído
     */
    public function toggleComplete($id, $userId) {
        $topic = $this->getById($id, $userId);
        if (!$topic) {
            throw new Exception("Tópico não encontrado ou sem permissão");
        }

        return $this->db->update(
            "topicos",
            ['concluido' => !$topic['concluido']],
            "id = ?",
            [$id]
        );
    }

    /**
     * Atualiza a nota de um tópico
     */
    public function updateGrade($id, $userId, $grade) {
        $topic = $this->getById($id, $userId);
        if (!$topic) {
            throw new Exception("Tópico não encontrado ou sem permissão");
        }

        return $this->db->update(
            "topicos",
            ['nota' => $grade],
            "id = ?",
            [$id]
        );
    }

    /**
     * Remove um tópico
     */
    public function delete($id, $userId) {
        $topic = $this->getById($id, $userId);
        if (!$topic) {
            throw new Exception("Tópico não encontrado ou sem permissão");
        }

        return $this->db->delete(
            "topicos",
            "id = ?",
            [$id]
        );
    }

    /**
     * Lista arquivos anexados a um tópico
     */
    public function listTopicFiles($topicId) {
        return $this->db->select(
            "SELECT a.* FROM arquivos a
            INNER JOIN topico_arquivo ta ON a.id = ta.arquivo_id
            WHERE ta.topico_id = ?
            ORDER BY a.data_upload DESC",
            [$topicId]
        );
    }

    /**
     * Anexa um arquivo ao tópico
     */
    public function attachFile($topicId, $fileId) {
        return $this->db->execute(
            "INSERT INTO topico_arquivo (topico_id, arquivo_id) VALUES (?, ?)",
            [$topicId, $fileId]
        );
    }

    /**
     * Remove um arquivo do tópico
     */
    public function detachFile($topicId, $fileId) {
        return $this->db->execute(
            "DELETE FROM topico_arquivo WHERE topico_id = ? AND arquivo_id = ?",
            [$topicId, $fileId]
        );
    }

    /**
     * Verifica se um arquivo está anexado ao tópico
     */
    public function hasFile($topicId, $fileId) {
        $result = $this->db->select(
            "SELECT 1 FROM topico_arquivo WHERE topico_id = ? AND arquivo_id = ?",
            [$topicId, $fileId]
        );
        return !empty($result);
    }
}
