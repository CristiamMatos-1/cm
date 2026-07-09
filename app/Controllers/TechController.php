<?php
namespace app\Controllers;

use app\Models\ChamadoModel;
use app\Services\GeminiService;
use app\Helpers\Security;

class TechController extends Controller {

    public function __construct() {
        // Verifica se o usuário está logado e se é tecnico
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tecnico') {
            $this->redirect('/auth');
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

        $chamadoModel = new ChamadoModel();
        $chamadoModel->atualizarTriagem($id, $atendimento, $status, $relatorio);

        $this->redirect('/tech/chamadoView/' . $id);
    }

    public function analisarIA($id) {
        $chamadoModel = new ChamadoModel();
        $chamado = $chamadoModel->getById($id);

        if ($chamado) {
            $geminiService = new GeminiService();
            $analise = $geminiService->analyzeTicket($chamado['descricao']);
            
            // Salva na sessão para exibir após redirecionamento
            $_SESSION['ia_response'] = Security::esc($analise);
        }

        $this->redirect('/tech/chamadoView/' . $id);
    }
}
