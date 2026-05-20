<?php

class Database {
    private PDO $pdo;

    public function __construct($config)
    {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $config['user'], $config['pass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (\PDOException $e) {
            die("Erro de conexão com o banco: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->pdo;
    }
}