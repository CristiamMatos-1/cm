<?php
namespace app\Models;

use PDO;
use Exception;

class ChamadoModel extends Model {
    
    /**
     * Retorna o total de chamados de um cliente específico
     */
    public function countByCliente($cliente_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM tickets WHERE cliente_id = :cliente_id");
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->execute();
        return $stmt->fetch()['total'];
    }

    /**
     * Retorna os últimos chamados de um cliente
     */
    public function getLatestByCliente($cliente_id, $limit = 5) {
        $stmt = $this->db->prepare("SELECT * FROM tickets WHERE cliente_id = :cliente_id ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna o total de chamados no sistema (Para Admin)
     */
    public function countAll() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM tickets");
        return $stmt->fetch()['total'];
    }

    /**
     * Retorna todos os chamados do sistema (Para Admin)
     */
    public function getAllChamados() {
        $stmt = $this->db->query("
            SELECT t.*, u.nome as cliente_nome, tec.nome as tecnico_nome 
            FROM tickets t 
            JOIN users u ON t.cliente_id = u.id
            LEFT JOIN users tec ON t.tecnico_id = tec.id
            ORDER BY t.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Retorna o total de chamados atribuídos a um técnico
     */
    public function countByTecnico($tecnico_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM tickets WHERE tecnico_id = :tecnico_id");
        $stmt->bindParam(':tecnico_id', $tecnico_id);
        $stmt->execute();
        return $stmt->fetch()['total'];
    }

    /**
     * Cria um novo chamado no banco
     */
    public function createTicket($data) {
        $stmt = $this->db->prepare("
            INSERT INTO tickets (cliente_id, tipo_servico, descricao, status) 
            VALUES (:cliente_id, :tipo_servico, :descricao, 'aberto')
        ");
        
        $stmt->bindParam(':cliente_id', $data['cliente_id']);
        $stmt->bindParam(':tipo_servico', $data['tipo_servico']);
        $stmt->bindParam(':descricao', $data['descricao']);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Adiciona uma mídia vinculada ao chamado
     */
    public function addMedia($ticket_id, $user_id, $file_url, $tipo) {
        $stmt = $this->db->prepare("
            INSERT INTO ticket_media (ticket_id, user_id, file_url, tipo) 
            VALUES (:ticket_id, :user_id, :file_url, :tipo)
        ");
        
        $stmt->bindParam(':ticket_id', $ticket_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':file_url', $file_url);
        $stmt->bindParam(':tipo', $tipo);
        
        return $stmt->execute();
    }

    /**
     * Retorna a fila de chamados (Abertos ou Atribuídos ao Técnico logado)
     */
    public function getFilaChamados($tecnico_id) {
        $stmt = $this->db->prepare("
            SELECT t.*, u.nome as cliente_nome 
            FROM tickets t 
            JOIN users u ON t.cliente_id = u.id
            WHERE t.status = 'aberto' OR t.tecnico_id = :tecnico_id 
            ORDER BY t.created_at ASC
        ");
        $stmt->bindParam(':tecnico_id', $tecnico_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Busca os detalhes de um chamado específico
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT t.*, 
                   c.nome as cliente_nome, c.telefone as cliente_telefone, c.email as cliente_email, 
                   tec.nome as tecnico_nome, 
                   prog.nome as programador_nome, 
                   eng.nome as engenheiro_nome
            FROM tickets t 
            JOIN users c ON t.cliente_id = c.id
            LEFT JOIN users tec ON t.tecnico_id = tec.id
            LEFT JOIN users prog ON t.programador_id = prog.id
            LEFT JOIN users eng ON t.engenheiro_id = eng.id
            WHERE t.id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Atualiza dados avançados do chamado pelo Admin (Atribuição, Valores, Pagamento)
     */
    public function atualizarChamadoAdmin($id, $dados) {
        $query = "
            UPDATE tickets SET 
                tecnico_id = :tecnico_id,
                programador_id = :programador_id,
                engenheiro_id = :engenheiro_id,
                status = :status,
                relatorio_final = :relatorio_final,
                valor_pecas = :valor_pecas,
                valor_mao_obra = :valor_mao_obra,
                valor_servico = :valor_servico,
                forma_pagamento = :forma_pagamento,
                autorizado_por = :autorizado_por
        ";

        if ($dados['status'] === 'finalizado') {
            $query .= ", closed_at = NOW() ";
        }
        if (isset($dados['data_autorizacao'])) {
            $query .= ", data_autorizacao = :data_autorizacao ";
        }

        $query .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':tecnico_id', $dados['tecnico_id']);
        $stmt->bindParam(':programador_id', $dados['programador_id']);
        $stmt->bindParam(':engenheiro_id', $dados['engenheiro_id']);
        $stmt->bindParam(':status', $dados['status']);
        $stmt->bindParam(':relatorio_final', $dados['relatorio_final']);
        $stmt->bindParam(':valor_pecas', $dados['valor_pecas']);
        $stmt->bindParam(':valor_mao_obra', $dados['valor_mao_obra']);
        $stmt->bindParam(':valor_servico', $dados['valor_servico']);
        $stmt->bindParam(':forma_pagamento', $dados['forma_pagamento']);
        $stmt->bindParam(':autorizado_por', $dados['autorizado_por']);
        if (isset($dados['data_autorizacao'])) {
            $stmt->bindParam(':data_autorizacao', $dados['data_autorizacao']);
        }
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    /**
     * Exclui um chamado do sistema
     */
    public function deleteTicket($id) {
        $stmt = $this->db->prepare("DELETE FROM tickets WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Busca todas as mídias de um chamado
     */
    public function getMediaByTicket($ticket_id) {
        $stmt = $this->db->prepare("SELECT * FROM ticket_media WHERE ticket_id = :ticket_id ORDER BY created_at ASC");
        $stmt->bindParam(':ticket_id', $ticket_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Atribui um chamado a um técnico e muda status para andamento
     */
    public function assumirChamado($ticket_id, $tecnico_id) {
        $stmt = $this->db->prepare("
            UPDATE tickets SET tecnico_id = :tecnico_id, status = 'andamento' 
            WHERE id = :ticket_id
        ");
        $stmt->bindParam(':ticket_id', $ticket_id);
        $stmt->bindParam(':tecnico_id', $tecnico_id);
        return $stmt->execute();
    }

    /**
     * Atualiza dados da triagem/atendimento do chamado
     */
    public function atualizarTriagem($ticket_id, $atendimento, $status, $relatorio) {
        // Se o status mudar para finalizado, registramos a hora de fechamento
        if ($status === 'finalizado') {
            $stmt = $this->db->prepare("
                UPDATE tickets 
                SET atendimento = :atendimento, status = :status, relatorio_final = :relatorio, closed_at = NOW() 
                WHERE id = :ticket_id
            ");
        } else {
            $stmt = $this->db->prepare("
                UPDATE tickets 
                SET atendimento = :atendimento, status = :status, relatorio_final = :relatorio 
                WHERE id = :ticket_id
            ");
        }
        
        $stmt->bindParam(':ticket_id', $ticket_id);
        $stmt->bindParam(':atendimento', $atendimento);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':relatorio', $relatorio);
        return $stmt->execute();
    }
}
