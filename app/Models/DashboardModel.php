<?php
namespace app\Models;

use PDO;
use PDOException;

/**
 * Consultas da Visão Geral do administrador.
 *
 * Regras de performance adotadas (a página inicial roda várias consultas):
 *  - totais por status usam GROUP BY sobre colunas indexadas (ver database/migracao_correcao_schema.sql);
 *  - filtros de data usam intervalos (col >= :ini AND col < :fim), nunca MONTH(col)/DATE(col) no WHERE,
 *    para que o MySQL consiga usar índice;
 *  - listas "recentes" ordenam pela chave primária e têm LIMIT;
 *  - cada bloco é uma única consulta com agregação condicional, evitando N+1.
 */
class DashboardModel extends Model {

    /** Status de chamado que significam "em andamento" para o KPI. */
    private const TICKET_STATUS_ANDAMENTO = ['andamento', 'em_analise', 'em_execucao', 'esperando_peca'];
    /** Status de chamado que significam "encerrado" para o KPI. */
    private const TICKET_STATUS_ENCERRADO = ['finalizado', 'rejeitado'];

    private const MESES_ABREV = [1 => 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    public function checkExpiredBudgets() {
        return (new OrcamentoModel())->checkExpiredBudgets();
    }

    // ------------------------------------------------------------------
    // KPIs
    // ------------------------------------------------------------------

    /**
     * Chamados: total, em aberto, em andamento e encerrados.
     * Uma consulta (varredura do índice idx_tickets_status).
     */
    public function getTicketKpis() {
        $porStatus = $this->db->query("SELECT status, COUNT(*) AS qtd FROM tickets GROUP BY status")
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        $soma = function (array $status) use ($porStatus) {
            $total = 0;
            foreach ($status as $s) {
                $total += (int)($porStatus[$s] ?? 0);
            }
            return $total;
        };

        return [
            'total'      => array_sum(array_map('intval', $porStatus)),
            'aberto'     => (int)($porStatus['aberto'] ?? 0),
            'andamento'  => $soma(self::TICKET_STATUS_ANDAMENTO),
            'encerrado'  => $soma(self::TICKET_STATUS_ENCERRADO),
        ];
    }

    /**
     * Orçamentos: total emitido, pendentes, aceitos, rejeitados (+ expirados e valores).
     * Também alimenta o gráfico de conversão.
     */
    public function getBudgetKpis() {
        $rows = $this->db->query("
            SELECT status, COUNT(*) AS qtd, COALESCE(SUM(valor_total), 0) AS valor
            FROM budgets
            GROUP BY status
        ")->fetchAll();

        $kpi = [
            'total' => 0, 'pendente' => 0, 'aprovado' => 0, 'rejeitado' => 0, 'expirado' => 0,
            'valor_pendente' => 0.0, 'valor_aprovado' => 0.0,
        ];
        foreach ($rows as $row) {
            $kpi['total'] += (int)$row['qtd'];
            if (array_key_exists($row['status'], $kpi)) {
                $kpi[$row['status']] = (int)$row['qtd'];
            }
            if ($row['status'] === 'pendente') {
                $kpi['valor_pendente'] = (float)$row['valor'];
            } elseif ($row['status'] === 'aprovado') {
                $kpi['valor_aprovado'] = (float)$row['valor'];
            }
        }

        $decididos = $kpi['aprovado'] + $kpi['rejeitado'];
        $kpi['taxa_conversao'] = $decididos > 0 ? round($kpi['aprovado'] / $decididos * 100, 1) : null;

        return $kpi;
    }

    /**
     * Financeiro (tabela financeiro_contabil, a mesma usada em "Financeiro Contábil"):
     *  - a receber: pendente vencido (inadimplência) e a vencer;
     *  - a pagar: despesas pendentes vencidas e a vencer;
     *  - saldo geral: receitas pagas - despesas pagas (mesmo cálculo de ContabilModel::getBalançoGeral);
     *  - receitas/despesas do mês corrente (valores efetivamente pagos).
     *
     * @param array $fluxoCaixa resultado de getCashFlow(), usado para não repetir a consulta do mês.
     */
    public function getFinanceKpis(array $fluxoCaixa) {
        $hoje = date('Y-m-d');

        $stmt = $this->db->prepare("
            SELECT tipo,
                   COUNT(*) AS qtd,
                   COALESCE(SUM(CASE WHEN data_vencimento <  :hoje_a THEN valor ELSE 0 END), 0) AS vencido_valor,
                   COALESCE(SUM(CASE WHEN data_vencimento <  :hoje_b THEN 1 ELSE 0 END), 0)     AS vencido_qtd,
                   COALESCE(SUM(CASE WHEN data_vencimento >= :hoje_c THEN valor ELSE 0 END), 0) AS a_vencer_valor,
                   COALESCE(SUM(CASE WHEN data_vencimento >= :hoje_d THEN 1 ELSE 0 END), 0)     AS a_vencer_qtd
            FROM financeiro_contabil
            WHERE status = 'pendente'
            GROUP BY tipo
        ");
        $stmt->execute([':hoje_a' => $hoje, ':hoje_b' => $hoje, ':hoje_c' => $hoje, ':hoje_d' => $hoje]);
        $pendentes = [];
        foreach ($stmt->fetchAll() as $row) {
            $pendentes[$row['tipo']] = $row;
        }

        $pagos = $this->db->query("
            SELECT tipo, COALESCE(SUM(valor), 0) AS total
            FROM financeiro_contabil
            WHERE status = 'pago'
            GROUP BY tipo
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        $bloco = function ($tipo) use ($pendentes) {
            $r = $pendentes[$tipo] ?? [];
            $vencido = (float)($r['vencido_valor'] ?? 0);
            $aVencer = (float)($r['a_vencer_valor'] ?? 0);
            return [
                'total'        => $vencido + $aVencer,
                'vencido'      => $vencido,
                'vencido_qtd'  => (int)($r['vencido_qtd'] ?? 0),
                'a_vencer'     => $aVencer,
                'a_vencer_qtd' => (int)($r['a_vencer_qtd'] ?? 0),
            ];
        };

        // O último bucket do fluxo de caixa é o mês corrente.
        $ultimo = max(0, count($fluxoCaixa['receitas']) - 1);
        $recMes = (float)($fluxoCaixa['receitas'][$ultimo] ?? 0);
        $despMes = (float)($fluxoCaixa['despesas'][$ultimo] ?? 0);
        $receitasPagas = (float)($pagos['receita'] ?? 0);
        $despesasPagas = (float)($pagos['despesa'] ?? 0);

        return [
            'receber'       => $bloco('receita'),
            'pagar'         => $bloco('despesa'),
            'saldo_geral'   => $receitasPagas - $despesasPagas,
            'receita_mes'   => $recMes,
            'despesa_mes'   => $despMes,
            'saldo_mes'     => $recMes - $despMes,
        ];
    }

    /**
     * Administração: clientes, equipe, novos cadastros no período e clientes com atividade no período.
     */
    public function getUserKpis($dias = 30) {
        $desde = date('Y-m-d 00:00:00', strtotime('-' . (int)$dias . ' days'));

        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(perfil = 'cliente'), 0)                                 AS clientes,
                COALESCE(SUM(perfil <> 'cliente'), 0)                                AS equipe,
                COALESCE(SUM(perfil = 'cliente' AND created_at >= :desde_a), 0)     AS novos_clientes,
                COALESCE(SUM(perfil <> 'cliente' AND created_at >= :desde_b), 0)    AS novos_equipe
            FROM users
        ");
        $stmt->execute([':desde_a' => $desde, ':desde_b' => $desde]);
        $row = $stmt->fetch();

        $ativos = $this->db->prepare("SELECT COUNT(DISTINCT cliente_id) FROM tickets WHERE created_at >= :desde");
        $ativos->execute([':desde' => $desde]);

        return [
            'clientes'          => (int)$row['clientes'],
            'equipe'            => (int)$row['equipe'],
            'novos_clientes'    => (int)$row['novos_clientes'],
            'novos_equipe'      => (int)$row['novos_equipe'],
            'clientes_ativos'   => (int)$ativos->fetchColumn(),
            'periodo_dias'      => (int)$dias,
        ];
    }

    /**
     * Saúde do sistema. Uptime do servidor só aparece se o host permitir ler /proc/uptime
     * (comum em hospedagem compartilhada bloquear).
     */
    public function getSystemStatus() {
        $inicio = microtime(true);
        $dbOk = true;
        try {
            $this->db->query('SELECT 1');
        } catch (PDOException $e) {
            $dbOk = false;
        }
        $dbMs = (int)round((microtime(true) - $inicio) * 1000);

        $uptimeSeg = null;
        $raw = @file_get_contents('/proc/uptime');
        if ($raw !== false && preg_match('/^(\d+(?:\.\d+)?)/', $raw, $m)) {
            $uptimeSeg = (int)$m[1];
        }

        $livre = @disk_free_space(APP_PATH);
        $total = @disk_total_space(APP_PATH);
        $discoLivrePct = ($livre !== false && $total) ? round($livre / $total * 100, 1) : null;

        $uploadsOk = is_writable(dirname(APP_PATH) . '/uploads');

        $nivel = 'operacional';
        if (!$dbOk) {
            $nivel = 'critico';
        } elseif (!$uploadsOk || ($discoLivrePct !== null && $discoLivrePct < 10) || $dbMs > 500) {
            $nivel = 'atencao';
        }

        return [
            'nivel'            => $nivel,
            'db_ok'            => $dbOk,
            'db_ms'            => $dbMs,
            'uploads_ok'       => $uploadsOk,
            'disco_livre_pct'  => $discoLivrePct,
            'uptime_segundos'  => $uptimeSeg,
            'php_version'      => PHP_VERSION,
            'memoria_mb'       => round(memory_get_peak_usage(true) / 1048576, 1),
        ];
    }

    // ------------------------------------------------------------------
    // Gráficos
    // ------------------------------------------------------------------

    /**
     * Chamados abertos x encerrados por dia nos últimos $dias dias (inclui hoje).
     * Dias sem movimento são preenchidos com zero para o gráfico não "pular" datas.
     */
    public function getTicketFlow($dias = 30) {
        $dias = max(1, (int)$dias);
        $inicio = date('Y-m-d', strtotime('-' . ($dias - 1) . ' days'));
        $inicioTs = $inicio . ' 00:00:00';

        // Encerramento: closed_at (finalizados) ou, na falta dele (ex.: rejeitados), updated_at.
        // O filtro por updated_at é um pré-filtro seguro (updated_at >= closed_at) que usa idx_tickets_status_updated.
        $stmt = $this->db->prepare("
            SELECT 'abertos' AS tipo, DATE(created_at) AS dia, COUNT(*) AS qtd
            FROM tickets
            WHERE created_at >= :ini_a
            GROUP BY DATE(created_at)
            UNION ALL
            SELECT 'encerrados' AS tipo, DATE(COALESCE(closed_at, updated_at)) AS dia, COUNT(*) AS qtd
            FROM tickets
            WHERE status IN ('finalizado', 'rejeitado')
              AND updated_at >= :ini_b
              AND COALESCE(closed_at, updated_at) >= :ini_c
            GROUP BY DATE(COALESCE(closed_at, updated_at))
        ");
        $stmt->execute([':ini_a' => $inicioTs, ':ini_b' => $inicioTs, ':ini_c' => $inicioTs]);

        $mapa = ['abertos' => [], 'encerrados' => []];
        foreach ($stmt->fetchAll() as $row) {
            $mapa[$row['tipo']][$row['dia']] = (int)$row['qtd'];
        }

        $labels = $abertos = $encerrados = [];
        for ($i = 0; $i < $dias; $i++) {
            $dia = date('Y-m-d', strtotime($inicio . " +{$i} days"));
            $labels[] = date('d/m', strtotime($dia));
            $abertos[] = $mapa['abertos'][$dia] ?? 0;
            $encerrados[] = $mapa['encerrados'][$dia] ?? 0;
        }

        return [
            'labels'            => $labels,
            'abertos'           => $abertos,
            'encerrados'        => $encerrados,
            'total_abertos'     => array_sum($abertos),
            'total_encerrados'  => array_sum($encerrados),
        ];
    }

    /**
     * Fluxo de caixa realizado: receitas x despesas pagas por mês nos últimos $meses meses.
     */
    public function getCashFlow($meses = 6) {
        $meses = max(1, (int)$meses);
        $primeiroMes = date('Y-m-01', strtotime('first day of -' . ($meses - 1) . ' months'));

        $stmt = $this->db->prepare("
            SELECT DATE_FORMAT(data_pagamento, '%Y-%m') AS ym, tipo, COALESCE(SUM(valor), 0) AS total
            FROM financeiro_contabil
            WHERE status = 'pago' AND data_pagamento >= :ini
            GROUP BY ym, tipo
        ");
        $stmt->execute([':ini' => $primeiroMes]);

        $mapa = [];
        foreach ($stmt->fetchAll() as $row) {
            $mapa[$row['ym']][$row['tipo']] = (float)$row['total'];
        }

        $labels = $receitas = $despesas = $saldo = [];
        for ($i = 0; $i < $meses; $i++) {
            $ts = strtotime($primeiroMes . " +{$i} months");
            $ym = date('Y-m', $ts);
            $rec = $mapa[$ym]['receita'] ?? 0.0;
            $des = $mapa[$ym]['despesa'] ?? 0.0;
            $labels[] = self::MESES_ABREV[(int)date('n', $ts)] . '/' . date('y', $ts);
            $receitas[] = $rec;
            $despesas[] = $des;
            $saldo[] = $rec - $des;
        }

        return ['labels' => $labels, 'receitas' => $receitas, 'despesas' => $despesas, 'saldo' => $saldo];
    }

    // ------------------------------------------------------------------
    // Tabelas de atividade recente
    // ------------------------------------------------------------------

    /**
     * Últimos chamados. Ordena pela PK (equivale à ordem de criação e dispensa filesort)
     * e trunca a descrição no banco para não trafegar o TEXT inteiro.
     */
    public function getRecentTickets($limit = 8) {
        $stmt = $this->db->prepare("
            SELECT t.id, t.tipo_servico, LEFT(t.descricao, 90) AS descricao_resumo,
                   t.status, t.created_at, u.nome AS cliente_nome
            FROM tickets t
            JOIN users u ON u.id = t.cliente_id
            ORDER BY t.id DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Contas a pagar/receber ainda pendentes que vencem de hoje até hoje + $dias.
     */
    public function getUpcomingDues($dias = 7, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT f.id, f.tipo, f.descricao, f.valor, f.data_vencimento, u.nome AS parte_nome
            FROM financeiro_contabil f
            LEFT JOIN users u ON u.id = f.cliente_fornecedor_id
            WHERE f.status = 'pendente'
              AND f.data_vencimento >= :hoje
              AND f.data_vencimento <= :limite_data
            ORDER BY f.data_vencimento ASC, f.id ASC
            LIMIT :limite
        ");
        $stmt->bindValue(':hoje', date('Y-m-d'));
        $stmt->bindValue(':limite_data', date('Y-m-d', strtotime('+' . (int)$dias . ' days')));
        $stmt->bindValue(':limite', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Últimas ações registradas em admin_logs.
     * Retorna ['disponivel' => false] se a migração da tabela ainda não foi executada.
     */
    public function getRecentAdminLogs($limit = 8) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, user_name, acao, entidade, entidade_id, descricao, nivel, ip, created_at
                FROM admin_logs
                ORDER BY id DESC
                LIMIT :limite
            ");
            $stmt->bindValue(':limite', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return ['disponivel' => true, 'registros' => $stmt->fetchAll()];
        } catch (PDOException $e) {
            if ($e->getCode() === '42S02') {
                return ['disponivel' => false, 'registros' => []];
            }
            throw $e;
        }
    }
}
