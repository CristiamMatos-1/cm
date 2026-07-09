<?php
namespace app\Models;

class ReportModel extends Model {

    /**
     * Retorna todos os serviços finalizados com o cálculo exato de horas gastas
     */
    public function getServicosFinalizados() {
        $stmt = $this->db->query("
            SELECT t.*, u.nome as cliente_nome, tec.nome as tecnico_nome,
                   TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at) as tempo_minutos
            FROM tickets t
            JOIN users u ON t.cliente_id = u.id
            LEFT JOIN users tec ON t.tecnico_id = tec.id
            WHERE t.status = 'finalizado' AND t.closed_at IS NOT NULL
            ORDER BY t.closed_at DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Retorna o balanço geral de clientes (Total de chamados, contratos ativos, etc)
     */
    public function getBalancoClientes() {
        $stmt = $this->db->query("
            SELECT u.id, u.nome, u.cpf_cnpj, u.telefone,
                   (SELECT COUNT(*) FROM tickets WHERE cliente_id = u.id) as total_chamados,
                   (SELECT COUNT(*) FROM contracts WHERE cliente_id = u.id) as total_contratos
            FROM users u
            WHERE u.perfil = 'cliente'
            ORDER BY u.nome ASC
        ");
        return $stmt->fetchAll();
    }
}
