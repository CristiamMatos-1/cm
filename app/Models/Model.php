<?php
namespace app\Models;

use app\Config\Database;
use PDO;

abstract class Model {
    protected $db;

    /**
     * @param PDO|null $db conexão existente (permite vários models participarem da mesma transação);
     *                     quando omitida, abre uma conexão nova.
     */
    public function __construct(?PDO $db = null) {
        if ($db === null) {
            $database = new Database();
            $db = $database->getConnection();
        }
        $this->db = $db;
    }
}
