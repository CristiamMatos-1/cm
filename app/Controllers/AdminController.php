<?php
namespace app\Controllers;

use app\Models\ChamadoModel;
use app\Models\FinanceiroModel;
use app\Models\UserModel;
use app\Models\ConfigModel;
use app\Models\OrcamentoModel;
use app\Models\DashboardModel;
use app\Models\AvulsoServiceModel;
use app\Models\ReportModel;
use app\Models\ContabilModel;
use app\Models\ProjetoModel;
use app\Models\FeedModel;
use app\Helpers\Security;
use app\Helpers\UploadHelper;

class AdminController extends Controller {

    private const PERFIS_FUNCIONARIO = ['admin', 'tecnico'];
    private const PERMISSOES_VALIDAS = ['abrir_chamado_admin', 'criar_orcamento', 'acesso_financeiro', 'gerar_relatorios'];

    private function sanitizePermissoes($permissoes) {
        if (!is_array($permissoes)) {
            return json_encode([]);
        }
        return json_encode(array_values(array_intersect(self::PERMISSOES_VALIDAS, $permissoes)));
    }

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/auth');
            exit;
        }

        if ($_SESSION['user_type'] === 'cliente') {
            $this->redirect('/client');
            exit;
        }

        // Técnicos só acessam rotas administrativas liberadas pelas suas permissões granulares.
        if ($_SESSION['user_type'] === 'tecnico') {
            $url = isset($_GET['url']) ? trim((string)$_GET['url'], '/') : '';
            $parts = explode('/', strtolower($url));
            $action = $parts[1] ?? 'index';

            $userModel = new UserModel();
            $user = $userModel->getUserById($_SESSION['user_id']);
            $perms = json_decode($user['permissoes'] ?? '[]', true) ?: [];

            $routesByPermission = [
                'acesso_financeiro' => ['contabil', 'novolancamentocontabil', 'alterarstatuslancamento'],
                'criar_orcamento' => [
                    'orcamentos', 'novoorcamento', 'salvarorcamento', 'editarorcamento', 'salvaredicaoorcamento',
                    'aprovarorcamento', 'rejeitarorcamento', 'reativarorcamento',
                    'adicionaritemorcamento', 'removeritemorcamento',
                    'imprimirorcamento', 'imprimirorcamentonovo', 'enviaremailorcamento', 'enviarwhatsapporcamento'
                ],
                'gerar_relatorios' => ['relatorios'],
                'abrir_chamado_admin' => ['chamados', 'editarchamado', 'salvaredicaochamado', 'imprimirchamado', 'enviaremailchamado'],
            ];

            $permitido = false;
            foreach ($routesByPermission as $perm => $actions) {
                if (in_array($perm, $perms, true) && in_array($action, $actions, true)) {
                    $permitido = true;
                    break;
                }
            }

            if (!$permitido) {
                $this->redirect('/tech');
                exit;
            }
        }
    }

    public function chamados() {
        $chamadoModel = new ChamadoModel();
        
        $this->view('admin/chamados_list', [
            'title' => 'Todos os Chamados',
            'chamados' => $chamadoModel->getAllChamados(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function editarChamado($id) {
        $chamadoModel = new ChamadoModel();
        $userModel = new UserModel();

        $chamado = $chamadoModel->getById($id);
        
        if (!$chamado) {
            $this->redirect('/admin/chamados');
            return;
        }

        $this->view('admin/editar_chamado', [
            'title' => 'Editar Chamado',
            'chamado' => $chamado,
            'funcionarios' => $userModel->getAllUsers(), // Busca todos que não são clientes
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarEdicaoChamado($id) {
        $this->requirePost();

        $dados = [
            'tecnico_id' => !empty($_POST['tecnico_id']) ? $_POST['tecnico_id'] : null,
            'programador_id' => !empty($_POST['programador_id']) ? $_POST['programador_id'] : null,
            'engenheiro_id' => !empty($_POST['engenheiro_id']) ? $_POST['engenheiro_id'] : null,
            'status' => $_POST['status'] ?? 'aberto',
            'relatorio_final' => Security::sanitizeInput($_POST['relatorio_final'] ?? ''),
            'valor_pecas' => Security::parseMoney($_POST['valor_pecas'] ?? '0'),
            'valor_mao_obra' => Security::parseMoney($_POST['valor_mao_obra'] ?? '0'),
            'valor_servico' => Security::parseMoney($_POST['valor_servico'] ?? '0'),
            'forma_pagamento' => !empty($_POST['forma_pagamento']) ? $_POST['forma_pagamento'] : null,
            'autorizado_por' => !empty($_POST['autorizado_por']) ? $_POST['autorizado_por'] : null
        ];

        if (!empty($dados['autorizado_por']) && $dados['status'] !== 'aberto' && $dados['status'] !== 'rejeitado') {
            $dados['data_autorizacao'] = date('Y-m-d H:i:s');
        }

        $chamadoModel = new ChamadoModel();
        $chamadoModel->atualizarChamadoAdmin($id, $dados);

        $this->redirect('/admin/chamados');
    }

    public function imprimirChamado($id) {
        $chamadoModel = new ChamadoModel();
        $chamado = $chamadoModel->getById($id);

        if (!$chamado) {
            http_response_code(404);
            die("Chamado não encontrado.");
        }

        // Não carrega header/footer padrão, carrega a view de impressão limpa
        require_once APP_PATH . '/Views/admin/imprimir_chamado.php';
    }

    public function enviarEmailChamado($id) {
        $this->requirePost();

        $chamadoModel = new ChamadoModel();
        $chamado = $chamadoModel->getById($id);

        if ($chamado && !empty($chamado['cliente_email'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $link = $scheme . "://" . $_SERVER['HTTP_HOST'] . BASE_URL . "/client/verChamado/" . (int)$id;
            $mensagem = "<p>Um novo serviço/chamado foi registrado e atualizado para você.</p>";
            $mensagem .= "<p>Você pode visualizar o Escopo, Valores e <strong>Autorizar a Execução</strong> acessando o link abaixo:</p>";
            $mensagem .= "<p><a href='" . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . "' style='display:inline-block; padding:10px 20px; background-color:#1e3a8a; color:#fff; text-decoration:none; border-radius:5px;'>Visualizar e Autorizar Serviço</a></p>";
            
            $enviado = \app\Helpers\MailHelper::enviarEmail($chamado['cliente_email'], $chamado['cliente_nome'], "Acompanhamento de Serviço #" . (int)$id, $mensagem);
            $this->flash($enviado ? 'success' : 'error', $enviado ? 'E-mail enviado ao cliente.' : 'Não foi possível enviar o e-mail.');
        } else {
            $this->flash('error', 'Chamado não encontrado ou cliente sem e-mail.');
        }

        $this->redirect('/admin/editarChamado/' . (int)$id);
    }

    public function excluirChamado($id) {
        $this->requirePost();
        $chamadoModel = new ChamadoModel();
        $chamadoModel->deleteTicket($id);
        $this->flash('success', 'Chamado excluído com sucesso.');
        $this->redirect('/admin/chamados');
    }

    public function clientes() {
        $userModel = new UserModel();
        
        $this->view('admin/clientes_list', [
            'title' => 'Clientes',
            'clientes' => $userModel->getAllClients()
        ]);
    }

    public function editarCliente($id) {
        $userModel = new UserModel();
        $cliente = $userModel->getUserById($id);

        if (!$cliente || $cliente['perfil'] !== 'cliente') {
            $this->redirect('/admin/clientes');
            return;
        }

        $this->view('admin/editar_cliente', [
            'title' => 'Editar Cliente',
            'cliente' => $cliente,
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarEdicaoCliente($id) {
        $this->requirePost();

        $dados = [
            'nome' => Security::sanitizeInput($_POST['nome'] ?? ''),
            'email' => Security::sanitizeInput($_POST['email'] ?? ''),
            'telefone' => Security::sanitizeInput($_POST['telefone'] ?? ''),
            'cep' => Security::sanitizeInput($_POST['cep'] ?? ''),
            'logradouro' => Security::sanitizeInput($_POST['logradouro'] ?? ''),
            'numero' => Security::sanitizeInput($_POST['numero'] ?? ''),
            'complemento' => Security::sanitizeInput($_POST['complemento'] ?? ''),
            'bairro' => Security::sanitizeInput($_POST['bairro'] ?? ''),
            'cidade' => Security::sanitizeInput($_POST['cidade'] ?? ''),
            'estado' => Security::sanitizeInput($_POST['estado'] ?? ''),
            'responsavel_nome' => Security::sanitizeInput($_POST['responsavel_nome'] ?? '')
        ];

        $userModel = new UserModel();
        $userModel->updateClient($id, $dados);

        $this->redirect('/admin/clientes');
    }

    public function excluirUsuario($id) {
        $this->requirePost();

        if ($id == $_SESSION['user_id']) {
            $this->flash('error', 'Você não pode excluir a si mesmo.');
            $this->redirect('/admin/usuarios');
        }

        $userModel = new UserModel();
        $userModel->deleteUser($id);
        $this->flash('success', 'Usuário excluído com sucesso.');

        $this->redirect('/admin/usuarios');
    }

    // ==========================================
    // MÓDULO 8: RELATÓRIOS E IMPRESSÃO
    // ==========================================

    public function relatorios() {
        $reportModel = new ReportModel();
        
        $this->view('admin/relatorios', [
            'title' => 'Relatórios do Sistema',
            'servicos' => $reportModel->getServicosFinalizados(),
            'clientes' => $reportModel->getBalancoClientes()
        ]);
    }

    // ==========================================
    // MÓDULO 7: ORÇAMENTOS E SERVIÇOS AVULSOS
    // ==========================================

    public function orcamentos() {
        $orcamentoModel = new OrcamentoModel();
        $orcamentoModel->checkExpiredBudgets();

        $this->view('admin/orcamentos_list', [
            'title' => 'Gestão de Orçamentos',
            'orcamentos' => $orcamentoModel->getAllBudgets(),
            'csrf_token' => Security::generateCsrfToken(),
            'podeExcluir' => $_SESSION['user_type'] === 'admin'
        ]);
    }

    public function novoOrcamento() {
        $userModel = new UserModel();
        $chamadoModel = new ChamadoModel();
        
        $this->view('admin/novo_orcamento', [
            'title' => 'Criar Novo Orçamento',
            'clientes' => $userModel->getAllClients(),
            'chamados_abertos' => $chamadoModel->getAllChamados(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    private function parseValidade($value) {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return ($date && $date->format('Y-m-d') === $value) ? $value : null;
    }

    public function salvarOrcamento() {
        $this->requirePost();

        $valor_pecas = Security::parseMoney($_POST['valor_pecas'] ?? '0');
        $valor_mao_obra = Security::parseMoney($_POST['valor_mao_obra'] ?? '0');

        $dados = [
            'cliente_id' => (int)($_POST['cliente_id'] ?? 0),
            'ticket_id' => !empty($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : null,
            'titulo' => Security::sanitizeInput($_POST['titulo'] ?? ''),
            'descricao' => Security::sanitizeInput($_POST['descricao'] ?? ''),
            'valor_pecas' => $valor_pecas,
            'valor_mao_obra' => $valor_mao_obra,
            'valor_total' => $valor_pecas + $valor_mao_obra,
            'data_validade' => $this->parseValidade($_POST['data_validade'] ?? '')
        ];

        if ($dados['cliente_id'] <= 0 || $dados['titulo'] === '' || $dados['descricao'] === '') {
            $this->flash('error', 'Preencha cliente, título e descrição para gerar o orçamento.');
            $this->redirect('/admin/novoOrcamento');
        }

        if ($dados['valor_pecas'] < 0 || $dados['valor_mao_obra'] < 0) {
            $this->flash('error', 'Os valores do orçamento não podem ser negativos.');
            $this->redirect('/admin/novoOrcamento');
        }

        if ($dados['data_validade'] !== null && $dados['data_validade'] < date('Y-m-d')) {
            $this->flash('error', 'A data de validade não pode estar no passado.');
            $this->redirect('/admin/novoOrcamento');
        }

        $userModel = new UserModel();
        $cliente = $userModel->getUserById($dados['cliente_id']);
        if (!$cliente || $cliente['perfil'] !== 'cliente') {
            $this->flash('error', 'Cliente inválido.');
            $this->redirect('/admin/novoOrcamento');
        }

        try {
            $orcamentoModel = new OrcamentoModel();
            $id = $orcamentoModel->createBudget($dados);
        } catch (\Throwable $e) {
            error_log('Erro ao criar orçamento: ' . $e->getMessage());
            $id = false;
        }

        if ($id) {
            $this->flash('success', 'Orçamento #' . $id . ' criado com sucesso.');
            $this->redirect('/admin/editarOrcamento/' . $id);
        }

        $this->flash('error', 'Não foi possível criar o orçamento. Tente novamente.');
        $this->redirect('/admin/novoOrcamento');
    }

    public function editarOrcamento($id) {
        $orcamentoModel = new OrcamentoModel();
        $chamadoModel = new ChamadoModel();
        
        $orcamento = $orcamentoModel->getBudgetById($id);
        
        if (!$orcamento) {
            $this->flash('error', 'Orçamento não encontrado.');
            $this->redirect('/admin/orcamentos');
            return;
        }

        $this->view('admin/editar_orcamento', [
            'title' => 'Orçamento #' . (int)$id,
            'orcamento' => $orcamento,
            'itens' => $orcamentoModel->getBudgetItems($id),
            'editavel' => $orcamentoModel->isEditable($orcamento),
            'chamados_abertos' => $chamadoModel->getAllChamados(),
            'csrf_token' => Security::generateCsrfToken(),
            'podeExcluir' => $_SESSION['user_type'] === 'admin'
        ]);
    }

    public function salvarEdicaoOrcamento($id) {
        $this->requirePost();

        $orcamentoModel = new OrcamentoModel();
        $orcamento = $orcamentoModel->getBudgetById($id);

        if (!$orcamento) {
            $this->flash('error', 'Orçamento não encontrado.');
            $this->redirect('/admin/orcamentos');
        }

        if (!$orcamentoModel->isEditable($orcamento)) {
            $this->flash('warning', 'Este orçamento já foi ' . $orcamento['status'] . ' e não pode mais ser alterado.');
            $this->redirect('/admin/editarOrcamento/' . (int)$id);
        }

        $dados = [
            'titulo' => Security::sanitizeInput($_POST['titulo'] ?? ''),
            'descricao' => Security::sanitizeInput($_POST['descricao'] ?? ''),
            'ticket_id' => !empty($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : null,
            'data_validade' => $this->parseValidade($_POST['data_validade'] ?? '')
        ];

        if ($dados['titulo'] === '' || $dados['descricao'] === '') {
            $this->flash('error', 'Título e descrição são obrigatórios.');
            $this->redirect('/admin/editarOrcamento/' . (int)$id);
        }

        if ($dados['data_validade'] !== null && $dados['data_validade'] < date('Y-m-d')) {
            $this->flash('error', 'A data de validade não pode estar no passado.');
            $this->redirect('/admin/editarOrcamento/' . (int)$id);
        }

        // Com itens detalhados, os totais são sempre derivados dos itens.
        if (count($orcamentoModel->getBudgetItems($id)) === 0) {
            $valor_pecas = Security::parseMoney($_POST['valor_pecas'] ?? '0');
            $valor_mao_obra = Security::parseMoney($_POST['valor_mao_obra'] ?? '0');

            if ($valor_pecas < 0 || $valor_mao_obra < 0) {
                $this->flash('error', 'Os valores do orçamento não podem ser negativos.');
                $this->redirect('/admin/editarOrcamento/' . (int)$id);
            }

            $dados['valor_pecas'] = $valor_pecas;
            $dados['valor_mao_obra'] = $valor_mao_obra;
            $dados['valor_total'] = $valor_pecas + $valor_mao_obra;
        }

        try {
            $orcamentoModel->updateBudget($id, $dados);
            $this->flash('success', 'Orçamento atualizado com sucesso.');
        } catch (\Throwable $e) {
            error_log('Erro ao atualizar orçamento #' . (int)$id . ': ' . $e->getMessage());
            $this->flash('error', 'Não foi possível salvar as alterações.');
        }

        $this->redirect('/admin/editarOrcamento/' . (int)$id);
    }

    public function imprimirOrcamento($id) {
        return $this->imprimirOrcamentoNovo($id);
    }

    public function enviarEmailOrcamento($id) {
        $this->requirePost();

        $orcamentoModel = new OrcamentoModel();
        $orcamento = $orcamentoModel->getBudgetById($id);

        if (!$orcamento) {
            $this->flash('error', 'Orçamento não encontrado.');
            $this->redirect('/admin/orcamentos');
        }

        if (empty($orcamento['cliente_email'])) {
            $this->flash('error', 'O cliente não possui e-mail cadastrado.');
            $this->redirect('/admin/editarOrcamento/' . (int)$id);
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $link = $scheme . "://" . $_SERVER['HTTP_HOST'] . BASE_URL . "/auth/autorizarOrcamento/" . rawurlencode($orcamento['token_autorizacao']);
        $mensagem = "<p>Uma nova proposta comercial / orçamento foi registrada para você.</p>";
        $mensagem .= "<p>Você pode visualizar o Escopo, Valores e <strong>Aprovar ou Rejeitar</strong> o orçamento acessando o link abaixo:</p>";
        $mensagem .= "<p><a href='" . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . "' style='display:inline-block; padding:10px 20px; background-color:#1e3a8a; color:#fff; text-decoration:none; border-radius:5px;'>Visualizar e Responder Orçamento</a></p>";

        $enviado = \app\Helpers\MailHelper::enviarEmail($orcamento['cliente_email'], $orcamento['cliente_nome'], "Proposta Comercial / Orçamento #" . (int)$id, $mensagem);
        $this->flash($enviado ? 'success' : 'error', $enviado ? 'E-mail enviado ao cliente.' : 'Não foi possível enviar o e-mail.');

        $this->redirect('/admin/editarOrcamento/' . (int)$id);
    }

    public function excluirOrcamento($id) {
        $this->requirePost();

        if ($_SESSION['user_type'] !== 'admin') {
            $this->flash('error', 'Apenas administradores podem excluir orçamentos.');
            $this->redirect('/admin/orcamentos');
        }

        $orcamentoModel = new OrcamentoModel();
        $orcamento = $orcamentoModel->getBudgetById($id);

        if (!$orcamento) {
            $this->flash('error', 'Orçamento não encontrado.');
        } elseif ($orcamento['status'] === 'aprovado') {
            $this->flash('error', 'Orçamentos aprovados não podem ser excluídos.');
        } elseif ($orcamentoModel->deleteBudget($id)) {
            $this->flash('success', 'Orçamento excluído com sucesso.');
        } else {
            $this->flash('error', 'Não foi possível excluir o orçamento.');
        }

        $this->redirect('/admin/orcamentos');
    }

    // ==========================================
    // DECISÃO DE ORÇAMENTO (APROVAR / REJEITAR)
    // ==========================================

    public function aprovarOrcamento($id) {
        $this->decidirOrcamento($id, OrcamentoModel::DECISION_APPROVE);
    }

    public function rejeitarOrcamento($id) {
        $this->decidirOrcamento($id, OrcamentoModel::DECISION_REJECT);
    }

    private function decidirOrcamento($id, $decision) {
        $this->requirePost();

        $motivo = $decision === OrcamentoModel::DECISION_REJECT
            ? Security::sanitizeInput($_POST['motivo'] ?? '')
            : null;

        $orcamentoModel = new OrcamentoModel();
        $resultado = $orcamentoModel->decide($id, $decision, $_SESSION['user_id'], $motivo);

        if ($this->isAjax()) {
            $this->respondBudgetDecision($resultado);
        }

        $this->flashBudgetDecision($resultado);
        $this->redirect('/admin/orcamentos');
    }

    public function reativarOrcamento($id) {
        $this->requirePost();

        $orcamentoModel = new OrcamentoModel();
        if ($orcamentoModel->reactivateBudget($id)) {
            $this->flash('success', 'Orçamento reativado por mais 30 dias.');
        } else {
            $this->flash('warning', 'Apenas orçamentos expirados podem ser reativados.');
        }

        $this->redirect('/admin/editarOrcamento/' . (int)$id);
    }

    public function servicoAvulso() {
        $userModel = new UserModel();
        $this->view('admin/servico_avulso', [
            'title' => 'Abertura de Serviço Avulso',
            'clientes' => $userModel->getAllClients(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarServicoAvulso() {
        $this->requirePost();

        $dados = [
            'cliente_id' => $_POST['cliente_id'] ?? null,
            'tipo_servico' => 'Servico Avulso',
            'descricao' => Security::sanitizeInput($_POST['descricao'] ?? '')
        ];

        if ($dados['cliente_id'] && $dados['descricao']) {
            $chamadoModel = new ChamadoModel();
            $chamadoModel->createTicket($dados);
        }

        $this->redirect('/admin/chamados');
    }

    // ==========================================
    // MÓDULO 6: GESTÃO DE USUÁRIOS E PERMISSÕES
    // ==========================================

    public function usuarios() {
        $userModel = new UserModel();
        $this->view('admin/usuarios_list', [
            'title' => 'Gestão de Usuários',
            'usuarios' => $userModel->getAllUsers(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function novoUsuario() {
        $this->view('admin/novo_usuario', [
            'title' => 'Criar Novo Usuário',
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarUsuario() {
        $this->requirePost();

        $dados = [
            'nome' => Security::sanitizeInput($_POST['nome'] ?? ''),
            'cpf_cnpj' => Security::sanitizeInput($_POST['cpf_cnpj'] ?? ''),
            'email' => Security::sanitizeInput($_POST['email'] ?? ''),
            'telefone' => Security::sanitizeInput($_POST['telefone'] ?? ''),
            'perfil' => $_POST['perfil'] ?? 'tecnico'
        ];

        if (!in_array($dados['perfil'], self::PERFIS_FUNCIONARIO, true)) {
            die("ERRO: Perfil inválido.");
        }

        $senha = $_POST['senha'] ?? '';
        if (strlen($senha) < 8) {
            die("ERRO: A senha deve ter pelo menos 8 caracteres.");
        }
        $dados['senha_hash'] = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

        $dados['permissoes'] = $this->sanitizePermissoes($_POST['permissoes'] ?? []);

        $userModel = new UserModel();
        
        // Verifica se o CPF já existe ANTES de tentar cadastrar
        if ($userModel->getUserByCpfCnpj($dados['cpf_cnpj'])) {
            die("ERRO: Este CPF ou CNPJ (" . htmlspecialchars($dados['cpf_cnpj'], ENT_QUOTES, 'UTF-8') . ") já está cadastrado no sistema para outro usuário. Por favor, volte e use um CPF diferente.");
        }

        // Verifica se o E-mail já existe ANTES de tentar cadastrar
        if (!empty($dados['email']) && $userModel->getUserByEmail($dados['email'])) {
            die("ERRO: O E-mail (" . htmlspecialchars($dados['email'], ENT_QUOTES, 'UTF-8') . ") já está cadastrado no sistema. Não é permitido usar o mesmo e-mail para contas diferentes. Por favor, volte e use um e-mail diferente.");
        }
        
        if (!$userModel->createUserByAdmin($dados)) {
            die("Erro crítico ao tentar salvar o usuário no banco de dados. Verifique as configurações das colunas ou se faltou algum campo.");
        }

        $this->redirect('/admin/usuarios');
    }

    public function editarUsuario($id) {
        $userModel = new UserModel();
        $usuario = $userModel->getUserById($id);

        // Se o usuário não existir ou for cliente (cliente tem edição própria)
        if (!$usuario || $usuario['perfil'] === 'cliente') {
            $this->redirect('/admin/usuarios');
            return;
        }

        $this->view('admin/editar_usuario', [
            'title' => 'Editar Funcionário',
            'usuario' => $usuario,
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarEdicaoUsuario($id) {
        $this->requirePost();

        $dados = [
            'nome' => Security::sanitizeInput($_POST['nome'] ?? ''),
            'email' => Security::sanitizeInput($_POST['email'] ?? ''),
            'telefone' => Security::sanitizeInput($_POST['telefone'] ?? ''),
            'cep' => Security::sanitizeInput($_POST['cep'] ?? ''),
            'logradouro' => Security::sanitizeInput($_POST['logradouro'] ?? ''),
            'numero' => Security::sanitizeInput($_POST['numero'] ?? ''),
            'complemento' => Security::sanitizeInput($_POST['complemento'] ?? ''),
            'bairro' => Security::sanitizeInput($_POST['bairro'] ?? ''),
            'cidade' => Security::sanitizeInput($_POST['cidade'] ?? ''),
            'estado' => Security::sanitizeInput($_POST['estado'] ?? ''),
            'perfil' => $_POST['perfil'] ?? 'tecnico'
        ];

        if (!in_array($dados['perfil'], self::PERFIS_FUNCIONARIO, true)) {
            die("ERRO: Perfil inválido.");
        }

        // Evita que o único administrador logado se rebaixe e perca o acesso.
        if ($id == $_SESSION['user_id'] && $dados['perfil'] !== 'admin' && $_SESSION['user_type'] === 'admin') {
            die("ERRO: Você não pode remover seu próprio perfil de administrador.");
        }

        // Se preencheu a senha, gera o hash novo. Se não preencheu, ignora e mantém a atual.
        if (!empty($_POST['senha'])) {
            if (strlen($_POST['senha']) < 8) {
                die("ERRO: A senha deve ter pelo menos 8 caracteres.");
            }
            $dados['senha_hash'] = password_hash($_POST['senha'], PASSWORD_BCRYPT, ['cost' => 12]);
        } else {
            $dados['senha_hash'] = null;
        }

        $dados['permissoes'] = $this->sanitizePermissoes($_POST['permissoes'] ?? []);

        $userModel = new UserModel();
        $usuarioAtual = $userModel->getUserById($id);

        if (!$usuarioAtual) {
            $this->redirect('/admin/usuarios');
        }

        // Verifica duplicidade de E-mail se estiver mudando o e-mail
        if (!empty($dados['email']) && $dados['email'] !== $usuarioAtual['email']) {
            if ($userModel->getUserByEmail($dados['email'])) {
                die("ERRO: O E-mail (" . htmlspecialchars($dados['email'], ENT_QUOTES, 'UTF-8') . ") já está cadastrado no sistema.");
            }
        }

        $userModel->updateUsuario($id, $dados);

        $this->redirect('/admin/usuarios');
    }

    public function index() {
        $chamadoModel = new ChamadoModel();
        $totalChamados = $chamadoModel->countAll();

        $this->view('dashboard/admin', [
            'title' => 'Visão Geral - Admin',
            'totalChamados' => $totalChamados
        ]);
    }

    // ==========================================
    // MÓDULO FINANCEIRO (CONTRATOS E NOTAS)
    // ==========================================

    public function financeiro() {
        $financeiroModel = new FinanceiroModel();
        
        $this->view('admin/financeiro_list', [
            'title' => 'Gestão de Contratos e Notas',
            'contratos' => $financeiroModel->getAllContratos(),
            'notas' => $financeiroModel->getAllNotas()
        ]);
    }

    public function novoContrato() {
        $userModel = new UserModel();
        $clientes = $userModel->getAllClients();

        $this->view('admin/novo_contrato', [
            'title' => 'Criar Novo Contrato',
            'clientes' => $clientes,
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarContrato() {
        $this->requirePost();

        $dados = [
            'cliente_id' => $_POST['cliente_id'] ?? '',
            'valor_mensal' => Security::parseMoney($_POST['valor_mensal'] ?? '0'),
            'data_inicio' => $_POST['data_inicio'] ?? '',
            'data_validade' => $_POST['data_validade'] ?? '',
            'prazo_renovacao_anos' => (int)($_POST['prazo_renovacao_anos'] ?? 1),
            'conteudo_sla' => Security::sanitizeInput($_POST['conteudo_sla'] ?? '')
        ];

        if (!empty($dados['cliente_id']) && !empty($dados['valor_mensal'])) {
            $financeiroModel = new FinanceiroModel();
            $financeiroModel->createContrato($dados);
        }

        $this->redirect('/admin/financeiro');
    }

    public function editarContrato($id) {
        $financeiroModel = new FinanceiroModel();
        $contrato = $financeiroModel->getContratoById($id);

        if (!$contrato) {
            $this->redirect('/admin/financeiro');
            return;
        }

        $this->view('admin/editar_contrato', [
            'title' => 'Editar Contrato #' . $id,
            'contrato' => $contrato,
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarEdicaoContrato($id) {
        $this->requirePost();

        $dados = [
            'valor_mensal' => Security::parseMoney($_POST['valor_mensal'] ?? '0'),
            'data_inicio' => $_POST['data_inicio'] ?? '',
            'data_validade' => $_POST['data_validade'] ?? '',
            'prazo_renovacao_anos' => (int)($_POST['prazo_renovacao_anos'] ?? 1),
            'conteudo_sla' => Security::sanitizeInput($_POST['conteudo_sla'] ?? ''),
            'status' => $_POST['status'] ?? 'ativo'
        ];

        $financeiroModel = new FinanceiroModel();
        $financeiroModel->updateContrato($id, $dados);

        $this->redirect('/admin/financeiro');
    }

    public function novaNota() {
        $userModel = new UserModel();
        $financeiroModel = new FinanceiroModel();

        $this->view('admin/nova_nota', [
            'title' => 'Emitir/Anexar Nota Fiscal',
            'clientes' => $userModel->getAllClients(),
            'contratos' => $financeiroModel->getAllContratos(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarNota() {
        $this->requirePost();

        $dados = [
            'cliente_id' => $_POST['cliente_id'] ?? '',
            'contrato_id' => !empty($_POST['contrato_id']) ? $_POST['contrato_id'] : null,
            'numero_nf' => Security::sanitizeInput($_POST['numero_nf'] ?? ''),
            'valor' => Security::parseMoney($_POST['valor'] ?? '0'),
            'data_emissao' => $_POST['data_emissao'] ?? ''
        ];

        if (empty($dados['cliente_id']) || empty($dados['numero_nf']) || empty($_FILES['arquivo_nf']['name'])) {
            $this->redirect('/admin/novaNota');
            return;
        }

        $upload = UploadHelper::processInvoiceUpload($_FILES['arquivo_nf']);

        if (isset($upload['success'])) {
            $dados['arquivo_url'] = $upload['success'];
            $financeiroModel = new FinanceiroModel();
            $financeiroModel->createNotaFiscal($dados);
            $this->redirect('/admin/financeiro');
        } else {
            // Em produção, exibir erro na tela
            $this->redirect('/admin/novaNota');
        }
    }

    public function editarNota($id) {
        $financeiroModel = new FinanceiroModel();
        $nota = $financeiroModel->getNotaById($id);

        if (!$nota) {
            $this->redirect('/admin/financeiro');
            return;
        }

        $this->view('admin/editar_nota', [
            'title' => 'Editar Nota Fiscal #' . $nota['numero_nf'],
            'nota' => $nota,
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarEdicaoNota($id) {
        $this->requirePost();

        $dados = [
            'numero_nf' => Security::sanitizeInput($_POST['numero_nf'] ?? ''),
            'valor' => Security::parseMoney($_POST['valor'] ?? '0'),
            'data_emissao' => $_POST['data_emissao'] ?? ''
        ];

        // Se enviou um novo arquivo para substituir
        if (!empty($_FILES['arquivo_nf']['name'])) {
            $upload = UploadHelper::processInvoiceUpload($_FILES['arquivo_nf']);
            if (isset($upload['success'])) {
                $dados['arquivo_url'] = $upload['success'];
            }
        }

        $financeiroModel = new FinanceiroModel();
        $financeiroModel->updateNotaFiscal($id, $dados);

        $this->redirect('/admin/financeiro');
    }

    // ==========================================
    // MÓDULO FINANCEIRO CONTÁBIL
    // ==========================================

    public function contabil() {
        $contabilModel = new ContabilModel();
        
        $this->view('admin/contabil_list', [
            'title' => 'Visão Contábil',
            'balanco' => $contabilModel->getBalançoGeral(),
            'lancamentos' => $contabilModel->getAllLancamentos(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function novoLancamentoContabil() {
        $this->requirePost();

        $dados = [
            'tipo' => $_POST['tipo'] ?? 'receita',
            'descricao' => Security::sanitizeInput($_POST['descricao'] ?? ''),
            'valor' => Security::parseMoney($_POST['valor'] ?? '0'),
            'data_vencimento' => $_POST['data_vencimento'] ?? date('Y-m-d'),
            'status' => $_POST['status'] ?? 'pendente',
            'cliente_fornecedor_id' => !empty($_POST['cliente_fornecedor_id']) ? $_POST['cliente_fornecedor_id'] : null,
            'ticket_id' => !empty($_POST['ticket_id']) ? $_POST['ticket_id'] : null,
            'data_pagamento' => (($_POST['status'] ?? '') === 'pago') ? date('Y-m-d') : null
        ];

        $contabilModel = new ContabilModel();
        $contabilModel->criarLancamento($dados);

        $this->redirect('/admin/contabil');
    }

    public function alterarStatusLancamento($id) {
        $this->requirePost();
        
        $status = $_POST['status'] ?? 'pendente';
        $data_pagamento = ($status === 'pago') ? date('Y-m-d') : null;

        $contabilModel = new ContabilModel();
        $contabilModel->atualizarStatus($id, $status, $data_pagamento);

        $this->redirect('/admin/contabil');
    }

    // ==========================================
    // MÓDULO DE ENGENHARIA DE SOFTWARE
    // ==========================================

    public function projetos() {
        $projetoModel = new ProjetoModel();
        
        $this->view('admin/projetos_list', [
            'title' => 'Projetos de Software',
            'projetos' => $projetoModel->getAllProjetos()
        ]);
    }

    public function novoProjeto() {
        $userModel = new UserModel();
        
        $this->view('admin/novo_projeto', [
            'title' => 'Criar Novo Projeto',
            'clientes' => $userModel->getAllClients(),
            'funcionarios' => $userModel->getAllUsers(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarProjeto() {
        $this->requirePost();

        $dados = [
            'cliente_id' => $_POST['cliente_id'] ?? null,
            'engenheiro_id' => !empty($_POST['engenheiro_id']) ? $_POST['engenheiro_id'] : null,
            'nome_projeto' => Security::sanitizeInput($_POST['nome_projeto'] ?? ''),
            'descricao' => Security::sanitizeInput($_POST['descricao'] ?? ''),
            'documentacao' => Security::sanitizeInput($_POST['documentacao'] ?? ''),
            'link_repositorio' => Security::sanitizeInput($_POST['link_repositorio'] ?? ''),
            'link_producao' => Security::sanitizeInput($_POST['link_producao'] ?? ''),
            'status' => $_POST['status'] ?? 'planejamento',
            'data_inicio' => !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null,
            'data_previsao_fim' => !empty($_POST['data_previsao_fim']) ? $_POST['data_previsao_fim'] : null
        ];

        if ($dados['cliente_id'] && $dados['nome_projeto']) {
            $projetoModel = new ProjetoModel();
            $projetoModel->criarProjeto($dados);
        }

        $this->redirect('/admin/projetos');
    }

    public function editarProjeto($id) {
        $projetoModel = new ProjetoModel();
        $userModel = new UserModel();
        
        $projeto = $projetoModel->getProjetoById($id);

        if (!$projeto) {
            $this->redirect('/admin/projetos');
            return;
        }

        $this->view('admin/editar_projeto', [
            'title' => 'Editar Projeto #' . $id,
            'projeto' => $projeto,
            'funcionarios' => $userModel->getAllUsers(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarEdicaoProjeto($id) {
        $this->requirePost();

        $dados = [
            'engenheiro_id' => !empty($_POST['engenheiro_id']) ? $_POST['engenheiro_id'] : null,
            'nome_projeto' => Security::sanitizeInput($_POST['nome_projeto'] ?? ''),
            'descricao' => Security::sanitizeInput($_POST['descricao'] ?? ''),
            'documentacao' => Security::sanitizeInput($_POST['documentacao'] ?? ''),
            'link_repositorio' => Security::sanitizeInput($_POST['link_repositorio'] ?? ''),
            'link_producao' => Security::sanitizeInput($_POST['link_producao'] ?? ''),
            'status' => $_POST['status'] ?? 'planejamento',
            'data_inicio' => !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null,
            'data_previsao_fim' => !empty($_POST['data_previsao_fim']) ? $_POST['data_previsao_fim'] : null
        ];

        $projetoModel = new ProjetoModel();
        $projetoModel->atualizarProjeto($id, $dados);

        $this->redirect('/admin/projetos');
    }

    // ==========================================
    // MÓDULO: FEED RÁPIDO (postagens sem página individual/slug)
    // ==========================================

    private const FEED_TEXTO_MAX = 5000;

    public function feed() {
        $feedModel = new FeedModel();

        $this->view('admin/feed_list', [
            'title' => 'Feed Rápido',
            'posts' => $feedModel->getAllPosts(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function novoFeedPost() {
        $this->view('admin/feed_form', [
            'title' => 'Nova Postagem do Feed',
            'post' => null,
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarFeedPost() {
        $this->requirePost();
        $this->gravarFeedPost(null);
    }

    public function editarFeedPost($id) {
        $feedModel = new FeedModel();
        $post = $feedModel->getPostById($id);

        if (!$post) {
            $this->flash('error', 'Postagem não encontrada.');
            $this->redirect('/admin/feed');
            return;
        }

        $this->view('admin/feed_form', [
            'title' => 'Editar Postagem #' . (int)$id,
            'post' => $post,
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarEdicaoFeedPost($id) {
        $this->requirePost();
        $this->gravarFeedPost((int)$id);
    }

    public function excluirFeedPost($id) {
        $this->requirePost();

        $feedModel = new FeedModel();
        $post = $feedModel->getPostById($id);

        if ($post) {
            $feedModel->deletePost($id);
            UploadHelper::deleteFeedMedia($post['caminho_midia']);
            $this->flash('success', 'Postagem excluída com sucesso.');
        } else {
            $this->flash('error', 'Postagem não encontrada.');
        }

        $this->redirect('/admin/feed');
    }

    /**
     * Cria ($id = null) ou atualiza uma postagem do feed, tratando o upload da mídia.
     * Em caso de erro de validação, reexibe o formulário preservando o que foi digitado.
     */
    private function gravarFeedPost($id) {
        $feedModel = new FeedModel();
        $atual = null;

        if ($id !== null) {
            $atual = $feedModel->getPostById($id);
            if (!$atual) {
                $this->flash('error', 'Postagem não encontrada.');
                $this->redirect('/admin/feed');
                return;
            }
        }

        $status = $_POST['status'] ?? 'publicado';
        $dados = [
            'texto_conteudo' => Security::sanitizeInput($_POST['texto_conteudo'] ?? ''),
            'status' => in_array($status, FeedModel::STATUS, true) ? $status : 'publicado',
            'tipo_midia' => $atual['tipo_midia'] ?? 'none',
            'caminho_midia' => $atual['caminho_midia'] ?? null
        ];

        $erro = null;
        $novoArquivo = null;
        $midiaAlterada = false;
        $temUpload = isset($_FILES['midia']) && ($_FILES['midia']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if (mb_strlen($dados['texto_conteudo'], 'UTF-8') > self::FEED_TEXTO_MAX) {
            $erro = 'O texto pode ter no máximo ' . self::FEED_TEXTO_MAX . ' caracteres.';
        } elseif ($temUpload) {
            $upload = UploadHelper::processFeedMedia($_FILES['midia']);
            if (isset($upload['error'])) {
                $erro = $upload['error'];
            } else {
                $novoArquivo = $upload['success']['path'];
                $dados['caminho_midia'] = $novoArquivo;
                $dados['tipo_midia'] = $upload['success']['tipo'];
                $midiaAlterada = true;
            }
        } elseif (!empty($_POST['remover_midia'])) {
            $dados['caminho_midia'] = null;
            $dados['tipo_midia'] = 'none';
            $midiaAlterada = true;
        }

        if (!$erro && $dados['texto_conteudo'] === '' && $dados['tipo_midia'] === 'none') {
            $erro = 'Escreva um texto ou envie uma imagem/vídeo para publicar.';
        }

        if ($erro) {
            if ($novoArquivo) {
                UploadHelper::deleteFeedMedia($novoArquivo);
            }
            $this->flash('error', $erro);
            $this->view('admin/feed_form', [
                'title' => $id === null ? 'Nova Postagem do Feed' : 'Editar Postagem #' . $id,
                'post' => array_merge($atual ?? [], $dados, $id === null ? [] : ['id' => $id], [
                    'caminho_midia' => $atual['caminho_midia'] ?? null,
                    'tipo_midia' => $atual['tipo_midia'] ?? 'none'
                ]),
                'csrf_token' => Security::generateCsrfToken()
            ]);
            return;
        }

        try {
            if ($id === null) {
                $feedModel->createPost($dados);
            } else {
                $feedModel->updatePost($id, $dados);
            }
        } catch (\Throwable $e) {
            if ($novoArquivo) {
                UploadHelper::deleteFeedMedia($novoArquivo);
            }
            throw $e;
        }

        if ($midiaAlterada && $atual) {
            UploadHelper::deleteFeedMedia($atual['caminho_midia']);
        }

        $this->flash('success', $id === null ? 'Postagem publicada no feed.' : 'Postagem atualizada com sucesso.');
        $this->redirect('/admin/feed');
    }

    // ==========================================
    // MÓDULO 5: CONFIGURAÇÕES E ATIVOS
    // ==========================================

    public function configuracoes() {
        $configModel = new ConfigModel();
        
        $this->view('admin/configuracoes', [
            'title' => 'Configurações Globais',
            'empresa' => $configModel->getCompanyInfo(),
            'fornecedores' => $configModel->getAllSuppliers(),
            'ativos' => $configModel->getAllAssets(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function empresa() {
        $this->redirect('/admin/configuracoes');
    }

    public function salvarEmpresa() {
        $this->requirePost();

        $dados = [
            'razao_social' => Security::sanitizeInput($_POST['razao_social'] ?? ''),
            'cnpj' => Security::sanitizeInput($_POST['cnpj'] ?? ''),
            'matriz_filial' => $_POST['matriz_filial'] ?? 'matriz',
            'telefone' => Security::sanitizeInput($_POST['telefone'] ?? ''),
            'email_contato' => Security::sanitizeInput($_POST['email_contato'] ?? ''),
            'endereco_completo' => Security::sanitizeInput($_POST['endereco_completo'] ?? '')
        ];

        if (!empty($_FILES['logo']['name']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $file = $_FILES['logo'];
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (isset($allowed[$mime]) && $file['size'] <= 5 * 1024 * 1024) {
                $newName = 'logo_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
                $uploadPath = APP_PATH . '/../uploads/company/';
                if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

                if (move_uploaded_file($file['tmp_name'], $uploadPath . $newName)) {
                    $dados['logo_url'] = $newName;
                }
            }
        }

        $configModel = new ConfigModel();
        $configModel->updateCompany($dados);

        $this->flash('success', 'Dados da empresa atualizados.');
        $this->redirect('/admin/configuracoes');
    }

    public function salvarFornecedor() {
        $this->requirePost();
        
        $dados = [
            'nome' => Security::sanitizeInput($_POST['nome'] ?? ''),
            'cnpj' => Security::sanitizeInput($_POST['cnpj'] ?? ''),
            'telefone' => Security::sanitizeInput($_POST['telefone'] ?? ''),
            'email' => Security::sanitizeInput($_POST['email'] ?? '')
        ];

        $configModel = new ConfigModel();
        $configModel->createSupplier($dados);

        $this->redirect('/admin/configuracoes');
    }

    public function novoAtivo() {
        $userModel = new UserModel();
        $configModel = new ConfigModel();

        $this->view('admin/novo_ativo', [
            'title' => 'Cadastrar Equipamento',
            'clientes' => $userModel->getAllClients(),
            'fornecedores' => $configModel->getAllSuppliers(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarAtivo() {
        $this->requirePost();

        $dados = [
            'cliente_id' => !empty($_POST['cliente_id']) ? $_POST['cliente_id'] : null,
            'fornecedor_id' => !empty($_POST['fornecedor_id']) ? $_POST['fornecedor_id'] : null,
            'nome_equipamento' => Security::sanitizeInput($_POST['nome_equipamento'] ?? ''),
            'numero_serie' => Security::sanitizeInput($_POST['numero_serie'] ?? ''),
            'data_compra' => $_POST['data_compra'] ?? null,
            'garantia_meses' => (int)($_POST['garantia_meses'] ?? 12)
        ];

        if (!empty($dados['nome_equipamento'])) {
            $configModel = new ConfigModel();
            $configModel->createAsset($dados);
        }

        $this->redirect('/admin/configuracoes');
    }

    // ==================== Dashboard ====================
    public function dashboard() {
        $dashboardController = new DashboardController();
        return $dashboardController->index();
    }

    // ==================== Budget Items Management ====================
    public function adicionarItemOrcamento() {
        $this->requirePost();

        $budget_id = (int)($_POST['budget_id'] ?? 0);
        $tipo = $_POST['tipo'] ?? 'peca';
        $descricao = Security::sanitizeInput($_POST['descricao'] ?? '');
        $quantidade = max(1, (int)($_POST['quantidade'] ?? 1));
        $valor_unitario = Security::parseMoney($_POST['valor_unitario'] ?? '0');

        $orcamentoModel = new OrcamentoModel();
        $orcamento = $budget_id ? $orcamentoModel->getBudgetById($budget_id) : null;

        if (!$orcamento) {
            $this->flash('error', 'Orçamento não encontrado.');
            $this->redirect('/admin/orcamentos');
        }

        if (!$orcamentoModel->isEditable($orcamento)) {
            $this->flash('warning', 'Este orçamento já foi decidido e não aceita novos itens.');
        } elseif ($descricao === '' || $valor_unitario <= 0 || !in_array($tipo, OrcamentoModel::ITEM_TYPES, true)) {
            $this->flash('error', 'Informe descrição, tipo e um valor unitário maior que zero.');
        } elseif ($orcamentoModel->addBudgetItem($budget_id, $tipo, $descricao, $quantidade, $valor_unitario)) {
            $this->flash('success', 'Item adicionado ao orçamento.');
        } else {
            $this->flash('error', 'Não foi possível adicionar o item.');
        }

        $this->redirect('/admin/editarOrcamento/' . $budget_id);
    }

    public function removerItemOrcamento($item_id) {
        $this->requirePost();

        $orcamentoModel = new OrcamentoModel();
        $item = $orcamentoModel->getBudgetItemById($item_id);

        if (!$item) {
            $this->flash('error', 'Item não encontrado.');
            $this->redirect('/admin/orcamentos');
        }

        $orcamento = $orcamentoModel->getBudgetById($item['budget_id']);
        if (!$orcamentoModel->isEditable($orcamento)) {
            $this->flash('warning', 'Este orçamento já foi decidido e não pode ser alterado.');
        } elseif ($orcamentoModel->deleteBudgetItem($item_id)) {
            $this->flash('success', 'Item removido do orçamento.');
        } else {
            $this->flash('error', 'Não foi possível remover o item.');
        }

        $this->redirect('/admin/editarOrcamento/' . (int)$item['budget_id']);
    }

    // ==================== Budget Print ====================
    public function imprimirOrcamentoNovo($id) {
        $orcamentoModel = new OrcamentoModel();
        $budget = $orcamentoModel->getBudgetById($id);

        if (!$budget) {
            http_response_code(404);
            die("Orçamento não encontrado.");
        }

        $budget_items = $orcamentoModel->getBudgetItems($id);

        $config = new ConfigModel();
        $company = $config->getCompanyInfo() ?: [];

        $logo_url = !empty($company['logo_url']) ? BASE_URL . '/uploads/company/' . rawurlencode($company['logo_url']) : null;
        $company_name = $company['razao_social'] ?? 'Sua Empresa';
        $company_cnpj = $company['cnpj'] ?? null;
        $company_phone = $company['telefone'] ?? '';
        $company_email = $company['email_contato'] ?? '';

        require_once APP_PATH . '/Views/admin/imprimir_orcamento.php';
    }

    // ==================== WhatsApp Approval ====================
    public function enviarWhatsAppOrcamento($id) {
        $orcamentoModel = new OrcamentoModel();
        $budget = $orcamentoModel->getBudgetById($id);

        if (!$budget || empty($budget['cliente_telefone'])) {
            $this->flash('error', 'Orçamento não encontrado ou cliente sem telefone.');
            $this->redirect('/admin/editarOrcamento/' . (int)$id);
            return;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $approval_link = $scheme . "://" . $_SERVER['HTTP_HOST'] . BASE_URL . '/auth/autorizarOrcamento/' . rawurlencode($budget['token_autorizacao']);

        $mensagem = "Olá " . $budget['cliente_nome'] . ",\n\n";
        $mensagem .= "Seu orçamento #" . (int)$id . " - *" . $budget['titulo'] . "* foi gerado.\n\n";
        $mensagem .= "💰 Valor Total: *R$ " . number_format($budget['valor_total'], 2, ',', '.') . "*\n";
        if (!empty($budget['data_validade'])) {
            $mensagem .= "📅 Válido até: *" . date('d/m/Y', strtotime($budget['data_validade'])) . "*\n\n";
        }
        $mensagem .= "Clique no link abaixo para visualizar, aprovar ou rejeitar o orçamento:\n";
        $mensagem .= $approval_link;

        $phone = preg_replace('/[^0-9]/', '', $budget['cliente_telefone']);
        if (strpos($phone, '55') !== 0) {
            $phone = '55' . $phone;
        }

        $this->redirectExternal("https://wa.me/{$phone}?text=" . rawurlencode($mensagem));
    }

    // ==================== Avulso Services ====================
    public function servicosAvulsos() {
        $avulsoModel = new AvulsoServiceModel();
        $this->view('admin/servicos_avulsos_list', [
            'title' => 'Serviços Avulsos',
            'servicos' => $avulsoModel->getAllServices(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function novoServicoAvulso() {
        $userModel = new UserModel();
        $this->view('admin/novo_servico_avulso', [
            'title' => 'Novo Serviço Avulso',
            'clientes' => $userModel->getAllClients(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarServicoAvulsoNovo() {
        $this->requirePost();

        $valor = (float)Security::parseMoney($_POST['valor'] ?? '0');
        
        $dados = [
            'cliente_id' => (int)($_POST['cliente_id'] ?? 0),
            'descricao' => Security::sanitizeInput($_POST['descricao'] ?? ''),
            'valor' => $valor,
            'data_servico' => $this->parseValidade($_POST['data_servico'] ?? '') ?? date('Y-m-d'),
            'status' => in_array($_POST['status'] ?? '', ['pendente', 'concluido', 'cancelado'], true) ? $_POST['status'] : 'pendente'
        ];

        if ($dados['cliente_id'] && $dados['descricao'] && $valor > 0) {
            $avulsoModel = new AvulsoServiceModel();
            $avulsoModel->createService($dados);
            $_SESSION['success'] = 'Serviço avulso cadastrado com sucesso!';
        } else {
            $_SESSION['error'] = 'Preencha todos os campos obrigatórios.';
        }

        $this->redirect('/admin/servicosAvulsos');
    }

    public function editarServicoAvulso($id) {
        $avulsoModel = new AvulsoServiceModel();
        $servico = $avulsoModel->getServiceById($id);

        if (!$servico) {
            $this->redirect('/admin/servicosAvulsos');
            return;
        }

        $this->view('admin/editar_servico_avulso', [
            'title' => 'Editar Serviço Avulso',
            'servico' => $servico,
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarEdicaoServicoAvulso($id) {
        $this->requirePost();

        $valor = (float)Security::parseMoney($_POST['valor'] ?? '0');
        
        $dados = [
            'descricao' => Security::sanitizeInput($_POST['descricao'] ?? ''),
            'valor' => $valor,
            'data_servico' => $this->parseValidade($_POST['data_servico'] ?? '') ?? date('Y-m-d'),
            'status' => in_array($_POST['status'] ?? '', ['pendente', 'concluido', 'cancelado'], true) ? $_POST['status'] : 'pendente'
        ];

        $avulsoModel = new AvulsoServiceModel();
        $avulsoModel->updateService($id, $dados);
        $_SESSION['success'] = 'Serviço avulso atualizado com sucesso!';

        $this->redirect('/admin/servicosAvulsos');
    }

    public function excluirServicoAvulso($id) {
        $this->requirePost();
        $avulsoModel = new AvulsoServiceModel();
        $avulsoModel->deleteService($id);
        $_SESSION['success'] = 'Serviço avulso excluído com sucesso!';
        $this->redirect('/admin/servicosAvulsos');
    }
}
