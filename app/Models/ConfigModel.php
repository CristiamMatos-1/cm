<?php
namespace app\Models;

class ConfigModel extends Model {

    // ==========================================
    // FORNECEDORES
    // ==========================================

    public function getAllSuppliers() {
        $stmt = $this->db->query("SELECT * FROM suppliers ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    public function createSupplier($data) {
        $stmt = $this->db->prepare("
            INSERT INTO suppliers (nome, cnpj, telefone, email) 
            VALUES (:nome, :cnpj, :telefone, :email)
        ");
        $stmt->bindParam(':nome', $data['nome']);
        $stmt->bindParam(':cnpj', $data['cnpj']);
        $stmt->bindParam(':telefone', $data['telefone']);
        $stmt->bindParam(':email', $data['email']);
        return $stmt->execute();
    }

    // ==========================================
    // ATIVOS E PATRIMÔNIO
    // ==========================================

    public function getAllAssets() {
        $stmt = $this->db->query("
            SELECT a.*, u.nome as cliente_nome, s.nome as fornecedor_nome 
            FROM assets a
            LEFT JOIN users u ON a.cliente_id = u.id
            LEFT JOIN suppliers s ON a.fornecedor_id = s.id
            ORDER BY a.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function getAssetsByClient($cliente_id) {
        $stmt = $this->db->prepare("
            SELECT a.*, s.nome as fornecedor_nome 
            FROM assets a
            LEFT JOIN suppliers s ON a.fornecedor_id = s.id
            WHERE a.cliente_id = :cliente_id 
            ORDER BY a.created_at DESC
        ");
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createAsset($data) {
        $stmt = $this->db->prepare("
            INSERT INTO assets (cliente_id, fornecedor_id, nome_equipamento, numero_serie, data_compra, garantia_meses) 
            VALUES (:cliente_id, :fornecedor_id, :nome_equipamento, :numero_serie, :data_compra, :garantia_meses)
        ");
        
        $stmt->bindParam(':cliente_id', $data['cliente_id']);
        $stmt->bindParam(':fornecedor_id', $data['fornecedor_id']);
        $stmt->bindParam(':nome_equipamento', $data['nome_equipamento']);
        $stmt->bindParam(':numero_serie', $data['numero_serie']);
        $stmt->bindParam(':data_compra', $data['data_compra']);
        $stmt->bindParam(':garantia_meses', $data['garantia_meses']);
        
        return $stmt->execute();
    }

    // ==========================================
    // EMPRESA (DADOS CORPORATIVOS)
    // ==========================================

    public function getCompanyInfo() {
        $stmt = $this->db->query("SELECT * FROM companies LIMIT 1");
        return $stmt->fetch();
    }

    public function getAllCompanies() {
        return $this->db->query("SELECT * FROM companies ORDER BY id ASC")->fetchAll();
    }

    public function updateCompany($data) {
        $existing = $this->getCompanyInfo();
        $logo = $data['logo_url'] ?? null;

        if ($existing) {
            $sql = "UPDATE companies SET razao_social = :razao_social, cnpj = :cnpj, matriz_filial = :matriz_filial";
            if ($logo) {
                $sql .= ", logo_url = :logo_url";
            }
            $sql .= " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', (int)$existing['id'], \PDO::PARAM_INT);
        } else {
            $stmt = $this->db->prepare($logo
                ? "INSERT INTO companies (razao_social, cnpj, matriz_filial, logo_url) VALUES (:razao_social, :cnpj, :matriz_filial, :logo_url)"
                : "INSERT INTO companies (razao_social, cnpj, matriz_filial) VALUES (:razao_social, :cnpj, :matriz_filial)");
        }

        $stmt->bindValue(':razao_social', $data['razao_social']);
        $stmt->bindValue(':cnpj', $data['cnpj']);
        $stmt->bindValue(':matriz_filial', $data['matriz_filial']);
        if ($logo) {
            $stmt->bindValue(':logo_url', $logo);
        }

        return $stmt->execute();
    }
}
