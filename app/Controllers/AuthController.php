<?php
namespace app\Controllers;

use app\Models\UserModel;
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

            $this->redirectDashboard($user['perfil']);
        } else {
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
    public function autorizarOrcamento($token = null) {
        $token = $token ?: ($_GET['token'] ?? ($_POST['token'] ?? ''));

        if (empty($token)) {
            $this->view('auth/erro', [
                'titulo' => 'Link Inválido',
                'mensagem' => 'O token de autorização não foi informado.'
            ]);
            return;
        }

        $orcamentoModel = new \app\Models\OrcamentoModel();
        $budget = $orcamentoModel->getBudgetByToken($token);

        if (!$budget) {
            $this->view('auth/erro', [
                'titulo' => 'Link Inválido',
                'mensagem' => 'O link de autorização do orçamento é inválido ou expirou.'
            ]);
            return;
        }

        if ($budget['status'] === 'expirado') {
            $this->view('auth/erro', [
                'titulo' => 'Orçamento Expirado',
                'mensagem' => 'Este orçamento expirou em ' . date('d/m/Y', strtotime($budget['data_validade'])) . '.'
            ]);
            return;
        }

        if ($budget['status'] === 'aprovado') {
            $this->view('auth/erro', [
                'titulo' => 'Orçamento Já Aprovado',
                'mensagem' => 'Este orçamento já foi aprovado.'
            ]);
            return;
        }

        $this->view('auth/autorizar_orcamento', [
            'budget' => $budget,
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function aprovarOrcamento() {
        $this->requirePost();
        $token = $_POST['token'] ?? '';

        $orcamentoModel = new \app\Models\OrcamentoModel();
        $budget = $orcamentoModel->getBudgetByToken($token);

        if ($budget && $budget['status'] !== 'aprovado' && $budget['status'] !== 'expirado') {
            $admin_id = $budget['cliente_id'];
            $orcamentoModel->approveBudget($budget['id'], $admin_id);

            $this->view('auth/sucesso', [
                'titulo' => 'Orçamento Aprovado',
                'mensagem' => 'O orçamento #' . $budget['id'] . ' foi aprovado com sucesso! Você será contatado em breve.',
                'tipo' => 'sucesso'
            ]);
        } else {
            $this->view('auth/erro', [
                'titulo' => 'Erro ao Aprovar',
                'mensagem' => 'Não foi possível aprovar o orçamento.'
            ]);
        }
    }

    public function rejeitarOrcamento() {
        $this->requirePost();
        $token = $_POST['token'] ?? '';
        $motivo = Security::sanitizeInput($_POST['motivo'] ?? '');

        $orcamentoModel = new \app\Models\OrcamentoModel();
        $budget = $orcamentoModel->getBudgetByToken($token);

        if ($budget && $budget['status'] !== 'rejeitado' && $budget['status'] !== 'expirado') {
            $admin_id = $budget['cliente_id'];
            $orcamentoModel->rejectBudget($budget['id'], $admin_id, $motivo);

            $this->view('auth/sucesso', [
                'titulo' => 'Orçamento Rejeitado',
                'mensagem' => 'O orçamento #' . $budget['id'] . ' foi rejeitado. O responsável será notificado.',
                'tipo' => 'rejeicao'
            ]);
        } else {
            $this->view('auth/erro', [
                'titulo' => 'Erro ao Rejeitar',
                'mensagem' => 'Não foi possível rejeitar o orçamento.'
            ]);
        }
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
