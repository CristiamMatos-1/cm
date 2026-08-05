<?php
namespace app\Models;

class OrcamentoModel extends Model {

    public function getAllBudgets($includeExpired = false) {
        $sql = "
            SELECT b.*, u.nome as cliente_nome, u.telefone as cliente_telefone, u.email as cliente_email
            FROM budgets b
            JOIN users u ON b.cliente_id = u.id
        ";
        
        if (!$includeExpired) {
            $sql .= " WHERE (b.status != 'expirado' OR (b.status = 'expirado' AND b.data_validade > CURDATE()))";
        }
        
        $sql .= " ORDER BY b.created_at DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getBudgetsByClient($cliente_id, $includeExpired = false) {
        $sql = "
            SELECT b.* 
            FROM budgets b
            WHERE b.cliente_id = :cliente_id
        ";
        
        if (!$includeExpired) {
            $sql .= " AND (b.status != 'expirado' OR (b.status = 'expirado' AND b.data_validade > CURDATE()))";
        }
        
        $sql .= " ORDER BY b.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getBudgetById($id) {
        $stmt = $this->db->prepare("
            SELECT b.*, u.nome as cliente_nome, u.telefone as cliente_telefone, u.email as cliente_email
            FROM budgets b
            JOIN users u ON b.cliente_id = u.id
            WHERE b.id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getBudgetByToken($token) {
        $stmt = $this->db->prepare("
            SELECT b.*, u.nome as cliente_nome, u.telefone as cliente_telefone, u.email as cliente_email
            FROM budgets b
            JOIN users u ON b.cliente_id = u.id
            WHERE b.token_autorizacao = :token
        ");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function createBudget($data) {
        $token = bin2hex(random_bytes(32));
        
        $stmt = $this->db->prepare("
            INSERT INTO budgets (cliente_id, ticket_id, titulo, descricao, valor_total, valor_pecas, valor_mao_obra, data_validade, token_autorizacao) 
            VALUES (:cliente_id, :ticket_id, :titulo, :descricao, :valor_total, :valor_pecas, :valor_mao_obra, :data_validade, :token)
        ");

        if ($stmt->execute([
            ':cliente_id' => $data['cliente_id'],
            ':ticket_id' => $data['ticket_id'] ?? null,
            ':titulo' => $data['titulo'],
            ':descricao' => $data['descricao'],
            ':valor_total' => $data['valor_total'] ?? 0,
            ':valor_pecas' => $data['valor_pecas'] ?? null,
            ':valor_mao_obra' => $data['valor_mao_obra'] ?? null,
            ':data_validade' => $data['data_validade'] ?? null,
            ':token' => $token,
        ])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function updateBudget($id, $data) {
        $updates = [
            'titulo = :titulo',
            'descricao = :descricao',
        ];

        $params = [
            ':titulo' => $data['titulo'],
            ':descricao' => $data['descricao'],
        ];

        if (array_key_exists('valor_total', $data) || array_key_exists('valor', $data)) {
            $updates[] = 'valor_total = :valor_total';
            $params[':valor_total'] = $data['valor_total'] ?? $data['valor'] ?? 0;
        }

        if ($this->hasBudgetColumn('valor_pecas') && array_key_exists('valor_pecas', $data)) {
            $updates[] = 'valor_pecas = :valor_pecas';
            $params[':valor_pecas'] = $data['valor_pecas'];
        }

        if ($this->hasBudgetColumn('valor_mao_obra') && array_key_exists('valor_mao_obra', $data)) {
            $updates[] = 'valor_mao_obra = :valor_mao_obra';
            $params[':valor_mao_obra'] = $data['valor_mao_obra'];
        }

        if ($this->hasBudgetColumn('data_validade') && array_key_exists('data_validade', $data)) {
            $updates[] = 'data_validade = :data_validade';
            $params[':data_validade'] = $data['data_validade'];
        }

        if (isset($data['status'])) {
            $updates[] = "status = :status";
            $params[':status'] = $data['status'];
        }

        if (isset($data['autorizado_por'])) {
            $updates[] = "autorizado_por = :autorizado_por";
            $updates[] = "data_autorizacao = NOW()";
            $params[':autorizado_por'] = $data['autorizado_por'];
        }

        $sql = "UPDATE budgets SET " . implode(', ', $updates) . " WHERE id = :id";
        $params[':id'] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function approveBudget($id, $admin_id) {
        $stmt = $this->db->prepare("
            UPDATE budgets SET 
                status = 'aprovado',
                autorizado_por = :admin_id,
                data_autorizacao = NOW()
            WHERE id = :id
        ");
        $stmt->bindParam(':admin_id', $admin_id);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function rejectBudget($id, $admin_id, $motivo = null) {
        $stmt = $this->db->prepare("
            UPDATE budgets SET 
                status = 'rejeitado',
                rejeitado_por = :admin_id,
                data_rejeicao = NOW(),
                motivo_rejeicao = :motivo
            WHERE id = :id
        ");
        $stmt->bindParam(':admin_id', $admin_id);
        $stmt->bindParam(':motivo', $motivo);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function reactivateBudget($id) {
        $stmt = $this->db->prepare("
            UPDATE budgets SET 
                status = 'pendente',
                data_validade = DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            WHERE id = :id AND status = 'expirado'
        ");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE budgets SET status = :status, updated_at = NOW() WHERE id = :id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function deleteBudget($id) {
        $stmt = $this->db->prepare("DELETE FROM budgets WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function checkExpiredBudgets() {
        $stmt = $this->db->prepare("
            UPDATE budgets SET status = 'expirado', updated_at = NOW()
            WHERE status IN ('pendente', 'aprovado') AND data_validade < CURDATE()
        ");
        return $stmt->execute();
    }

    private function hasBudgetColumn($column) {
        static $columns = null;

        if ($columns === null) {
            $stmt = $this->db->query("SHOW COLUMNS FROM budgets");
            $columns = array_map(static function ($row) {
                return $row['Field'];
            }, $stmt->fetchAll());
        }

        return in_array($column, $columns, true);
    }

    // Métodos para itens de orçamento
    public function addBudgetItem($budget_id, $tipo, $descricao, $quantidade, $valor_unitario) {
        $subtotal = $quantidade * $valor_unitario;
        
        $stmt = $this->db->prepare("
            INSERT INTO budget_items (budget_id, tipo, descricao, quantidade, valor_unitario, subtotal)
            VALUES (:budget_id, :tipo, :descricao, :quantidade, :valor_unitario, :subtotal)
        ");
        
        $stmt->bindParam(':budget_id', $budget_id);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':quantidade', $quantidade);
        $stmt->bindParam(':valor_unitario', $valor_unitario);
        $stmt->bindParam(':subtotal', $subtotal);
        
        if ($stmt->execute()) {
            $this->recalculateBudgetTotal($budget_id);
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function getBudgetItems($budget_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM budget_items 
            WHERE budget_id = :budget_id
            ORDER BY tipo, id
        ");
        $stmt->bindParam(':budget_id', $budget_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function deleteBudgetItem($item_id) {
        $stmt = $this->db->prepare("SELECT budget_id FROM budget_items WHERE id = :id");
        $stmt->bindParam(':id', $item_id);
        $stmt->execute();
        $result = $stmt->fetch();
        $budget_id = $result['budget_id'] ?? null;

        $stmt = $this->db->prepare("DELETE FROM budget_items WHERE id = :id");
        $stmt->bindParam(':id', $item_id);
        
        if ($stmt->execute() && $budget_id) {
            $this->recalculateBudgetTotal($budget_id);
            return true;
        }
        return false;
    }

    public function recalculateBudgetTotal($budget_id) {
        $stmt = $this->db->prepare("
            SELECT SUM(CASE WHEN tipo = 'peca' THEN subtotal ELSE 0 END) as valor_pecas,
                   SUM(CASE WHEN tipo = 'mao_obra' THEN subtotal ELSE 0 END) as valor_mao_obra,
                   SUM(subtotal) as valor_total
            FROM budget_items
            WHERE budget_id = :budget_id
        ");
        $stmt->bindParam(':budget_id', $budget_id);
        $stmt->execute();
        $totals = $stmt->fetch();

        $stmt = $this->db->prepare("
            UPDATE budgets SET 
                valor_pecas = :valor_pecas,
                valor_mao_obra = :valor_mao_obra,
                valor_total = :valor_total,
                updated_at = NOW()
            WHERE id = :budget_id
        ");
        $stmt->bindParam(':valor_pecas', $totals['valor_pecas']);
        $stmt->bindParam(':valor_mao_obra', $totals['valor_mao_obra']);
        $stmt->bindParam(':valor_total', $totals['valor_total']);
        $stmt->bindParam(':budget_id', $budget_id);
        return $stmt->execute();
    }
}
