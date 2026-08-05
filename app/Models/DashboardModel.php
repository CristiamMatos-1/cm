<?php
namespace app\Models;

class DashboardModel extends Model {

    public function getTicketStats() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'aberto' THEN 1 ELSE 0 END) as aberto,
                SUM(CASE WHEN status = 'andamento' THEN 1 ELSE 0 END) as andamento,
                SUM(CASE WHEN status = 'em_analise' THEN 1 ELSE 0 END) as em_analise,
                SUM(CASE WHEN status = 'em_execucao' THEN 1 ELSE 0 END) as em_execucao,
                SUM(CASE WHEN status = 'esperando_peca' THEN 1 ELSE 0 END) as esperando_peca,
                SUM(CASE WHEN status = 'finalizado' THEN 1 ELSE 0 END) as finalizado,
                SUM(CASE WHEN status = 'rejeitado' THEN 1 ELSE 0 END) as rejeitado
            FROM tickets
        ");
        return $stmt->fetch();
    }

    public function getBudgetStats() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendente,
                SUM(CASE WHEN status = 'aprovado' THEN 1 ELSE 0 END) as aprovado,
                SUM(CASE WHEN status = 'rejeitado' THEN 1 ELSE 0 END) as rejeitado,
                SUM(CASE WHEN status = 'expirado' THEN 1 ELSE 0 END) as expirado,
                SUM(CASE WHEN status = 'aprovado' THEN valor_total ELSE 0 END) as valor_total_aprovado,
                SUM(valor_total) as valor_total
            FROM budgets
        ");
        return $stmt->fetch();
    }

    public function getAvulsoServiceStats() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendente,
                SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluido,
                SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelado,
                SUM(valor) as valor_total,
                SUM(CASE WHEN status = 'concluido' THEN valor ELSE 0 END) as valor_concluido
            FROM avulso_services
        ");
        return $stmt->fetch();
    }

    public function getClientStats() {
        $stmt = $this->db->query("
            SELECT COUNT(*) as total FROM users WHERE perfil = 'cliente'
        ");
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    public function getContractStats() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(valor_mensal) as valor_total_mensal
            FROM contracts
        ");
        return $stmt->fetch();
    }

    public function getInvoiceStats() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(valor) as valor_total
            FROM invoices
            WHERE MONTH(data_emissao) = MONTH(CURDATE()) AND YEAR(data_emissao) = YEAR(CURDATE())
        ");
        return $stmt->fetch();
    }

    public function getFinancialSummary() {
        $stmt = $this->db->query("
            SELECT 
                COALESCE(SUM(CASE WHEN b.status = 'aprovado' THEN b.valor_total ELSE 0 END), 0) as budgets_aprovados,
                COALESCE((SELECT SUM(valor) FROM invoices), 0) as notas_fiscais_total,
                COALESCE((SELECT SUM(valor) FROM avulso_services WHERE status = 'concluido'), 0) as servicos_avulsos_concluidos,
                COALESCE((SELECT SUM(valor_mensal) FROM contracts), 0) as contratos_mensais
            FROM budgets b
        ");
        return $stmt->fetch();
    }

    public function getTicketsByClientChart() {
        $stmt = $this->db->query("
            SELECT u.nome, COUNT(t.id) as quantidade
            FROM users u
            LEFT JOIN tickets t ON u.id = t.cliente_id
            WHERE u.perfil = 'cliente'
            GROUP BY u.id, u.nome
            ORDER BY quantidade DESC
            LIMIT 10
        ");
        return $stmt->fetchAll();
    }

    public function getBudgetsByStatusChart() {
        $stmt = $this->db->query("
            SELECT status, COUNT(*) as quantidade, SUM(valor_total) as valor_total
            FROM budgets
            GROUP BY status
        ");
        return $stmt->fetchAll();
    }

    public function getMonthlyRevenueChart($months = 12) {
        $stmt = $this->db->query("
            SELECT 
                DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL ROW_NUMBER() OVER () - 1 MONTH), '%Y-%m') as mes,
                COALESCE(SUM(b.valor_total), 0) as budgets,
                COALESCE((SELECT SUM(valor) FROM invoices i WHERE DATE_FORMAT(i.data_emissao, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL ROW_NUMBER() OVER () - 1 MONTH), '%Y-%m')), 0) as notas_fiscais,
                COALESCE((SELECT SUM(valor) FROM avulso_services s WHERE DATE_FORMAT(s.data_servico, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL ROW_NUMBER() OVER () - 1 MONTH), '%Y-%m') AND status = 'concluido'), 0) as servicos
            FROM (
                SELECT ROW_NUMBER() OVER () as num 
                FROM budgets b
                LIMIT $months
            ) nums
            LEFT JOIN budgets b ON DATE_FORMAT(b.created_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL ROW_NUMBER() OVER () - 1 MONTH), '%Y-%m') AND b.status = 'aprovado'
            GROUP BY mes
            ORDER BY mes DESC
        ");
        return $stmt->fetchAll();
    }

    public function getRecentTickets($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT t.*, u.nome as cliente_nome
            FROM tickets t
            JOIN users u ON t.cliente_id = u.id
            ORDER BY t.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRecentBudgets($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT b.*, u.nome as cliente_nome
            FROM budgets b
            JOIN users u ON b.cliente_id = u.id
            ORDER BY b.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPendingApprovals($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT b.*, u.nome as cliente_nome
            FROM budgets b
            JOIN users u ON b.cliente_id = u.id
            WHERE b.status = 'pendente' AND (b.data_validade IS NULL OR b.data_validade >= CURDATE())
            ORDER BY b.data_validade ASC, b.created_at ASC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
