<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<!-- Cards de Resumo -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-corpBlue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium">Meus Chamados Atribuídos</p>
                <p class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($totalMeusChamados) ?></p>
            </div>
            <div class="p-3 bg-blue-50 text-corpBlue-600 rounded-full">
                <i class="fas fa-tools text-xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-4">Acesso Técnico</h2>
    <p class="text-gray-600">Nesta área você poderá realizar triagem, usar a IA Gemini para análises, gerar relatórios de execução e interagir com o cliente.</p>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>