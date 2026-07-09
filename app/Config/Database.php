<?php

namespace app\Config;

use PDO;
use PDOException;

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host = $this->env('DB_HOST', 'localhost');
        $this->port = $this->env('DB_PORT', '3306');
        $this->db_name = $this->env('DB_NAME', '');
        $this->username = $this->env('DB_USER', '');
        $this->password = $this->env('DB_PASSWORD', '');
    }

    public function getConnection() {
        $this->conn = null;

        try {
            if ($this->db_name === '' || $this->username === '') {
                throw new PDOException('Credenciais não configuradas. Defina DB_NAME e DB_USER no ambiente.');
            }

            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
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

    private function env($key, $default = '') {
        $value = getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        return trim($value);
    }
}
