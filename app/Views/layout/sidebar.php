<?php
$userType = $_SESSION['user_type'] ?? 'cliente';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if (strpos($requestPath, BASE_URL) === 0) {
    $requestPath = substr($requestPath, strlen(BASE_URL));
}
$pathParts = array_values(array_filter(explode('/', strtolower(trim($requestPath, '/')))));
$currentController = $pathParts[0] ?? '';
$currentAction = $pathParts[1] ?? 'index';

// [href, ícone, rótulo, controlador, [ações que mantêm o item ativo]]
$menu = [];

if ($userType === 'cliente') {
    $menu = [
        ['/client', 'fa-home', 'Meu Painel', 'client', ['index']],
        ['/client/chamados', 'fa-ticket-alt', 'Meus Chamados', 'client', ['chamados', 'verchamado', 'novochamado']],
        ['/client/orcamentos', 'fa-hand-holding-usd', 'Meus Orçamentos', 'client', ['orcamentos']],
        ['/client/contratos', 'fa-file-signature', 'Contratos & NF', 'client', ['contratos']],
        ['/client/patrimonio', 'fa-desktop', 'Meu Patrimônio', 'client', ['patrimonio']],
    ];
} elseif ($userType === 'tecnico') {
    $perms = [];
    $userModel = new \app\Models\UserModel();
    $loggedUser = $userModel->getUserById($_SESSION['user_id']);
    $perms = json_decode($loggedUser['permissoes'] ?? '[]', true) ?: [];

    $menu[] = ['/tech', 'fa-home', 'Início', 'tech', ['index']];
    $menu[] = ['/tech/chamados', 'fa-list', 'Fila de Chamados', 'tech', ['chamados', 'chamadoview']];
    if (in_array('abrir_chamado_admin', $perms, true)) {
        $menu[] = ['/admin/chamados', 'fa-headset', 'Todos os Chamados', 'admin', ['chamados', 'editarchamado']];
    }
    if (in_array('criar_orcamento', $perms, true)) {
        $menu[] = ['/admin/orcamentos', 'fa-hand-holding-usd', 'Orçamentos', 'admin', ['orcamentos', 'novoorcamento', 'editarorcamento']];
    }
    if (in_array('acesso_financeiro', $perms, true)) {
        $menu[] = ['/admin/contabil', 'fa-file-invoice-dollar', 'Financeiro Contábil', 'admin', ['contabil']];
    }
    if (in_array('gerar_relatorios', $perms, true)) {
        $menu[] = ['/admin/relatorios', 'fa-chart-bar', 'Relatórios', 'admin', ['relatorios']];
    }
} elseif ($userType === 'admin') {
    $menu = [
        ['/admin', 'fa-chart-pie', 'Visão Geral', 'admin', ['index', 'dashboard']],
        ['/admin/chamados', 'fa-tasks', 'Todos Chamados', 'admin', ['chamados', 'editarchamado']],
        ['/admin/servicoAvulso', 'fa-plus-circle', 'Criar Chamado Avulso', 'admin', ['servicoavulso']],
        ['/admin/servicosAvulsos', 'fa-concierge-bell', 'Serviços Avulsos', 'admin', ['servicosavulsos', 'novoservicoavulso', 'editarservicoavulso']],
        ['/admin/orcamentos', 'fa-hand-holding-usd', 'Orçamentos', 'admin', ['orcamentos', 'novoorcamento', 'editarorcamento']],
        ['/admin/relatorios', 'fa-print', 'Relatórios e Balanço', 'admin', ['relatorios']],
        ['/admin/clientes', 'fa-users', 'Clientes', 'admin', ['clientes', 'editarcliente']],
        ['/admin/projetos', 'fa-project-diagram', 'Eng. de Software', 'admin', ['projetos', 'novoprojeto', 'editarprojeto']],
        ['/admin/financeiro', 'fa-file-signature', 'Contratos e NF', 'admin', ['financeiro', 'novocontrato', 'editarcontrato', 'novanota', 'editarnota']],
        ['/admin/contabil', 'fa-file-invoice-dollar', 'Financeiro Contábil', 'admin', ['contabil']],
        ['/admin/usuarios', 'fa-user-shield', 'Usuários & Permissões', 'admin', ['usuarios', 'novousuario', 'editarusuario']],
        ['/admin/configuracoes', 'fa-cogs', 'Configurações', 'admin', ['configuracoes', 'novoativo']],
    ];
}
?>
<!-- Mobile Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-gray-900/50 z-20 hidden md:hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="bg-corpBlue-900 text-white w-64 py-6 px-2 absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out z-30 flex flex-col shrink-0">
    <div class="flex items-center space-x-2 px-4 mb-6">
        <i class="fas fa-headset text-2xl text-blue-300"></i>
        <span class="text-2xl font-extrabold tracking-wider">ITSM<span class="text-blue-300">Pro</span></span>
    </div>

    <nav class="flex-1 overflow-y-auto space-y-1" aria-label="Menu principal">
        <?php foreach ($menu as [$href, $icon, $label, $controller, $actions]):
            $active = ($currentController === $controller && in_array($currentAction, $actions, true));
        ?>
            <a href="<?= BASE_URL . $href ?>"
               class="flex items-center py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white text-sm <?= $active ? 'bg-corpBlue-700 font-semibold' : 'text-blue-50' ?>"
               <?= $active ? 'aria-current="page"' : '' ?>>
                <i class="fas <?= $icon ?> mr-3 w-5 text-center"></i> <?= htmlspecialchars($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="px-4 py-3 mt-4 border-t border-corpBlue-700 text-sm">
        <p class="text-blue-200 text-xs">Logado como:</p>
        <p class="font-bold truncate"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></p>
        <p class="text-xs text-blue-300 uppercase mt-1"><?= htmlspecialchars($userType) ?></p>
    </div>
</aside>
