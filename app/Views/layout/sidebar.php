<?php
$userType = $_SESSION['user_type'] ?? 'cliente';
?>
<!-- Mobile Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-gray-800 bg-opacity-50 z-20 hidden md:hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="bg-corpBlue-900 text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out z-30 flex flex-col">
    <!-- Logo -->
    <div class="flex items-center space-x-2 px-4 mb-6">
        <i class="fas fa-headset text-2xl text-blue-300"></i>
        <span class="text-2xl font-extrabold tracking-wider">ITSM<span class="text-blue-300">Pro</span></span>
    </div>

    <!-- Nav Links -->
    <nav class="flex-1">
        <?php if ($userType === 'cliente'): ?>
            <a href="<?= BASE_URL ?>/client" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white <?= (strpos($_SERVER['REQUEST_URI'], '/client') !== false && strpos($_SERVER['REQUEST_URI'], '/chamados') === false) ? 'bg-corpBlue-700' : '' ?>">
                <i class="fas fa-home mr-2 w-5 text-center"></i> Meu Painel
            </a>
            <a href="<?= BASE_URL ?>/client/chamados" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-ticket-alt mr-2 w-5 text-center"></i> Meus Chamados
            </a>
            <a href="<?= BASE_URL ?>/client/orcamentos" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-hand-holding-usd mr-2 w-5 text-center"></i> Meus Orçamentos
            </a>
            <a href="<?= BASE_URL ?>/client/contratos" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-file-signature mr-2 w-5 text-center"></i> Contratos & NF
            </a>
            <a href="<?= BASE_URL ?>/client/patrimonio" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-desktop mr-2 w-5 text-center"></i> Meu Patrimônio
            </a>
        
        <?php elseif($_SESSION['user_type'] === 'tecnico'): ?>
            <a href="<?= BASE_URL ?>/tech" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-home mr-2 w-5 text-center"></i> Início
            </a>
            <a href="<?= BASE_URL ?>/tech/chamados" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-list mr-2 w-5 text-center"></i> Fila de Chamados
            </a>
            
            <?php 
                $userModel = new \app\Models\UserModel();
                $user = $userModel->getUserById($_SESSION['user_id']);
                $perms = json_decode($user['permissoes'] ?? '[]', true) ?: [];
            ?>

            <?php if(in_array('abrir_chamado_admin', $perms)): ?>
            <a href="<?= BASE_URL ?>/admin/chamados" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-headset mr-2 w-5 text-center"></i> Todos os Chamados
            </a>
            <?php endif; ?>

            <?php if(in_array('criar_orcamento', $perms)): ?>
            <a href="<?= BASE_URL ?>/admin/orcamentos" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-hand-holding-usd mr-2 w-5 text-center"></i> Orçamentos / Avulsos
            </a>
            <?php endif; ?>

            <?php if(in_array('acesso_financeiro', $perms)): ?>
            <a href="<?= BASE_URL ?>/admin/contabil" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-file-invoice-dollar mr-2 w-5 text-center"></i> Financeiro Contábil
            </a>
            <?php endif; ?>

            <?php if(in_array('gerar_relatorios', $perms)): ?>
            <a href="<?= BASE_URL ?>/admin/relatorios" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-chart-bar mr-2 w-5 text-center"></i> Relatórios
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/tech/relatorios" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-chart-line mr-2 w-5 text-center"></i> Meus Relatórios
            </a>

        <?php elseif ($userType === 'admin'): ?>
            <a href="<?= BASE_URL ?>/admin" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-chart-pie mr-2 w-5 text-center"></i> Visão Geral
            </a>
            <a href="<?= BASE_URL ?>/admin/chamados" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-tasks mr-2 w-5 text-center"></i> Todos Chamados
            </a>
            <a href="<?= BASE_URL ?>/admin/servicoAvulso" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white text-sm pl-8">
                <i class="fas fa-plus-circle mr-2 w-4 text-center"></i> Criar Serv. Avulso
            </a>
            <a href="<?= BASE_URL ?>/admin/orcamentos" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-hand-holding-usd mr-2 w-5 text-center"></i> Orçamentos
            </a>
            <a href="<?= BASE_URL ?>/admin/relatorios" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-print mr-2 w-5 text-center"></i> Relatórios e Balanço
            </a>
            <a href="<?= BASE_URL ?>/admin/clientes" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-users mr-2 w-5 text-center"></i> Clientes
            </a>
            <a href="<?= BASE_URL ?>/admin/projetos" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-project-diagram mr-2 w-5 text-center"></i> Eng. de Software
            </a>
            <a href="<?= BASE_URL ?>/admin/financeiro" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-file-signature mr-2 w-5 text-center"></i> Contratos e NF
            </a>
            <a href="<?= BASE_URL ?>/admin/contabil" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-file-invoice-dollar mr-2 w-5 text-center"></i> Financeiro Contábil
            </a>
            <a href="<?= BASE_URL ?>/admin/usuarios" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-user-shield mr-2 w-5 text-center"></i> Usuários & Permissões
            </a>
            <a href="<?= BASE_URL ?>/admin/configuracoes" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-corpBlue-700 hover:text-white">
                <i class="fas fa-cogs mr-2 w-5 text-center"></i> Configurações
            </a>
        <?php endif; ?>
    </nav>

    <!-- Footer Profile -->
    <div class="px-4 py-2 border-t border-corpBlue-700 text-sm">
        <p class="text-blue-200">Logado como:</p>
        <p class="font-bold truncate"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></p>
        <p class="text-xs text-blue-400 uppercase mt-1"><?= htmlspecialchars($userType) ?></p>
    </div>
</aside>