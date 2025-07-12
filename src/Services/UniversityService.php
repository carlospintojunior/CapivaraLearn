<?php
namespace App\Services;

use PDO;
use Exception;
use Monolog\Logger;

class UniversityService
{
    private $db;
    private $logger;

    public function __construct(PDO $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Lista todas as universidades
     */
    public function getAll(bool $activeOnly = false): array
    {
        try {
            $sql = "SELECT id, nome, sigla, cidade, estado, ativo, created_at, updated_at FROM universidades";
            
            if ($activeOnly) {
                $sql .= " WHERE ativo = 1";
            }
            
            $sql .= " ORDER BY nome";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $universities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->logger->info('Universidades listadas', [
                'count' => count($universities),
                'active_only' => $activeOnly
            ]);
            
            return $universities;
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao listar universidades', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new Exception('Erro ao carregar universidades');
        }
    }

    /**
     * Busca uma universidade por ID
     */
    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nome, sigla, cidade, estado, ativo, created_at, updated_at 
                FROM universidades 
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);
            $university = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($university) {
                $this->logger->info('Universidade encontrada', ['id' => $id]);
            } else {
                $this->logger->warning('Universidade não encontrada', ['id' => $id]);
            }
            
            return $university ?: null;
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao buscar universidade', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            throw new Exception('Erro ao carregar universidade');
        }
    }

    /**
     * Cria uma nova universidade
     */
    public function create(array $data): array
    {
        try {
            $this->logger->info('Criando nova universidade', $data);
            
            // Validar dados
            $this->validateUniversityData($data);
            
            // Verificar se já existe universidade com mesmo nome ou sigla
            $stmt = $this->db->prepare("
                SELECT id FROM universidades 
                WHERE (nome = ? OR sigla = ?) AND ativo = 1
            ");
            
            $stmt->execute([$data['nome'], $data['sigla']]);
            
            if ($stmt->fetch()) {
                throw new Exception('Já existe uma universidade com esse nome ou sigla');
            }
            
            // Inserir nova universidade
            $stmt = $this->db->prepare("
                INSERT INTO universidades (nome, sigla, cidade, estado, ativo, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $data['nome'],
                $data['sigla'],
                $data['cidade'],
                $data['estado'],
                $data['ativo'] ?? 1
            ]);
            
            $universityId = $this->db->lastInsertId();
            
            $this->logger->info('Universidade criada com sucesso', [
                'id' => $universityId,
                'nome' => $data['nome']
            ]);
            
            return $this->getById($universityId);
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao criar universidade', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            throw $e;
        }
    }

    /**
     * Atualiza uma universidade existente
     */
    public function update(int $id, array $data): array
    {
        try {
            $this->logger->info('Atualizando universidade', ['id' => $id, 'data' => $data]);
            
            // Verificar se a universidade existe
            $existing = $this->getById($id);
            if (!$existing) {
                throw new Exception('Universidade não encontrada');
            }
            
            // Validar dados
            $this->validateUniversityData($data);
            
            // Verificar se já existe outra universidade com mesmo nome ou sigla
            $stmt = $this->db->prepare("
                SELECT id FROM universidades 
                WHERE (nome = ? OR sigla = ?) AND ativo = 1 AND id != ?
            ");
            
            $stmt->execute([$data['nome'], $data['sigla'], $id]);
            
            if ($stmt->fetch()) {
                throw new Exception('Já existe outra universidade com esse nome ou sigla');
            }
            
            // Atualizar universidade
            $stmt = $this->db->prepare("
                UPDATE universidades 
                SET nome = ?, sigla = ?, cidade = ?, estado = ?, ativo = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['nome'],
                $data['sigla'],
                $data['cidade'],
                $data['estado'],
                $data['ativo'] ?? 1,
                $id
            ]);
            
            $this->logger->info('Universidade atualizada com sucesso', [
                'id' => $id,
                'nome' => $data['nome']
            ]);
            
            return $this->getById($id);
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao atualizar universidade', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $data
            ]);
            
            throw $e;
        }
    }

    /**
     * Desativa uma universidade (soft delete)
     */
    public function deactivate(int $id): bool
    {
        try {
            $this->logger->info('Desativando universidade', ['id' => $id]);
            
            // Verificar se a universidade existe
            $existing = $this->getById($id);
            if (!$existing) {
                throw new Exception('Universidade não encontrada');
            }
            
            // Verificar se há cursos ativos vinculados
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM cursos 
                WHERE universidade_id = ? AND ativo = 1
            ");
            
            $stmt->execute([$id]);
            $courseCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($courseCount > 0) {
                throw new Exception('Não é possível desativar uma universidade com cursos ativos');
            }
            
            // Desativar universidade
            $stmt = $this->db->prepare("
                UPDATE universidades 
                SET ativo = 0, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);
            
            $this->logger->info('Universidade desativada com sucesso', ['id' => $id]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao desativar universidade', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            throw $e;
        }
    }

    /**
     * Exclui permanentemente uma universidade
     */
    public function delete(int $id): bool
    {
        try {
            $this->logger->info('Excluindo universidade', ['id' => $id]);
            
            // Verificar se a universidade existe
            $existing = $this->getById($id);
            if (!$existing) {
                throw new Exception('Universidade não encontrada');
            }
            
            // Verificar se há cursos vinculados
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM cursos 
                WHERE universidade_id = ?
            ");
            
            $stmt->execute([$id]);
            $courseCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($courseCount > 0) {
                throw new Exception('Não é possível excluir uma universidade com cursos vinculados');
            }
            
            // Excluir universidade
            $stmt = $this->db->prepare("DELETE FROM universidades WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->logger->info('Universidade excluída com sucesso', ['id' => $id]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao excluir universidade', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            
            throw $e;
        }
    }

    /**
     * Valida os dados de uma universidade
     */
    private function validateUniversityData(array $data): void
    {
        if (empty($data['nome'])) {
            throw new Exception('Nome é obrigatório');
        }
        
        if (empty($data['sigla'])) {
            throw new Exception('Sigla é obrigatória');
        }
        
        if (empty($data['cidade'])) {
            throw new Exception('Cidade é obrigatória');
        }
        
        if (empty($data['estado'])) {
            throw new Exception('Estado é obrigatório');
        }
        
        // Validar formato da sigla
        if (strlen($data['sigla']) > 10) {
            throw new Exception('Sigla deve ter no máximo 10 caracteres');
        }
        
        // Validar estados brasileiros
        $validStates = [
            'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
            'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
            'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
        ];
        
        if (!in_array($data['estado'], $validStates)) {
            throw new Exception('Estado inválido');
        }
    }

    /**
     * Obtém estatísticas das universidades
     */
    public function getStatistics(): array
    {
        try {
            $stats = [];
            
            // Total de universidades
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM universidades");
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Universidades ativas
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM universidades WHERE ativo = 1");
            $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Universidades inativas
            $stats['inactive'] = $stats['total'] - $stats['active'];
            
            // Universidades por estado
            $stmt = $this->db->query("
                SELECT estado, COUNT(*) as count 
                FROM universidades 
                WHERE ativo = 1 
                GROUP BY estado 
                ORDER BY count DESC
            ");
            $stats['by_state'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->logger->info('Estatísticas das universidades geradas');
            
            return $stats;
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao gerar estatísticas', [
                'error' => $e->getMessage()
            ]);
            
            throw new Exception('Erro ao carregar estatísticas');
        }
    }
}
