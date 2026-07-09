<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<!-- Cards de Resumo -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-corpBlue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium">Total de Chamados</p>
                <p class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($totalChamados) ?></p>
            </div>
            <div class="p-3 bg-blue-50 text-corpBlue-600 rounded-full">
                <i class="fas fa-ticket-alt text-xl"></i>
            </div>
        </div>
    </div>
    
    <!-- Mais cards podem ser adicionados futuramente -->
</div>

<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-4">Visão Geral Administrativa</h2>
    <p class="text-gray-600">Bem-vindo ao painel administrativo. A partir daqui você poderá gerenciar chamados, clientes, configurações e faturamento.</p>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>