<?php
namespace app\Models;

class ProjetoModel extends Model {

    public function getAllProjetos() {
        $stmt = $this->db->query("
            SELECT p.*, c.nome as cliente_nome, e.nome as engenheiro_nome 
            FROM projetos_software p
            JOIN users c ON p.cliente_id = c.id
            LEFT JOIN users e ON p.engenheiro_id = e.id
            ORDER BY p.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function getProjetosByCliente($cliente_id) {
        $stmt = $this->db->prepare("
            SELECT p.*, e.nome as engenheiro_nome 
            FROM projetos_software p
            LEFT JOIN users e ON p.engenheiro_id = e.id
            WHERE p.cliente_id = :cliente_id
            ORDER BY p.created_at DESC
        ");
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getProjetoById($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, c.nome as cliente_nome, e.nome as engenheiro_nome 
            FROM projetos_software p
            JOIN users c ON p.cliente_id = c.id
            LEFT JOIN users e ON p.engenheiro_id = e.id
            WHERE p.id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function criarProjeto($data) {
        $stmt = $this->db->prepare("
            INSERT INTO projetos_software 
            (cliente_id, engenheiro_id, nome_projeto, descricao, documentacao, link_repositorio, link_producao, status, data_inicio, data_previsao_fim) 
            VALUES 
            (:cliente_id, :engenheiro_id, :nome_projeto, :descricao, :documentacao, :link_repositorio, :link_producao, :status, :data_inicio, :data_previsao_fim)
        ");

        $stmt->bindParam(':cliente_id', $data['cliente_id']);
        $stmt->bindParam(':engenheiro_id', $data['engenheiro_id']);
        $stmt->bindParam(':nome_projeto', $data['nome_projeto']);
        $stmt->bindParam(':descricao', $data['descricao']);
        $stmt->bindParam(':documentacao', $data['documentacao']);
        $stmt->bindParam(':link_repositorio', $data['link_repositorio']);
        $stmt->bindParam(':link_producao', $data['link_producao']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':data_inicio', $data['data_inicio']);
        $stmt->bindParam(':data_previsao_fim', $data['data_previsao_fim']);

        return $stmt->execute();
    }

    public function atualizarProjeto($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE projetos_software SET 
                engenheiro_id = :engenheiro_id,
                nome_projeto = :nome_projeto,
                descricao = :descricao,
                documentacao = :documentacao,
                link_repositorio = :link_repositorio,
                link_producao = :link_producao,
                status = :status,
                data_inicio = :data_inicio,
                data_previsao_fim = :data_previsao_fim
            WHERE id = :id
        ");

        $stmt->bindParam(':engenheiro_id', $data['engenheiro_id']);
        $stmt->bindParam(':nome_projeto', $data['nome_projeto']);
        $stmt->bindParam(':descricao', $data['descricao']);
        $stmt->bindParam(':documentacao', $data['documentacao']);
        $stmt->bindParam(':link_repositorio', $data['link_repositorio']);
        $stmt->bindParam(':link_producao', $data['link_producao']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':data_inicio', $data['data_inicio']);
        $stmt->bindParam(':data_previsao_fim', $data['data_previsao_fim']);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }
}
