<?php
namespace app\Controllers;

use app\Models\UserModel;
use app\Models\AuditLogModel;
use app\Helpers\Security;

class AuthController extends Controller {

    public function index() {
        // Se já estiver logado, redireciona para o dashboard correto
        if (isset($_SESSION['user_id'])) {
            $this->redirectDashboard($_SESSION['user_type']);
        }
        
        $this->view('auth/login', [
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function login() {
        $this->requirePost();
        
        $cpf_cnpj = Security::sanitizeInput($_POST['cpf_cnpj'] ?? '');
        $senha = $_POST['senha'] ?? '';

        $userModel = new UserModel();
        $user = $userModel->getUserByCpfCnpj($cpf_cnpj);

        if ($user && password_verify($senha, $user['senha'])) {
            // Prevenção de Fixation de Sessão
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_type'] = $user['perfil'];

            if ($user['perfil'] !== 'cliente') {
                AuditLogModel::record('login', 'Login realizado (' . $user['perfil'] . ')', 'user', $user['id']);
            }

            $this->redirectDashboard($user['perfil']);
        } else {
            if ($user && $user['perfil'] !== 'cliente') {
                AuditLogModel::record(
                    'login_falhou',
                    'Tentativa de login com senha incorreta na conta de "' . $user['nome'] . '" (' . $user['perfil'] . ')',
                    'user', $user['id'], AuditLogModel::NIVEL_AVISO,
                    ['id' => $user['id'], 'nome' => $user['nome']]
                );
            }
            // Credenciais inválidas
            $this->view('auth/login', [
                'csrf_token' => Security::generateCsrfToken(),
                'error' => 'CPF/CNPJ ou senha incorretos.'
            ]);
        }
    }

    public function register() {
        $this->requirePost();

        $dados = [
            'cpf_cnpj' => Security::sanitizeInput($_POST['reg_cpf_cnpj'] ?? ''),
            'nome' => Security::sanitizeInput($_POST['reg_nome'] ?? ''),
            'email' => Security::sanitizeInput($_POST['reg_email'] ?? ''),
            'telefone' => Security::sanitizeInput($_POST['reg_telefone'] ?? ''),
        ];
        
        $senha = $_POST['reg_senha'] ?? '';
        
        // Validação básica
        if (empty($dados['cpf_cnpj']) || empty($dados['nome']) || empty($senha)) {
            $this->view('auth/login', [
                'csrf_token' => Security::generateCsrfToken(),
                'error' => 'Por favor, preencha todos os campos obrigatórios.'
            ]);
            return;
        }

        if (strlen($senha) < 8) {
            $this->view('auth/login', [
                'csrf_token' => Security::generateCsrfToken(),
                'error' => 'A senha deve ter pelo menos 8 caracteres.'
            ]);
            return;
        }

        if (!empty($dados['email']) && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $this->view('auth/login', [
                'csrf_token' => Security::generateCsrfToken(),
                'error' => 'Informe um e-mail válido.'
            ]);
            return;
        }

        $dados['senha_hash'] = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

        $userModel = new UserModel();
        
        // Verifica se já existe
        if ($userModel->getUserByCpfCnpj($dados['cpf_cnpj'])) {
            $this->view('auth/login', [
                'csrf_token' => Security::generateCsrfToken(),
                'error' => 'Este CPF/CNPJ já está cadastrado no sistema.'
            ]);
            return;
        }

        if ($userModel->createUser($dados)) {
            // Sucesso no cadastro, faz login automático
            $user = $userModel->getUserByCpfCnpj($dados['cpf_cnpj']);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_type'] = $user['perfil'];
            
            $this->redirectDashboard($user['perfil']);
        } else {
            $this->view('auth/login', [
                'csrf_token' => Security::generateCsrfToken(),
                'error' => 'Erro ao realizar cadastro. Tente novamente.'
            ]);
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
        $this->redirect('/auth');
    }

    // ==================== Budget Approval via Link ====================
    public function autorizarOrcamento($token = '') {
        $orcamentoModel = new \app\Models\OrcamentoModel();
        $budget = $orcamentoModel->getBudgetByToken($token);

        if (!$budget) {
            $this->view('auth/erro', [
                'titulo' => 'Link Inválido',
                'mensagem' => 'O link de autorização do orçamento é inválido ou expirou.'
            ]);
            return;
        }

        // Atualiza o status caso a validade tenha vencido desde a última visita.
        if ($budget['status'] === 'pendente' && !empty($budget['data_validade']) && $budget['data_validade'] < date('Y-m-d')) {
            $orcamentoModel->checkExpiredBudgets();
            $budget = $orcamentoModel->getBudgetByToken($token);
        }

        $this->view('auth/autorizar_orcamento', [
            'budget' => $budget,
            'items' => $orcamentoModel->getBudgetItems($budget['id']),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function aprovarOrcamento() {
        $this->decidirPorToken(\app\Models\OrcamentoModel::DECISION_APPROVE);
    }

    public function rejeitarOrcamento() {
        $this->decidirPorToken(\app\Models\OrcamentoModel::DECISION_REJECT);
    }

    private function decidirPorToken($decision) {
        $this->requirePost();

        $token = (string)($_POST['token'] ?? '');
        $orcamentoModel = new \app\Models\OrcamentoModel();
        $budget = $orcamentoModel->getBudgetByToken($token);

        if (!$budget) {
            $resultado = ['ok' => false, 'code' => 'not_found', 'message' => 'O link de autorização do orçamento é inválido.', 'budget' => null];
        } else {
            $motivo = $decision === \app\Models\OrcamentoModel::DECISION_REJECT
                ? Security::sanitizeInput($_POST['motivo'] ?? '')
                : null;
            $resultado = $orcamentoModel->decide($budget['id'], $decision, $budget['cliente_id'], $motivo);
        }

        if ($this->isAjax()) {
            $this->respondBudgetDecision($resultado);
        }

        if ($resultado['ok']) {
            $aprovado = $decision === \app\Models\OrcamentoModel::DECISION_APPROVE;
            $this->view('auth/sucesso', [
                'titulo' => $aprovado ? 'Orçamento Aprovado' : 'Orçamento Rejeitado',
                'mensagem' => $aprovado
                    ? 'O orçamento #' . $budget['id'] . ' foi aprovado com sucesso! Você será contatado em breve.'
                    : 'O orçamento #' . $budget['id'] . ' foi rejeitado. O responsável será notificado.',
                'tipo' => $aprovado ? 'sucesso' : 'rejeicao'
            ]);
            return;
        }

        $this->view('auth/erro', [
            'titulo' => $decision === \app\Models\OrcamentoModel::DECISION_APPROVE ? 'Não foi possível aprovar' : 'Não foi possível rejeitar',
            'mensagem' => $resultado['message']
        ]);
    }

    private function redirectDashboard($type) {
        switch ($type) {
            case 'admin':
                $this->redirect('/admin');
                break;
            case 'tecnico':
                $this->redirect('/tech');
                break;
            default: // cliente
                $this->redirect('/client');
                break;
        }
    }
}
