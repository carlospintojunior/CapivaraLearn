<?php
require_once __DIR__ . '/DatabaseConnection.php';

use CapivaraLearn\DatabaseConnection;

/**
 * Classe Database - Wrapper para DatabaseConnection
 * Mantém compatibilidade com código existente
 */
class Database {
    private $connection;
    private static $instance = null;

    private function __construct() {
        $this->connection = DatabaseConnection::getInstance();
    }

    /**
     * Implementação do padrão Singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Executa uma query SELECT e retorna os resultados
     */
    public function select($query, $params = []) {
        return $this->connection->select($query, $params);
    }

    /**
     * Insere dados em uma tabela
     */
    public function insert($table, $data) {
        return $this->connection->insert($table, $data);
    }

    /**
     * Atualiza dados em uma tabela
     */
    public function update($table, $data, $where, $whereParams = []) {
        return $this->connection->update($table, $data, $where, $whereParams);
    }

    /**
     * Deleta dados de uma tabela
     */
    public function delete($table, $where, $whereParams = []) {
        return $this->connection->delete($table, $where, $whereParams);
    }

    /**
     * Executa uma query genérica
     */
    public function execute($query, $params = []) {
        return $this->connection->execute($query, $params);
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