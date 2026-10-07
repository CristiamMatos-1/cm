<?php
namespace app\Controllers;

use app\Models\DashboardModel;

/**
 * Visão Geral do administrador (rotas /admin, /admin/dashboard e /dashboard).
 *
 * O controller só orquestra: toda a SQL fica em DashboardModel e a apresentação em
 * app/Views/admin/dashboard.php. Para acrescentar um novo indicador:
 *   1. crie o método no DashboardModel;
 *   2. chame-o aqui e envie o resultado em $this->view([...]);
 *   3. renderize na view (cada bloco tem um comentário "DADOS DINÂMICOS").
 */
class DashboardController extends Controller {

    private const PERIODO_GRAFICO_CHAMADOS_DIAS = 30;
    private const PERIODO_FLUXO_CAIXA_MESES = 6;
    private const PERIODO_NOVOS_REGISTROS_DIAS = 30;
    private const VENCIMENTOS_PROXIMOS_DIAS = 7;
    private const LINHAS_TABELAS = 8;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/auth');
            exit;
        }

        if ($_SESSION['user_type'] === 'cliente') {
            $this->redirect('/client');
            exit;
        }

        // O painel mostra dados financeiros e de auditoria: restrito a administradores.
        if ($_SESSION['user_type'] !== 'admin') {
            $this->redirect('/tech');
            exit;
        }
    }

    public function index() {
        $model = new DashboardModel();

        $model->checkExpiredBudgets();

        // O fluxo de caixa é calculado antes porque o KPI financeiro reaproveita o mês corrente dele.
        $fluxoCaixa = $model->getCashFlow(self::PERIODO_FLUXO_CAIXA_MESES);

        return $this->view('admin/dashboard', [
            'title'          => 'Visão Geral',

            // KPIs
            'ticketKpis'     => $model->getTicketKpis(),
            'budgetKpis'     => $model->getBudgetKpis(),
            'financeKpis'    => $model->getFinanceKpis($fluxoCaixa),
            'userKpis'       => $model->getUserKpis(self::PERIODO_NOVOS_REGISTROS_DIAS),
            'systemStatus'   => $model->getSystemStatus(),

            // Gráficos
            'ticketFlow'     => $model->getTicketFlow(self::PERIODO_GRAFICO_CHAMADOS_DIAS),
            'cashFlow'       => $fluxoCaixa,

            // Tabelas
            'recentTickets'  => $model->getRecentTickets(self::LINHAS_TABELAS),
            'upcomingDues'   => $model->getUpcomingDues(self::VENCIMENTOS_PROXIMOS_DIAS, self::LINHAS_TABELAS + 2),
            'adminLogs'      => $model->getRecentAdminLogs(self::LINHAS_TABELAS),

            'diasProximosVencimentos' => self::VENCIMENTOS_PROXIMOS_DIAS,
        ]);
    }
}
