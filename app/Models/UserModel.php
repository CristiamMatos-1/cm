<?php
namespace app\Models;

use PDO;
use Exception;

class UserModel extends Model {
    
    /**
     * Busca um usuário específico pelo ID
     */
    public function getUserById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Atualiza os dados de um usuário (Admin/Funcionário)
     */
    public function updateUsuario($id, $data) {
        try {
            $sql = "
                UPDATE users SET 
                    nome = :nome, 
                    email = :email, 
                    telefone = :telefone,
                    cep = :cep,
                    logradouro = :logradouro,
                    numero = :numero,
                    complemento = :complemento,
                    bairro = :bairro,
                    cidade = :cidade,
                    estado = :estado,
                    perfil = :perfil,
                    permissoes = :permissoes
            ";
            
            if (!empty($data['senha_hash'])) {
                $sql .= ", senha = :senha";
            }
            
            $sql .= " WHERE id = :id AND perfil != 'cliente'";

            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':nome', $data['nome']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':telefone', $data['telefone']);
            $stmt->bindParam(':cep', $data['cep']);
            $stmt->bindParam(':logradouro', $data['logradouro']);
            $stmt->bindParam(':numero', $data['numero']);
            $stmt->bindParam(':complemento', $data['complemento']);
            $stmt->bindParam(':bairro', $data['bairro']);
            $stmt->bindParam(':cidade', $data['cidade']);
            $stmt->bindParam(':estado', $data['estado']);
            $stmt->bindParam(':perfil', $data['perfil']);
            $stmt->bindParam(':permissoes', $data['permissoes']);
            $stmt->bindParam(':id', $id);
            
            if (!empty($data['senha_hash'])) {
                $stmt->bindParam(':senha', $data['senha_hash']);
            }
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    public function updateClient($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users SET 
                    nome = :nome, 
                    email = :email, 
                    telefone = :telefone,
                    cep = :cep,
                    logradouro = :logradouro,
                    numero = :numero,
                    complemento = :complemento,
                    bairro = :bairro,
                    cidade = :cidade,
                    estado = :estado,
                    responsavel_nome = :responsavel_nome
                WHERE id = :id AND perfil = 'cliente'
            ");
            
            $stmt->bindParam(':nome', $data['nome']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':telefone', $data['telefone']);
            $stmt->bindParam(':cep', $data['cep']);
            $stmt->bindParam(':logradouro', $data['logradouro']);
            $stmt->bindParam(':numero', $data['numero']);
            $stmt->bindParam(':complemento', $data['complemento']);
            $stmt->bindParam(':bairro', $data['bairro']);
            $stmt->bindParam(':cidade', $data['cidade']);
            $stmt->bindParam(':estado', $data['estado']);
            $stmt->bindParam(':responsavel_nome', $data['responsavel_nome']);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    public function getUserByCpfCnpj($cpf_cnpj) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE cpf_cnpj = :cpf_cnpj LIMIT 1");
        $stmt->bindParam(':cpf_cnpj', $cpf_cnpj);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Busca um usuário pelo Email (usado para evitar duplicidade)
     */
    public function getUserByEmail($email) {
        if (empty($email)) return false;
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Busca todos os usuários do sistema, EXCETO clientes (Apenas Admin e Tecnicos para a tela de Permissões)
     */
    public function getAllUsers() {
        $stmt = $this->db->query("SELECT id, nome, cpf_cnpj, email, telefone, perfil, permissoes FROM users WHERE perfil != 'cliente' ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    /**
     * Busca todos os usuários cadastrados (Geral - Admin)
     */
    public function getAllUsersAdminList() {
        $stmt = $this->db->query("SELECT id, nome, cpf_cnpj, email, telefone, perfil, permissoes FROM users ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    /**
     * Busca todos os clientes
     */
    public function getAllClients() {
        $stmt = $this->db->query("SELECT id, nome, cpf_cnpj, email FROM users WHERE perfil = 'cliente' ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    /**
     * Cria um novo usuário (Cliente)
     */
    public function createUser($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (nome, cpf_cnpj, email, telefone, senha, perfil) 
                VALUES (:nome, :cpf_cnpj, :email, :telefone, :senha, 'cliente')
            ");
            
            $stmt->bindParam(':nome', $data['nome']);
            $stmt->bindParam(':cpf_cnpj', $data['cpf_cnpj']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':telefone', $data['telefone']);
            $stmt->bindParam(':senha', $data['senha_hash']);
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Cria um novo usuário diretamente pelo Admin
     */
    public function createUserByAdmin($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (nome, cpf_cnpj, email, telefone, senha, perfil, permissoes) 
                VALUES (:nome, :cpf_cnpj, :email, :telefone, :senha, :perfil, :permissoes)
            ");
            
            $stmt->bindParam(':nome', $data['nome']);
            $stmt->bindParam(':cpf_cnpj', $data['cpf_cnpj']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':telefone', $data['telefone']);
            $stmt->bindParam(':senha', $data['senha_hash']);
            $stmt->bindParam(':perfil', $data['perfil']);
            $stmt->bindParam(':permissoes', $data['permissoes']);
            
            if (!$stmt->execute()) {
                $error = $stmt->errorInfo();
                die("Erro no SQL: " . print_r($error, true));
            }
            return true;
        } catch (\PDOException $e) {
            die("Erro PDO: " . $e->getMessage());
        } catch (Exception $e) {
            die("Erro Genérico: " . $e->getMessage());
        }
    }

    /**
     * Atualiza as permissões granulares de um usuário
     */
    public function updateUserPermissions($id, $permissoes) {
        $stmt = $this->db->prepare("UPDATE users SET permissoes = :permissoes WHERE id = :id");
        $stmt->bindParam(':permissoes', $permissoes);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Exclui um usuário
     */
    public function deleteUser($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}