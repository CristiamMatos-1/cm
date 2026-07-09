<?php
namespace app\Controllers;

use app\Models\ChamadoModel;
use app\Models\FinanceiroModel;
use app\Models\UserModel;
use app\Models\ConfigModel;
use app\Models\OrcamentoModel;
use app\Models\ReportModel;
use app\Models\ContabilModel;
use app\Models\ProjetoModel;
use app\Helpers\Security;
use app\Helpers\UploadHelper;

class AdminController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/auth');
            exit;
        }

        if ($_SESSION['user_type'] === 'cliente') {
            $this->redirect('/client');
            exit;
        }

        // Se for técnico, só permite acesso às rotas financeiras SE tiver a permissão "acesso_financeiro"
        if ($_SESSION['user_type'] === 'tecnico') {
            $url = isset($_GET['url']) ? filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL) : '';
            
            $userModel = new UserModel();
            $user = $userModel->getUserById($_SESSION['user_id']);
            $perms = json_decode($user['permissoes'] ?? '[]', true) ?: [];

            $permitido = false;

            // Módulo Financeiro
            if (strpos($url, 'admin/contabil') === 0 || strpos($url, 'admin/novoLancamentoContabil') === 0 || strpos($url, 'admin/alterarStatusLancamento') === 0) {
                if (in_array('acesso_financeiro', $perms)) $permitido = true;
            }
            
            // Módulo Orçamentos
            elseif (strpos($url, 'admin/orcamentos') === 0 || strpos($url, 'admin/novoOrcamento') === 0 || strpos($url, 'admin/editarOrcamento') === 0) {
                if (in_array('criar_orcamento', $perms)) $permitido = true;
            }

            // Módulo Relatórios
            elseif (strpos($url, 'admin/relatorios') === 0) {
                if (in_array('gerar_relatorios', $perms)) $permitido = true;
            }

            // Módulo Chamados
            elseif (strpos($url, 'admin/chamados') === 0 || strpos($url, 'admin/editarChamado') === 0) {
                if (in_array('abrir_chamado_admin', $perms)) $permitido = true;
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
            'chamados' => $chamadoModel->getAllChamados()
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
            'valor_pecas' => str_replace(',', '.', $_POST['valor_pecas'] ?? '0'),
            'valor_mao_obra' => str_replace(',', '.', $_POST['valor_mao_obra'] ?? '0'),
            'valor_servico' => str_replace(',', '.', $_POST['valor_servico'] ?? '0'),
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
            die("Chamado não encontrado.");
        }

        // Não carrega header/footer padrão, carrega a view de impressão limpa
        require_once APP_PATH . '/Views/admin/imprimir_chamado.php';
    }

    public function enviarEmailChamado($id) {
        $chamadoModel = new ChamadoModel();
        $chamado = $chamadoModel->getById($id);

        if ($chamado && !empty($chamado['cliente_email'])) {
            $link = "http://" . $_SERVER['HTTP_HOST'] . BASE_URL . "/admin/imprimirChamado/" . $id;
            $mensagem = "<p>Um novo serviço/chamado foi registrado e atualizado para você.</p>";
            $mensagem .= "<p>Você pode visualizar a Ordem de Serviço, o Escopo, Valores e <strong>Autorizar a Execução</strong> acessando o link abaixo:</p>";
            $mensagem .= "<p><a href='{$link}' style='display:inline-block; padding:10px 20px; background-color:#1e3a8a; color:#fff; text-decoration:none; border-radius:5px;'>Visualizar e Autorizar Serviço</a></p>";
            
            \app\Helpers\MailHelper::enviarEmail($chamado['cliente_email'], $chamado['cliente_nome'], "Acompanhamento de Serviço #" . $id, $mensagem);
        }

        $this->redirect('/admin/editarChamado/' . $id);
    }

    public function excluirChamado($id) {
        $chamadoModel = new ChamadoModel();
        $chamadoModel->deleteTicket($id);
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
        // Não permite excluir a si mesmo
        if ($id == $_SESSION['user_id']) {
            die("ERRO: Você não pode excluir a si mesmo.");
        }

        $userModel = new UserModel();
        $userModel->deleteUser($id);

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
        $this->view('admin/orcamentos_list', [
            'title' => 'Gestão de Orçamentos',
            'orcamentos' => $orcamentoModel->getAllBudgets()
        ]);
    }

    public function novoOrcamento() {
        $userModel = new UserModel();
        $chamadoModel = new ChamadoModel();
        
        $this->view('admin/novo_orcamento', [
            'title' => 'Criar Novo Orçamento',
            'clientes' => $userModel->getAllClients(),
            'chamados_abertos' => $chamadoModel->getAllChamados(), // Passa a lista para o select opcional
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarOrcamento() {
        $this->requirePost();

        $valor_pecas = (float)str_replace(',', '.', $_POST['valor_pecas'] ?? '0');
        $valor_mao_obra = (float)str_replace(',', '.', $_POST['valor_mao_obra'] ?? '0');
        $valor_total = $valor_pecas + $valor_mao_obra;

        $dados = [
            'cliente_id' => $_POST['cliente_id'] ?? null,
            'ticket_id' => !empty($_POST['ticket_id']) ? $_POST['ticket_id'] : null,
            'titulo' => Security::sanitizeInput($_POST['titulo'] ?? ''),
            'descricao' => Security::sanitizeInput($_POST['descricao'] ?? ''),
            'valor_pecas' => $valor_pecas,
            'valor_mao_obra' => $valor_mao_obra,
            'valor' => $valor_total
        ];

        if ($dados['cliente_id'] && $dados['titulo']) {
            $orcamentoModel = new OrcamentoModel();
            $orcamentoModel->createBudget($dados);
        }

        $this->redirect('/admin/orcamentos');
    }

    public function editarOrcamento($id) {
        $orcamentoModel = new OrcamentoModel();
        $chamadoModel = new ChamadoModel();
        
        $orcamento = $orcamentoModel->getBudgetById($id);
        
        if (!$orcamento) {
            $this->redirect('/admin/orcamentos');
            return;
        }

        $this->view('admin/editar_orcamento', [
            'title' => 'Editar Orçamento #' . $id,
            'orcamento' => $orcamento,
            'chamados_abertos' => $chamadoModel->getAllChamados(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
    }

    public function salvarEdicaoOrcamento($id) {
        $this->requirePost();

        $valor_pecas = (float)str_replace(',', '.', $_POST['valor_pecas'] ?? '0');
        $valor_mao_obra = (float)str_replace(',', '.', $_POST['valor_mao_obra'] ?? '0');
        $valor_total = $valor_pecas + $valor_mao_obra;

        $dados = [
            'titulo' => Security::sanitizeInput($_POST['titulo'] ?? ''),
            'descricao' => Security::sanitizeInput($_POST['descricao'] ?? ''),
            'valor_pecas' => $valor_pecas,
            'valor_mao_obra' => $valor_mao_obra,
            'valor' => $valor_total,
            'status' => $_POST['status'] ?? 'pendente',
            'ticket_id' => !empty($_POST['ticket_id']) ? $_POST['ticket_id'] : null,
            'autorizado_por' => !empty($_POST['autorizado_por']) ? $_POST['autorizado_por'] : null
        ];

        $orcamentoModel = new OrcamentoModel();
        
        // Se mudou para um status de aprovação, registra a data de autorização
        if (!empty($dados['autorizado_por']) && $dados['status'] !== 'pendente' && $dados['status'] !== 'rejeitado') {
            $dados['data_autorizacao'] = date('Y-m-d H:i:s');
        }

        $orcamentoModel->updateBudget($id, $dados);

        $this->redirect('/admin/orcamentos');
    }

    public function imprimirOrcamento($id) {
        $orcamentoModel = new OrcamentoModel();
        $orcamento = $orcamentoModel->getBudgetById($id);

        if (!$orcamento) {
            die("Orçamento não encontrado.");
        }

        // Não carrega header/footer padrão, carrega a view de impressão limpa
        require_once APP_PATH . '/Views/admin/imprimir_orcamento.php';
    }

    public function enviarEmailOrcamento($id) {
        $orcamentoModel = new OrcamentoModel();
        $userModel = new UserModel();
        
        $orcamento = $orcamentoModel->getBudgetById($id);
        
        if ($orcamento) {
            $cliente = $userModel->getUserById($orcamento['cliente_id']);
            
            if ($cliente && !empty($cliente['email'])) {
                $link = "http://" . $_SERVER['HTTP_HOST'] . BASE_URL . "/admin/imprimirOrcamento/" . $id;
                $mensagem = "<p>Uma nova proposta comercial / orçamento foi registrada para você.</p>";
                $mensagem .= "<p>Você pode visualizar o Escopo, Valores e <strong>Autorizar a Execução</strong> acessando o link abaixo:</p>";
                $mensagem .= "<p><a href='{$link}' style='display:inline-block; padding:10px 20px; background-color:#1e3a8a; color:#fff; text-decoration:none; border-radius:5px;'>Visualizar e Aprovar Orçamento</a></p>";
                
                \app\Helpers\MailHelper::enviarEmail($cliente['email'], $cliente['nome'], "Proposta Comercial / Orçamento #" . $id, $mensagem);
            }
        }

        $this->redirect('/admin/editarOrcamento/' . $id);
    }

    public function excluirOrcamento($id) {
        $orcamentoModel = new OrcamentoModel();
        $orcamentoModel->deleteBudget($id);
        $this->redirect('/admin/orcamentos');
    }

    public function alterarStatusOrcamento($id) {
        $this->requirePost();
        $status = $_POST['status'] ?? 'pendente';
        
        $orcamentoModel = new OrcamentoModel();
        $orcamentoModel->updateStatus($id, $status);

        $this->redirect('/admin/orcamentos');
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
            'usuarios' => $userModel->getAllUsers() // Mostra APENAS funcionários
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
            'perfil' => $_POST['perfil'] ?? 'cliente'
        ];

        $senha = $_POST['senha'] ?? '';
        $dados['senha_hash'] = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

        // Captura as permissões do formulário e transforma em JSON
        $permissoes = $_POST['permissoes'] ?? [];
        $dados['permissoes'] = json_encode($permissoes);

        $userModel = new UserModel();
        
        // Verifica se o CPF já existe ANTES de tentar cadastrar
        if ($userModel->getUserByCpfCnpj($dados['cpf_cnpj'])) {
            die("ERRO: Este CPF ou CNPJ (" . htmlspecialchars($dados['cpf_cnpj']) . ") já está cadastrado no sistema para outro usuário. Por favor, volte e use um CPF diferente.");
        }

        // Verifica se o E-mail já existe ANTES de tentar cadastrar
        if (!empty($dados['email']) && $userModel->getUserByEmail($dados['email'])) {
            die("ERRO: O E-mail (" . htmlspecialchars($dados['email']) . ") já está cadastrado no sistema. Não é permitido usar o mesmo e-mail para contas diferentes. Por favor, volte e use um e-mail diferente.");
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

        // Se preencheu a senha, gera o hash novo. Se não preencheu, ignora e mantém a atual.
        if (!empty($_POST['senha'])) {
            $dados['senha_hash'] = password_hash($_POST['senha'], PASSWORD_BCRYPT, ['cost' => 12]);
        } else {
            $dados['senha_hash'] = null;
        }

        $permissoes = $_POST['permissoes'] ?? [];
        $dados['permissoes'] = json_encode($permissoes);

        $userModel = new UserModel();
        $usuarioAtual = $userModel->getUserById($id);

        // Verifica duplicidade de E-mail se estiver mudando o e-mail
        if (!empty($dados['email']) && $dados['email'] !== $usuarioAtual['email']) {
            if ($userModel->getUserByEmail($dados['email'])) {
                die("ERRO: O E-mail (" . htmlspecialchars($dados['email']) . ") já está cadastrado no sistema.");
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
            'valor_mensal' => str_replace(',', '.', $_POST['valor_mensal'] ?? '0'),
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
            'valor_mensal' => str_replace(',', '.', $_POST['valor_mensal'] ?? '0'),
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
            'valor' => str_replace(',', '.', $_POST['valor'] ?? '0'),
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
            'valor' => str_replace(',', '.', $_POST['valor'] ?? '0'),
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
            'valor' => str_replace(',', '.', $_POST['valor'] ?? '0'),
            'data_vencimento' => $_POST['data_vencimento'] ?? date('Y-m-d'),
            'status' => $_POST['status'] ?? 'pendente',
            'cliente_fornecedor_id' => !empty($_POST['cliente_fornecedor_id']) ? $_POST['cliente_fornecedor_id'] : null,
            'ticket_id' => !empty($_POST['ticket_id']) ? $_POST['ticket_id'] : null,
            'data_pagamento' => ($_POST['status'] === 'pago') ? date('Y-m-d') : null
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
        $configModel = new ConfigModel();
        
        $this->view('admin/empresa_config', [
            'title' => 'Dados da Empresa',
            'empresa' => $configModel->getCompanyInfo(),
            'csrf_token' => Security::generateCsrfToken()
        ]);
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

        // Tratamento de Upload de Logo
        if (!empty($_FILES['logo']['name'])) {
            $file = $_FILES['logo'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (in_array($file['type'], $allowedTypes)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newName = 'logo_' . time() . '.' . $ext;
                $uploadPath = APP_PATH . '/../uploads/company/';
                if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath . $newName)) {
                    $dados['logo_url'] = $newName;
                }
            }
        }

        $configModel = new ConfigModel();
        $configModel->updateCompany($dados);

        $this->redirect('/admin/empresa');
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
}
