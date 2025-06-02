<?php
/**
 * Serviço para gerenciamento de módulos
 */
class ModuleService {
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
     * Lista todos os módulos de um usuário
     */
    public function listByUser($userId) {
        return $this->db->select(
            "SELECT m.*, 
                    COUNT(t.id) as total_topicos,
                    COUNT(CASE WHEN t.concluido = 1 THEN 1 END) as topicos_concluidos,
                    COUNT(CASE WHEN t.data_fim < CURDATE() AND t.concluido = 0 THEN 1 END) as topicos_atrasados
             FROM modulos m
             LEFT JOIN topicos t ON m.id = t.modulo_id
             WHERE m.usuario_id = ? AND m.ativo = 1
             GROUP BY m.id, m.nome, m.codigo, m.descricao, m.data_inicio, m.data_fim
             ORDER BY m.data_inicio DESC",
            [$userId]
        );
    }

    /**
     * Busca um módulo por ID
     */
    public function getById($id, $userId) {
        $result = $this->db->select(
            "SELECT * FROM modulos WHERE id = ? AND usuario_id = ? AND ativo = 1",
            [$id, $userId]
        );
        return $result[0] ?? null;
    }

    /**
     * Cria um novo módulo
     */
    public function create($userId, $data) {
        return $this->db->insert(
            "modulos",
            [
                'usuario_id' => $userId,
                'nome' => $data['nome'],
                'codigo' => $data['codigo'],
                'descricao' => $data['descricao'] ?? null,
                'data_inicio' => $data['data_inicio'],
                'data_fim' => $data['data_fim'],
                'cor' => $data['cor'] ?? '#3498db'
            ]
        );
    }

    /**
     * Atualiza um módulo existente
     */
    public function update($id, $userId, $data) {
        return $this->db->update(
            "modulos",
            [
                'nome' => $data['nome'],
                'codigo' => $data['codigo'],
                'descricao' => $data['descricao'] ?? null,
                'data_inicio' => $data['data_inicio'],
                'data_fim' => $data['data_fim'],
                'cor' => $data['cor'] ?? '#3498db'
            ],
            "id = ? AND usuario_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Remove (soft delete) um módulo
     */
    public function delete($id, $userId) {
        return $this->db->update(
            "modulos",
            ['ativo' => 0],
            "id = ? AND usuario_id = ?",
            [$id, $userId]
        );
    }
}
