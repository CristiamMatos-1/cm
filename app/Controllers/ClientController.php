<?php
namespace app\Controllers;

use app\Models\ChamadoModel;
use app\Models\FinanceiroModel;
use app\Models\ConfigModel;
use app\Helpers\Security;
use app\Helpers\UploadHelper;

class ClientController extends Controller {

    public function __construct() {
        // Verifica se o usuário está logado e se é cliente
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'cliente') {
            $this->redirect('/auth');
        }
    }

    // ==========================================
    // MÓDULO DE ORÇAMENTOS
    // ==========================================

    public function orcamentos() {
        $orcamentoModel = new \app\Models\OrcamentoModel();
        
        $this->view('client/orcamentos_list', [
            'title' => 'Meus Orçamentos',
            'orcamentos' => $orcamentoModel->getBudgetsByClient($_SESSION['user_id']),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function responderOrcamento($id) {
        $this->requirePost();
        
        $acao = $_POST['acao'] ?? '';
        $status = ($acao === 'aprovar') ? 'aprovado' : 'rejeitado';

        $orcamentoModel = new \app\Models\OrcamentoModel();
        
        // Verifica se o orçamento realmente pertence a este cliente
        $orcamento = null;
        $lista = $orcamentoModel->getBudgetsByClient($_SESSION['user_id']);
        foreach ($lista as $item) {
            if ($item['id'] == $id) {
                $orcamento = $item;
                break;
            }
        }

        if ($orcamento && $orcamento['status'] === 'pendente') {
            $orcamentoModel->updateStatus($id, $status);
        }

        $this->redirect('/client/orcamentos');
    }

    public function index() {
        $chamadoModel = new ChamadoModel();
        
        $totalChamados = $chamadoModel->countByCliente($_SESSION['user_id']);
        $ultimosChamados = $chamadoModel->getLatestByCliente($_SESSION['user_id']);

        $this->view('dashboard/client', [
            'title' => 'Painel do Cliente',
            'totalChamados' => $totalChamados,
            'ultimosChamados' => $ultimosChamados
        ]);
    }

    public function chamados() {
        $chamadoModel = new ChamadoModel();
        $meusChamados = $chamadoModel->getLatestByCliente($_SESSION['user_id'], 50); // Pega os últimos 50

        $this->view('client/chamados_list', [
            'title' => 'Meus Chamados',
            'chamados' => $meusChamados
        ]);
    }

    public function verChamado($id) {
        $chamadoModel = new ChamadoModel();
        $chamado = $chamadoModel->getById($id);

        // Segurança: verificar se o chamado pertence ao cliente
        if (!$chamado || $chamado['cliente_id'] != $_SESSION['user_id']) {
            $this->redirect('/client/chamados');
            return;
        }

        $this->view('client/ver_chamado', [
            'title' => 'Detalhes do Chamado #' . $chamado['id'],
            'chamado' => $chamado
        ]);
    }

    public function responderChamado($id) {
        $this->requirePost();
        
        $acao = $_POST['acao'] ?? '';
        
        $chamadoModel = new ChamadoModel();
        $chamado = $chamadoModel->getById($id);

        if ($chamado && $chamado['cliente_id'] == $_SESSION['user_id']) {
            if ($acao === 'aprovar') {
                $dados = [
                    'tecnico_id' => $chamado['tecnico_id'],
                    'programador_id' => $chamado['programador_id'],
                    'engenheiro_id' => $chamado['engenheiro_id'],
                    'status' => 'em_execucao',
                    'relatorio_final' => $chamado['relatorio_final'],
                    'valor_pecas' => $chamado['valor_pecas'],
                    'valor_mao_obra' => $chamado['valor_mao_obra'],
                    'valor_servico' => $chamado['valor_servico'],
                    'forma_pagamento' => $chamado['forma_pagamento'],
                    'autorizado_por' => 'sistema_cliente',
                    'data_autorizacao' => date('Y-m-d H:i:s')
                ];
                $chamadoModel->atualizarChamadoAdmin($id, $dados);
            }
        }

        $this->redirect('/client/verChamado/' . $id);
    }

    public function novoChamado() {
        $this->view('client/novo_chamado', [
            'title' => 'Abertura de Chamado',
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarChamado() {
        $this->requirePost();

        $dados = [
            'cliente_id' => $_SESSION['user_id'],
            'tipo_servico' => Security::sanitizeInput($_POST['tipo_servico'] ?? ''),
            'descricao' => Security::sanitizeInput($_POST['descricao'] ?? '')
        ];

        // Validação básica
        if (empty($dados['tipo_servico']) || empty($dados['descricao'])) {
            $this->redirect('/client/novoChamado');
        }

        $chamadoModel = new ChamadoModel();
        $ticketId = $chamadoModel->createTicket($dados);

        if ($ticketId) {
            // Processa o upload se houver arquivos
            if (!empty($_FILES['midias']['name'][0])) {
                $uploadResult = UploadHelper::processTicketMedia($_FILES['midias']);
                
                if (!empty($uploadResult['success'])) {
                    foreach ($uploadResult['success'] as $media) {
                        $chamadoModel->addMedia($ticketId, $_SESSION['user_id'], $media['url'], $media['tipo']);
                    }
                }
            }

            $this->redirect('/client/chamados');
        } else {
            $this->redirect('/client/novoChamado');
        }
    }

    // ==========================================
    // MÓDULO FINANCEIRO (CONTRATOS E NOTAS)
    // ==========================================
    
    public function contratos() {
        $financeiroModel = new FinanceiroModel();

        $contratos = $financeiroModel->getContratosByCliente($_SESSION['user_id']);
        $notas = $financeiroModel->getNotasByCliente($_SESSION['user_id']);

        $this->view('client/contratos_list', [
            'title' => 'Meus Contratos e Notas',
            'contratos' => $contratos,
            'notas' => $notas
        ]);
    }

    // ==========================================
    // MÓDULO DE PATRIMÔNIO (ATIVOS)
    // ==========================================

    public function patrimonio() {
        $configModel = new ConfigModel();
        $ativos = $configModel->getAssetsByClient($_SESSION['user_id']);

        $this->view('client/patrimonio_list', [
            'title' => 'Meu Patrimônio e Ativos',
            'ativos' => $ativos
        ]);
    }
}
