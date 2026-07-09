<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/admin/configuracoes" class="text-corpBlue-600 hover:text-corpBlue-800 font-medium text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Voltar
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Cadastrar Equipamento / Ativo</h2>

    <form action="<?= BASE_URL ?>/admin/salvarAtivo" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Equipamento *</label>
            <input type="text" name="nome_equipamento" required placeholder="Ex: Servidor Dell PowerEdge R740" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número de Série / Service Tag</label>
                <input type="text" name="numero_serie" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Compra</label>
                <input type="date" name="data_compra" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fornecedor da Peça/Equipamento</label>
                <select name="fornecedor_id" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">Não especificado...</option>
                    <?php foreach ($fornecedores as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Garantia (Meses)</label>
                <input type="number" name="garantia_meses" value="12" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
        </div>

        <div class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-lg">
            <label class="block text-sm font-bold text-corpBlue-900 mb-2">Proprietário do Equipamento</label>
            <select name="cliente_id" class="w-full px-4 py-2 border border-blue-300 rounded focus:ring-2 focus:ring-corpBlue-500 outline-none">
                <option value="">Nossa Empresa (Patrimônio Próprio)</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= $c['id'] ?>">Cliente: <?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-blue-600 mt-2">Se deixar em branco, o sistema entenderá que este equipamento pertence à sua empresa prestadora de serviços.</p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-6 rounded hover:bg-indigo-700 transition-colors">
                <i class="fas fa-save mr-2"></i> Salvar Equipamento
            </button>
        </div>
    </form>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>