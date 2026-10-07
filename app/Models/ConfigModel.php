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

        $columns = [
            'razao_social' => $data['razao_social'],
            'cnpj' => $data['cnpj'],
            'matriz_filial' => $data['matriz_filial'],
        ];
        if (!empty($data['logo_url'])) {
            $columns['logo_url'] = $data['logo_url'];
        }
        foreach (['encarregado_nome', 'email_privacidade'] as $optional) {
            if (array_key_exists($optional, $data) && $this->columnExists('companies', $optional)) {
                $columns[$optional] = $data[$optional] !== '' ? $data[$optional] : null;
            }
        }

        if ($existing) {
            $sets = [];
            foreach (array_keys($columns) as $name) {
                $sets[] = "`$name` = :$name";
            }
            $stmt = $this->db->prepare("UPDATE companies SET " . implode(', ', $sets) . " WHERE id = :id");
            $stmt->bindValue(':id', (int)$existing['id'], \PDO::PARAM_INT);
        } else {
            $names = array_keys($columns);
            $stmt = $this->db->prepare(
                "INSERT INTO companies (`" . implode('`, `', $names) . "`) VALUES (:" . implode(', :', $names) . ")"
            );
        }

        foreach ($columns as $name => $value) {
            $stmt->bindValue(':' . $name, $value);
        }

        return $stmt->execute();
    }
}
