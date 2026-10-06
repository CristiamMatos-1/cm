<?php
namespace app\Controllers;

use app\Models\ChamadoModel;
use app\Services\GeminiService;
use app\Helpers\Security;

class TechController extends Controller {

    public function __construct() {
        // Verifica se o usuário está logado e se é tecnico
        if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'tecnico') {
            $this->redirect('/auth');
            exit;
        }
    }

    public function index() {
        $chamadoModel = new ChamadoModel();
        
        $totalMeusChamados = $chamadoModel->countByTecnico($_SESSION['user_id']);

        $this->view('dashboard/tech', [
            'title' => 'Painel do Técnico',
            'totalMeusChamados' => $totalMeusChamados
        ]);
    }

    public function chamados() {
        $chamadoModel = new ChamadoModel();
        $fila = $chamadoModel->getFilaChamados($_SESSION['user_id']);

        $this->view('tech/chamados_list', [
            'title' => 'Fila de Chamados',
            'chamados' => $fila
        ]);
    }

    public function chamadoView($id) {
        $chamadoModel = new ChamadoModel();
        $chamado = $chamadoModel->getById($id);

        if (!$chamado) {
            $this->redirect('/tech/chamados');
        }

        $midias = $chamadoModel->getMediaByTicket($id);

        // Se houver pedido de IA na sessão (via redirecionamento)
        $iaResponse = $_SESSION['ia_response'] ?? null;
        unset($_SESSION['ia_response']);

        $this->view('tech/chamado_view', [
            'title' => 'Triagem de Chamado #' . $id,
            'chamado' => $chamado,
            'midias' => $midias,
            'iaResponse' => $iaResponse,
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function assumirChamado($id) {
        $this->requirePost();
        $chamadoModel = new ChamadoModel();
        $chamado = $chamadoModel->getById($id);

        if ($chamado && empty($chamado['tecnico_id'])) {
            $chamadoModel->assumirChamado($id, $_SESSION['user_id']);
        }

        $this->redirect('/tech/chamadoView/' . $id);
    }

    public function atualizarChamado($id) {
        $this->requirePost();

        $atendimento = $_POST['atendimento'] ?? null;
        $status = $_POST['status'] ?? 'andamento';
        $relatorio = Security::sanitizeInput($_POST['relatorio'] ?? '');

        $statusValidos = ['andamento', 'em_analise', 'em_execucao', 'esperando_peca', 'finalizado'];
        if (!in_array($status, $statusValidos, true) || !in_array($atendimento, ['remoto', 'presencial', null, ''], true)) {
            $this->redirect('/tech/chamadoView/' . (int)$id);
        }
        $atendimento = $atendimento === '' ? null : $atendimento;

        $chamadoModel = new ChamadoModel();
        $chamado = $chamadoModel->getById($id);

        // Apenas o técnico responsável pelo chamado pode atualizá-lo.
        if (!$chamado || (int)$chamado['tecnico_id'] !== (int)$_SESSION['user_id']) {
            $this->redirect('/tech/chamados');
        }

        $chamadoModel->atualizarTriagem($id, $atendimento, $status, $relatorio);

        $this->redirect('/tech/chamadoView/' . $id);
    }

    public function analisarIA($id) {
        $this->requirePost();
        $chamadoModel = new ChamadoModel();
        $chamado = $chamadoModel->getById($id);

        if ($chamado && (int)$chamado['tecnico_id'] === (int)$_SESSION['user_id']) {
            $geminiService = new GeminiService();
            $analise = $geminiService->analyzeTicket($chamado['descricao']);
            
            // Salva na sessão para exibir após redirecionamento
            $_SESSION['ia_response'] = Security::esc($analise);
        }

        $this->redirect('/tech/chamadoView/' . $id);
    }
}
