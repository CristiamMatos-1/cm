<?php
namespace app\Models;

class OrcamentoModel extends Model {

    public function getAllBudgets() {
        $stmt = $this->db->query("
            SELECT b.*, u.nome as cliente_nome, u.telefone as cliente_telefone, t.tipo_servico 
            FROM budgets b
            JOIN users u ON b.cliente_id = u.id
            LEFT JOIN tickets t ON b.ticket_id = t.id
            ORDER BY b.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function getBudgetsByClient($cliente_id) {
        $stmt = $this->db->prepare("
            SELECT b.*, t.tipo_servico 
            FROM budgets b
            LEFT JOIN tickets t ON b.ticket_id = t.id
            WHERE b.cliente_id = :cliente_id 
            ORDER BY b.created_at DESC
        ");
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getBudgetById($id) {
        $stmt = $this->db->prepare("
            SELECT b.*, u.nome as cliente_nome, u.telefone as cliente_telefone, t.tipo_servico 
            FROM budgets b
            JOIN users u ON b.cliente_id = u.id
            LEFT JOIN tickets t ON b.ticket_id = t.id
            WHERE b.id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function createBudget($data) {
        $stmt = $this->db->prepare("
            INSERT INTO budgets (cliente_id, ticket_id, titulo, descricao, valor, valor_pecas, valor_mao_obra, status) 
            VALUES (:cliente_id, :ticket_id, :titulo, :descricao, :valor, :valor_pecas, :valor_mao_obra, 'pendente')
        ");
        
        $stmt->bindParam(':cliente_id', $data['cliente_id']);
        $stmt->bindParam(':ticket_id', $data['ticket_id']);
        $stmt->bindParam(':titulo', $data['titulo']);
        $stmt->bindParam(':descricao', $data['descricao']);
        $stmt->bindParam(':valor', $data['valor']);
        $stmt->bindParam(':valor_pecas', $data['valor_pecas']);
        $stmt->bindParam(':valor_mao_obra', $data['valor_mao_obra']);
        
        return $stmt->execute();
    }

    public function updateBudget($id, $data) {
        $sql = "
            UPDATE budgets SET 
                titulo = :titulo,
                descricao = :descricao,
                valor = :valor,
                valor_pecas = :valor_pecas,
                valor_mao_obra = :valor_mao_obra,
                status = :status,
                ticket_id = :ticket_id,
                autorizado_por = :autorizado_por
        ";

        if (isset($data['data_autorizacao'])) {
            $sql .= ", data_autorizacao = :data_autorizacao";
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':titulo', $data['titulo']);
        $stmt->bindParam(':descricao', $data['descricao']);
        $stmt->bindParam(':valor', $data['valor']);
        $stmt->bindParam(':valor_pecas', $data['valor_pecas']);
        $stmt->bindParam(':valor_mao_obra', $data['valor_mao_obra']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':ticket_id', $data['ticket_id']);
        $stmt->bindParam(':autorizado_por', $data['autorizado_por']);
        $stmt->bindParam(':id', $id);

        if (isset($data['data_autorizacao'])) {
            $stmt->bindParam(':data_autorizacao', $data['data_autorizacao']);
        }
        
        return $stmt->execute();
    }

    public function deleteBudget($id) {
        $stmt = $this->db->prepare("DELETE FROM budgets WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE budgets SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
