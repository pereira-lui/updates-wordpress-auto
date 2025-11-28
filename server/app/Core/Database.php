<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Classe de conexão com o banco de dados
 */
class Database {
    
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $config = config('database');
        
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );
        
        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                die('Erro de conexão: ' . $e->getMessage());
            }
            die('Erro de conexão com o banco de dados');
        }
    }
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function getInstance() {
        return self::init();
    }
    
    public static function pdo() {
        return self::getInstance()->pdo;
    }
    
    public static function query($sql, $params = []) {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public static function select($sql, $params = []) {
        return self::query($sql, $params)->fetchAll();
    }
    
    public static function selectOne($sql, $params = []) {
        return self::query($sql, $params)->fetch();
    }
    
    public static function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        self::query($sql, array_values($data));
        
        return self::pdo()->lastInsertId();
    }
    
    public static function update($table, $data, $where, $whereParams = []) {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);
        
        return self::query($sql, $params)->rowCount();
    }
    
    public static function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return self::query($sql, $params)->rowCount();
    }
}
