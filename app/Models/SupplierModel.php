<?php
namespace app\Models;

use PDO;
use Exception;

class SupplierModel extends Model {

    public function getAll($busca = '') {
        try {
            $sql = "SELECT * FROM suppliers";
            if (!empty($busca)) {
                $sql .= " WHERE razao_social LIKE :busca OR nome_fantasia LIKE :busca OR cnpj LIKE :busca";
            }
            $sql .= " ORDER BY razao_social ASC";
            $stmt = $this->db->prepare($sql);
            if (!empty($busca)) {
                $like = "%{$busca}%";
                $stmt->bindParam(':busca', $like);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = :id LIMIT 1");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    public function getByCnpj($cnpj) {
        try {
            $cnpj = preg_replace('/\D/', '', $cnpj);
            $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE cnpj = :cnpj LIMIT 1");
            $stmt->bindParam(':cnpj', $cnpj);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO suppliers (
                    razao_social, nome_fantasia, cnpj, ie, im,
                    email, whatsapp, telefone,
                    cep, logradouro, numero, complemento, bairro, cidade, uf, codigo_ibge,
                    crt, indicador_ie, cnae_principal,
                    observacoes
                ) VALUES (
                    :razao_social, :nome_fantasia, :cnpj, :ie, :im,
                    :email, :whatsapp, :telefone,
                    :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :uf, :codigo_ibge,
                    :crt, :indicador_ie, :cnae_principal,
                    :observacoes
                )
            ");
            $stmt->execute([
                ':razao_social'   => $data['razao_social'] ?? '',
                ':nome_fantasia'  => $data['nome_fantasia'] ?? '',
                ':cnpj'           => preg_replace('/\D/', '', $data['cnpj'] ?? ''),
                ':ie'             => $data['ie'] ?? '',
                ':im'             => $data['im'] ?? '',
                ':email'          => $data['email'] ?? '',
                ':whatsapp'       => $data['whatsapp'] ?? '',
                ':telefone'       => $data['telefone'] ?? '',
                ':cep'            => $data['cep'] ?? '',
                ':logradouro'     => $data['logradouro'] ?? '',
                ':numero'         => $data['numero'] ?? '',
                ':complemento'    => $data['complemento'] ?? '',
                ':bairro'         => $data['bairro'] ?? '',
                ':cidade'         => $data['cidade'] ?? '',
                ':uf'             => $data['uf'] ?? '',
                ':codigo_ibge'    => $data['codigo_ibge'] ?? '',
                ':crt'            => $data['crt'] ?? '1',
                ':indicador_ie'   => $data['indicador_ie'] ?? '9',
                ':cnae_principal' => $data['cnae_principal'] ?? '',
                ':observacoes'    => $data['observacoes'] ?? '',
            ]);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('SupplierModel::create - ' . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE suppliers SET
                    razao_social   = :razao_social,
                    nome_fantasia  = :nome_fantasia,
                    cnpj           = :cnpj,
                    ie             = :ie,
                    im             = :im,
                    email          = :email,
                    whatsapp       = :whatsapp,
                    telefone       = :telefone,
                    cep            = :cep,
                    logradouro     = :logradouro,
                    numero         = :numero,
                    complemento    = :complemento,
                    bairro         = :bairro,
                    cidade         = :cidade,
                    uf             = :uf,
                    codigo_ibge    = :codigo_ibge,
                    crt            = :crt,
                    indicador_ie   = :indicador_ie,
                    cnae_principal = :cnae_principal,
                    observacoes    = :observacoes
                WHERE id = :id
            ");
            $stmt->execute([
                ':razao_social'   => $data['razao_social'] ?? '',
                ':nome_fantasia'  => $data['nome_fantasia'] ?? '',
                ':cnpj'           => preg_replace('/\D/', '', $data['cnpj'] ?? ''),
                ':ie'             => $data['ie'] ?? '',
                ':im'             => $data['im'] ?? '',
                ':email'          => $data['email'] ?? '',
                ':whatsapp'       => $data['whatsapp'] ?? '',
                ':telefone'       => $data['telefone'] ?? '',
                ':cep'            => $data['cep'] ?? '',
                ':logradouro'     => $data['logradouro'] ?? '',
                ':numero'         => $data['numero'] ?? '',
                ':complemento'    => $data['complemento'] ?? '',
                ':bairro'         => $data['bairro'] ?? '',
                ':cidade'         => $data['cidade'] ?? '',
                ':uf'             => $data['uf'] ?? '',
                ':codigo_ibge'    => $data['codigo_ibge'] ?? '',
                ':crt'            => $data['crt'] ?? '1',
                ':indicador_ie'   => $data['indicador_ie'] ?? '9',
                ':cnae_principal' => $data['cnae_principal'] ?? '',
                ':observacoes'    => $data['observacoes'] ?? '',
                ':id'             => $id,
            ]);
            return true;
        } catch (Exception $e) {
            error_log('SupplierModel::update - ' . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM suppliers WHERE id = :id");
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    public function count() {
        try {
            return $this->db->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
}
