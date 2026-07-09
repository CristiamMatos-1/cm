<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/admin/financeiro" class="text-corpBlue-600 hover:text-corpBlue-800 font-medium text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Voltar para Gestão Financeira
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 max-w-3xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Criar Novo Contrato SLA</h2>

    <form action="<?= BASE_URL ?>/admin/salvarContrato" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
            <select name="cliente_id" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
                <option value="">Selecione o Cliente...</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?> (<?= htmlspecialchars($c['cpf_cnpj']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valor Mensal (R$)</label>
                <input type="text" name="valor_mensal" required placeholder="Ex: 1500,00" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prazo de Renovação</label>
                <select name="prazo_renovacao_anos" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="1">1 Ano</option>
                    <option value="2">2 Anos</option>
                    <option value="3">3 Anos</option>
                    <option value="4">4 Anos</option>
                    <option value="5">5 Anos</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Início</label>
                <input type="date" name="data_inicio" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Validade</label>
                <input type="date" name="data_validade" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Conteúdo do Contrato (SLA)</label>
            <textarea name="conteudo_sla" rows="6" placeholder="Redija as cláusulas principais do SLA..." class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-6 rounded hover:bg-indigo-700 transition-colors">
                <i class="fas fa-save mr-2"></i> Salvar Contrato
            </button>
        </div>
    </form>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>