<?php
namespace app\Models;

use PDO;
use Exception;

class ClienteModel extends Model {
    
    /**
     * Busca um cliente pelo ID
     */
    public function getClienteById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id AND perfil = 'cliente' LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Lista todos os clientes
     */
    public function getAllClientes() {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE perfil = 'cliente' ORDER BY nome ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Atualiza dados do cliente (inclui anotações)
     */
    public function updateCliente($id, $data) {
        try {
            $sql = "
                UPDATE users SET 
                    nome = :nome,
                    email = :email,
                    telefone = :telefone,
                    responsavel_nome = :responsavel_nome,
                    cep = :cep,
                    logradouro = :logradouro,
                    numero = :numero,
                    complemento = :complemento,
                    bairro = :bairro,
                    cidade = :cidade,
                    estado = :estado,
                    anotacoes_visivel = :anotacoes_visivel,
                    anotacoes_interna = :anotacoes_interna
                WHERE id = :id AND perfil = 'cliente'
            ";

            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':nome' => $data['nome'] ?? null,
                ':email' => $data['email'] ?? null,
                ':telefone' => $data['telefone'] ?? null,
                ':responsavel_nome' => $data['responsavel_nome'] ?? null,
                ':cep' => $data['cep'] ?? null,
                ':logradouro' => $data['logradouro'] ?? null,
                ':numero' => $data['numero'] ?? null,
                ':complemento' => $data['complemento'] ?? null,
                ':bairro' => $data['bairro'] ?? null,
                ':cidade' => $data['cidade'] ?? null,
                ':estado' => $data['estado'] ?? null,
                ':anotacoes_visivel' => $data['anotacoes_visivel'] ?? null,
                ':anotacoes_interna' => $data['anotacoes_interna'] ?? null,
                ':id' => $id
            ]);
        } catch (Exception $e) {
            error_log("Erro ao atualizar cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca contratos do cliente
     */
    public function getContractsByCliente($cliente_id) {
        $stmt = $this->db->prepare("
            SELECT c.* FROM contracts c 
            WHERE c.cliente_id = :cliente_id 
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([':cliente_id' => $cliente_id]);
        return $stmt->fetchAll();
    }

    /**
     * Busca notas fiscais/documentos do cliente
     */
    public function getNotesByCliente($cliente_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM notas 
                WHERE cliente_id = :cliente_id 
                ORDER BY created_at DESC
                LIMIT 100
            ");
            $stmt->execute([':cliente_id' => $cliente_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Busca orçamentos do cliente
     */
    public function getBudgetsByCliente($cliente_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM budgets 
                WHERE cliente_id = :cliente_id 
                ORDER BY created_at DESC
                LIMIT 50
            ");
            $stmt->execute([':cliente_id' => $cliente_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Busca chamados do cliente
     */
    public function getTicketsByCliente($cliente_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM chamados 
            WHERE cliente_id = :cliente_id 
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute([':cliente_id' => $cliente_id]);
        return $stmt->fetchAll();
    }

    /**
     * Busca serviços avulsos do cliente
     */
    public function getServicesByCliente($cliente_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM servicos_avulsos 
            WHERE cliente_id = :cliente_id 
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute([':cliente_id' => $cliente_id]);
        return $stmt->fetchAll();
    }

    /**
     * Atualiza apenas as anotações visíveis do cliente
     */
    public function updateAnotacoesVisivel($cliente_id, $anotacoes) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users SET 
                    anotacoes_visivel = :anotacoes
                WHERE id = :id AND perfil = 'cliente'
            ");
            return $stmt->execute([
                ':anotacoes' => $anotacoes,
                ':id' => $cliente_id
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Atualiza apenas as anotações internas
     */
    public function updateAnotacoesInterna($cliente_id, $anotacoes) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users SET 
                    anotacoes_interna = :anotacoes
                WHERE id = :id AND perfil = 'cliente'
            ");
            return $stmt->execute([
                ':anotacoes' => $anotacoes,
                ':id' => $cliente_id
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
}
