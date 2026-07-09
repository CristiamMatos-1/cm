<?php

namespace app\Config;

use PDO;
use PDOException;

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    public $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'coninfom_itsm';
        $this->username = getenv('DB_USER') ?: 'root';
        $dbPassword = getenv('DB_PASS');
        $this->password = $dbPassword === false ? '' : $dbPassword;
        $this->charset = getenv('DB_CHARSET') ?: 'utf8mb4';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
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
