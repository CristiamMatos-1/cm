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
}
