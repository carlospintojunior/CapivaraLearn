<?php

namespace CapivaraLearn;

use PDO;
use PDOException;

/**
 * Classe de conexão com banco de dados
 * Implementação real da classe DatabaseConnection
 */
class DatabaseConnection {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                die("Erro de conexão: " . $e->getMessage());
            } else {
                die("Erro interno do sistema. Tente novamente em alguns minutos.");
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Executa uma query SELECT e retorna os resultados
     */
    public function select($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro SQL SELECT: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Insere dados em uma tabela
     */
    public function insert($table, $data) {
        try {
            $fields = array_keys($data);
            $values = array_values($data);
            $placeholders = str_repeat('?,', count($fields) - 1) . '?';
            
            $sql = "INSERT INTO {$table} (`" . implode('`, `', $fields) . "`) VALUES ({$placeholders})";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($values);
            
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log("Erro SQL INSERT: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza dados em uma tabela
     */
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $fields = array_keys($data);
            $values = array_values($data);
            
            $setClause = implode(' = ?, ', $fields) . ' = ?';
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
            
            $params = array_merge($values, $whereParams);
            $stmt = $this->connection->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Erro SQL UPDATE: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Deleta dados de uma tabela
     */
    public function delete($table, $where, $whereParams = []) {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($whereParams);
        } catch (PDOException $e) {
            error_log("Erro SQL DELETE: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Executa uma query genérica
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Erro SQL EXECUTE: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Prepara uma query
     */
    public function prepare($query) {
        return $this->connection->prepare($query);
    }
    
    /**
     * Inicia uma transação
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Confirma uma transação
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Desfaz uma transação
     */
    public function rollBack() {
        return $this->connection->rollBack();
    }
}