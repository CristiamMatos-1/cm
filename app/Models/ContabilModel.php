<?php
namespace app\Models;

class ContabilModel extends Model {

    public function getBalançoGeral() {
        $stmt = $this->db->query("
            SELECT 
                SUM(CASE WHEN tipo = 'receita' AND status = 'pago' THEN valor ELSE 0 END) as total_receitas,
                SUM(CASE WHEN tipo = 'despesa' AND status = 'pago' THEN valor ELSE 0 END) as total_despesas
            FROM financeiro_contabil
        ");
        $result = $stmt->fetch();
        
        $receitas = $result['total_receitas'] ?? 0;
        $despesas = $result['total_despesas'] ?? 0;
        $saldo = $receitas - $despesas;

        return [
            'receitas' => $receitas,
            'despesas' => $despesas,
            'saldo' => $saldo
        ];
    }

    public function getAllLancamentos() {
        $stmt = $this->db->query("
            SELECT f.*, u.nome as cliente_nome 
            FROM financeiro_contabil f
            LEFT JOIN users u ON f.cliente_fornecedor_id = u.id
            ORDER BY f.data_vencimento DESC
        ");
        return $stmt->fetchAll();
    }

    public function criarLancamento($data) {
        $stmt = $this->db->prepare("
            INSERT INTO financeiro_contabil (tipo, descricao, valor, data_vencimento, data_pagamento, status, cliente_fornecedor_id, ticket_id) 
            VALUES (:tipo, :descricao, :valor, :data_vencimento, :data_pagamento, :status, :cliente_fornecedor_id, :ticket_id)
        ");
        
        $stmt->bindParam(':tipo', $data['tipo']);
        $stmt->bindParam(':descricao', $data['descricao']);
        $stmt->bindParam(':valor', $data['valor']);
        $stmt->bindParam(':data_vencimento', $data['data_vencimento']);
        $stmt->bindParam(':data_pagamento', $data['data_pagamento']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':cliente_fornecedor_id', $data['cliente_fornecedor_id']);
        $stmt->bindParam(':ticket_id', $data['ticket_id']);
        
        return $stmt->execute();
    }

    public function atualizarStatus($id, $status, $data_pagamento) {
        $stmt = $this->db->prepare("UPDATE financeiro_contabil SET status = :status, data_pagamento = :data_pagamento WHERE id = :id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':data_pagamento', $data_pagamento);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
