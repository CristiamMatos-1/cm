<?php
namespace app\Controllers;

use app\Models\DashboardModel;
use app\Models\UserModel;

class DashboardController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/auth');
            exit;
        }

        if ($_SESSION['user_type'] === 'cliente') {
            $this->redirect('/client');
            exit;
        }
    }

    public function index() {
        $dashboardModel = new DashboardModel();
        
        // Atualizar orçamentos expirados
        $dashboardModel->checkExpiredBudgets();

        // Obter estatísticas
        $ticketStats = $dashboardModel->getTicketStats();
        $budgetStats = $dashboardModel->getBudgetStats();
        $avulsoStats = $dashboardModel->getAvulsoServiceStats();
        $clientCount = $dashboardModel->getClientStats();
        $contractStats = $dashboardModel->getContractStats();
        $invoiceStats = $dashboardModel->getInvoiceStats();
        $financialSummary = $dashboardModel->getFinancialSummary();

        // Gráficos
        $ticketsByClient = $dashboardModel->getTicketsByClientChart();
        $budgetsByStatus = $dashboardModel->getBudgetsByStatusChart();

        // Atividades recentes
        $recentTickets = $dashboardModel->getRecentTickets(5);
        $recentBudgets = $dashboardModel->getRecentBudgets(5);
        $pendingApprovals = $dashboardModel->getPendingApprovals(5);

        return $this->view('admin/dashboard', [
            'ticketStats' => $ticketStats,
            'budgetStats' => $budgetStats,
            'avulsoStats' => $avulsoStats,
            'clientCount' => $clientCount,
            'contractStats' => $contractStats,
            'invoiceStats' => $invoiceStats,
            'financialSummary' => $financialSummary,
            'ticketsByClient' => $ticketsByClient,
            'budgetsByStatus' => $budgetsByStatus,
            'recentTickets' => $recentTickets,
            'recentBudgets' => $recentBudgets,
            'pendingApprovals' => $pendingApprovals,
        ]);
    }
}
