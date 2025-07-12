<?php
namespace App\Services;

use PDO;
use Exception;
use Monolog\Logger;

class AuthService
{
    private $db;
    private $logger;

    public function __construct(PDO $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Autentica um usuário
     */
    public function authenticate(string $email, string $password): ?array
    {
        try {
            $this->logger->info('Tentativa de autenticação', ['email' => $email]);
            
            $stmt = $this->db->prepare("
                SELECT id, nome, email, senha, ativo, created_at, updated_at
                FROM usuarios 
                WHERE email = ? AND ativo = 1
            ");
            
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $this->logger->warning('Usuário não encontrado', ['email' => $email]);
                return null;
            }
            
            if (!password_verify($password, $user['senha'])) {
                $this->logger->warning('Senha incorreta', ['email' => $email]);
                return null;
            }
            
            // Remover senha do retorno
            unset($user['senha']);
            
            $this->logger->info('Autenticação bem-sucedida', [
                'user_id' => $user['id'],
                'email' => $email
            ]);
            
            // Registrar atividade de login
            $this->logActivity($user['id'], 'login', 'Login realizado com sucesso');
            
            return $user;
            
        } catch (Exception $e) {
            $this->logger->error('Erro na autenticação', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $email
            ]);
            
            throw new Exception('Erro interno do servidor');
        }
    }

    /**
     * Registra uma atividade do usuário
     */
    public function logActivity(int $userId, string $action, string $details): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO logs_atividade (usuario_id, acao, detalhes, timestamp) 
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([$userId, $action, $details]);
            
            $this->logger->info('Atividade registrada', [
                'user_id' => $userId,
                'action' => $action,
                'details' => $details
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erro ao registrar atividade', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'action' => $action
            ]);
        }
    }

    /**
     * Valida se o usuário está autenticado
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Obtém o usuário atual da sessão
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
        ];
    }

    /**
     * Realiza logout do usuário
     */
    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            $this->logger->info('Logout realizado', ['user_id' => $userId]);
            $this->logActivity($userId, 'logout', 'Logout realizado');
        }
        
        session_destroy();
    }

    /**
     * Registra um novo usuário
     */
    public function register(string $nome, string $email, string $password): array
    {
        try {
            $this->logger->info('Tentativa de registro', ['email' => $email]);
            
            // Verificar se o email já existe
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                throw new Exception('Email já está em uso');
            }
            
            // Hash da senha
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Inserir novo usuário
            $stmt = $this->db->prepare("
                INSERT INTO usuarios (nome, email, senha, ativo, created_at, updated_at) 
                VALUES (?, ?, ?, 1, NOW(), NOW())
            ");
            
            $stmt->execute([$nome, $email, $hashedPassword]);
            $userId = $this->db->lastInsertId();
            
            $this->logger->info('Usuário registrado com sucesso', [
                'user_id' => $userId,
                'email' => $email
            ]);
            
            // Registrar atividade
            $this->logActivity($userId, 'register', 'Usuário registrado no sistema');
            
            return [
                'id' => $userId,
                'nome' => $nome,
                'email' => $email,
                'ativo' => 1
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Erro no registro', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $email
            ]);
            
            throw $e;
        }
    }

    /**
     * Atualiza a senha do usuário
     */
    public function updatePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        try {
            $this->logger->info('Tentativa de atualização de senha', ['user_id' => $userId]);
            
            // Verificar senha atual
            $stmt = $this->db->prepare("SELECT senha FROM usuarios WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($currentPassword, $user['senha'])) {
                $this->logger->warning('Senha atual incorreta', ['user_id' => $userId]);
                return false;
            }
            
            // Atualizar senha
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("
                UPDATE usuarios 
                SET senha = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([$hashedPassword, $userId]);
            
            $this->logger->info('Senha atualizada com sucesso', ['user_id' => $userId]);
            $this->logActivity($userId, 'password_update', 'Senha atualizada');
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Erro na atualização de senha', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId
            ]);
            
            throw new Exception('Erro interno do servidor');
        }
    }
}
