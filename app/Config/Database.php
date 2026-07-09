<?php

namespace app\Config;

use PDO;
use PDOException;

class Database {
    private $host = 'localhost';
    private $db_name = 'coninfom_itsm';
    private $username = 'coninfom_admin';
    private $password = 'SenhaConinfoms2026';
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $exception) {
            // Em ambiente de produção, registrar no log ao invés de exibir na tela
            error_log("Erro de conexão: " . $exception->getMessage());
            die("Erro de conexão com o banco de dados. Tente novamente mais tarde.");
        }

        return $this->conn;
    }
}
