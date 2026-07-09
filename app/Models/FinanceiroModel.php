<?php
namespace app\Models;

class FinanceiroModel extends Model {
    
    // ==========================================
    // CONTRATOS
    // ==========================================
    
    public function getAllContratos() {
        $stmt = $this->db->query("
            SELECT c.*, u.nome as cliente_nome, u.telefone as cliente_telefone 
            FROM contracts c 
            JOIN users u ON c.cliente_id = u.id 
            ORDER BY c.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function getContratosByCliente($cliente_id) {
        $stmt = $this->db->prepare("SELECT * FROM contracts WHERE cliente_id = :cliente_id ORDER BY created_at DESC");
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getContratoById($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.nome as cliente_nome 
            FROM contracts c 
            JOIN users u ON c.cliente_id = u.id 
            WHERE c.id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function createContrato($data) {
        $stmt = $this->db->prepare("
            INSERT INTO contracts (cliente_id, valor_mensal, data_inicio, data_validade, prazo_renovacao_anos, conteudo_sla) 
            VALUES (:cliente_id, :valor_mensal, :data_inicio, :data_validade, :prazo_renovacao_anos, :conteudo_sla)
        ");
        
        $stmt->bindParam(':cliente_id', $data['cliente_id']);
        $stmt->bindParam(':valor_mensal', $data['valor_mensal']);
        $stmt->bindParam(':data_inicio', $data['data_inicio']);
        $stmt->bindParam(':data_validade', $data['data_validade']);
        $stmt->bindParam(':prazo_renovacao_anos', $data['prazo_renovacao_anos']);
        $stmt->bindParam(':conteudo_sla', $data['conteudo_sla']);
        
        return $stmt->execute();
    }

    public function updateContrato($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE contracts SET 
                valor_mensal = :valor_mensal,
                data_inicio = :data_inicio,
                data_validade = :data_validade,
                prazo_renovacao_anos = :prazo_renovacao_anos,
                conteudo_sla = :conteudo_sla,
                status = :status
            WHERE id = :id
        ");
        
        $stmt->bindParam(':valor_mensal', $data['valor_mensal']);
        $stmt->bindParam(':data_inicio', $data['data_inicio']);
        $stmt->bindParam(':data_validade', $data['data_validade']);
        $stmt->bindParam(':prazo_renovacao_anos', $data['prazo_renovacao_anos']);
        $stmt->bindParam(':conteudo_sla', $data['conteudo_sla']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    // ==========================================
    // NOTAS FISCAIS
    // ==========================================

    public function getAllNotas() {
        $stmt = $this->db->query("
            SELECT i.*, u.nome as cliente_nome 
            FROM invoices i 
            JOIN users u ON i.cliente_id = u.id 
            ORDER BY i.data_emissao DESC
        ");
        return $stmt->fetchAll();
    }

    public function getNotasByCliente($cliente_id) {
        $stmt = $this->db->prepare("SELECT * FROM invoices WHERE cliente_id = :cliente_id ORDER BY data_emissao DESC");
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getNotaById($id) {
        $stmt = $this->db->prepare("
            SELECT i.*, u.nome as cliente_nome 
            FROM invoices i 
            JOIN users u ON i.cliente_id = u.id 
            WHERE i.id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function createNotaFiscal($data) {
        $stmt = $this->db->prepare("
            INSERT INTO invoices (cliente_id, contrato_id, numero_nf, valor, data_emissao, arquivo_url) 
            VALUES (:cliente_id, :contrato_id, :numero_nf, :valor, :data_emissao, :arquivo_url)
        ");
        
        $stmt->bindParam(':cliente_id', $data['cliente_id']);
        $stmt->bindParam(':contrato_id', $data['contrato_id']);
        $stmt->bindParam(':numero_nf', $data['numero_nf']);
        $stmt->bindParam(':valor', $data['valor']);
        $stmt->bindParam(':data_emissao', $data['data_emissao']);
        $stmt->bindParam(':arquivo_url', $data['arquivo_url']);
        
        return $stmt->execute();
    }

    public function updateNotaFiscal($id, $data) {
        $sql = "UPDATE invoices SET numero_nf = :numero_nf, valor = :valor, data_emissao = :data_emissao";
        if (isset($data['arquivo_url'])) {
            $sql .= ", arquivo_url = :arquivo_url";
        }
        $sql .= " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':numero_nf', $data['numero_nf']);
        $stmt->bindParam(':valor', $data['valor']);
        $stmt->bindParam(':data_emissao', $data['data_emissao']);
        if (isset($data['arquivo_url'])) {
            $stmt->bindParam(':arquivo_url', $data['arquivo_url']);
        }
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}
