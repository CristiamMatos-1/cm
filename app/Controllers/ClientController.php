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
        if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'cliente') {
            $this->redirect('/auth');
            exit;
        }
    }

    // ==========================================
    // PRIVACIDADE (LGPD - DIREITOS DO TITULAR)
    // ==========================================

    public function privacidade() {
        $userModel = new \app\Models\UserModel();
        $lgpdModel = new \app\Models\LgpdModel();

        $this->view('client/privacidade', [
            'title' => 'Privacidade (LGPD)',
            'usuario' => $userModel->getUserById($_SESSION['user_id']),
            'solicitacoes' => $lgpdModel->getRequestsByUser($_SESSION['user_id']),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function exportarMeusDados() {
        $this->requirePost();

        $lgpdModel = new \app\Models\LgpdModel();
        $dados = $lgpdModel->exportUserData($_SESSION['user_id']);

        if (!$dados) {
            $this->flash('error', 'Não foi possível gerar a exportação dos seus dados.');
            $this->redirect('/client/privacidade');
        }

        \app\Helpers\Audit::log('lgpd_exportacao_titular', 'user', (int)$_SESSION['user_id']);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="meus-dados-' . date('Ymd-His') . '.json"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function solicitarPrivacidade() {
        $this->requirePost();

        $tipo = (string)($_POST['tipo'] ?? '');
        $mensagem = Security::sanitizeInput($_POST['mensagem'] ?? '');

        if (!isset(\app\Helpers\Lgpd::TIPOS_SOLICITACAO[$tipo])) {
            $this->flash('error', 'Selecione o tipo de solicitação.');
            $this->redirect('/client/privacidade');
        }

        $lgpdModel = new \app\Models\LgpdModel();
        if ($lgpdModel->countOpenByUser($_SESSION['user_id']) >= 3) {
            $this->flash('warning', 'Você já possui solicitações em análise. Aguarde o retorno antes de abrir novas.');
            $this->redirect('/client/privacidade');
        }

        $id = $lgpdModel->createRequest($_SESSION['user_id'], $tipo, $mensagem);
        if ($id) {
            \app\Helpers\Audit::log('lgpd_solicitacao_criada', 'lgpd_request', $id, ['tipo' => $tipo]);
            $this->flash('success', 'Solicitação registrada (protocolo #' . $id . '). Responderemos em até 15 dias.');
        } else {
            $this->flash('error', 'Não foi possível registrar a solicitação. Tente novamente.');
        }

        $this->redirect('/client/privacidade');
    }

    // ==========================================
    // MÓDULO DE ORÇAMENTOS
    // ==========================================

    public function orcamentos() {
        $orcamentoModel = new \app\Models\OrcamentoModel();
        $orcamentoModel->checkExpiredBudgets();
        
        $this->view('client/orcamentos_list', [
            'title' => 'Meus Orçamentos',
            'orcamentos' => $orcamentoModel->getBudgetsByClient($_SESSION['user_id']),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function responderOrcamento($id) {
        $this->requirePost();
        
        $acao = $_POST['acao'] ?? '';
        $orcamentoModel = new \app\Models\OrcamentoModel();

        if (!in_array($acao, [\app\Models\OrcamentoModel::DECISION_APPROVE, \app\Models\OrcamentoModel::DECISION_REJECT], true)) {
            $resultado = ['ok' => false, 'code' => 'invalid', 'message' => 'Ação inválida para o orçamento.', 'budget' => null];
        } else {
            // O orçamento precisa pertencer ao cliente logado (evita IDOR).
            $orcamento = $orcamentoModel->getBudgetById($id);

            if (!$orcamento || (int)$orcamento['cliente_id'] !== (int)$_SESSION['user_id']) {
                $resultado = ['ok' => false, 'code' => 'not_found', 'message' => 'Orçamento não encontrado.', 'budget' => null];
            } else {
                $motivo = $acao === \app\Models\OrcamentoModel::DECISION_REJECT
                    ? Security::sanitizeInput($_POST['motivo'] ?? '')
                    : null;
                $resultado = $orcamentoModel->decide($id, $acao, $_SESSION['user_id'], $motivo, 'cliente');
            }
        }

        if ($this->isAjax()) {
            $this->respondBudgetDecision($resultado);
        }

        $this->flashBudgetDecision($resultado);
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
                    'tecnico_id' => $chamado['tecnico_id'] ?? null,
                    'programador_id' => $chamado['programador_id'] ?? null,
                    'engenheiro_id' => $chamado['engenheiro_id'] ?? null,
                    'status' => 'em_execucao',
                    'relatorio_final' => $chamado['relatorio_final'] ?? '',
                    'valor_pecas' => $chamado['valor_pecas'] ?? 0,
                    'valor_mao_obra' => $chamado['valor_mao_obra'] ?? 0,
                    'valor_servico' => $chamado['valor_servico'] ?? 0,
                    'forma_pagamento' => $chamado['forma_pagamento'] ?? null,
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
