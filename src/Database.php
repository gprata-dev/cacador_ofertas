<?php

class Database {
    private PDO $pdo;

    /**
     * Creates a database connection using the provided configuration.
     * 
     * @param array $config Database connection settings.
     */
    public function __construct(array $config)
    {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $config['user'], $config['pass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (\PDOException $e) {
            die("Erro de conexão com o banco: " . $e->getMessage());
        }
    }

    /**
     * @return PDO
     */
    public function getConnection() {
        return $this->pdo;
    }
}