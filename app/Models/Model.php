<?php
namespace app\Models;

use app\Config\Database;
use PDO;

abstract class Model {
    protected $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Verifica (com cache por requisição) se uma coluna existe. Permite que o código
     * continue funcionando enquanto a migração do banco ainda não foi aplicada.
     */
    protected function columnExists($table, $column) {
        static $cache = [];
        $key = $table . '.' . $column;
        if (!array_key_exists($key, $cache)) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c
            ");
            $stmt->execute([':t' => $table, ':c' => $column]);
            $cache[$key] = (int)$stmt->fetchColumn() > 0;
        }
        return $cache[$key];
    }
}
