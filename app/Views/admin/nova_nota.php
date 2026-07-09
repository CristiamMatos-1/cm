<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/admin/financeiro" class="text-corpBlue-600 hover:text-corpBlue-800 font-medium text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Voltar para Gestão Financeira
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Enviar Nota Fiscal</h2>

    <form action="<?= BASE_URL ?>/admin/salvarNota" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
            <select name="cliente_id" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 outline-none">
                <option value="">Selecione o Cliente...</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Vincular a um Contrato (Opcional)</label>
            <select name="contrato_id" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 outline-none">
                <option value="">Nota Avulsa (Sem Contrato)</option>
                <?php foreach ($contratos as $c): ?>
                    <option value="<?= $c['id'] ?>">Contrato #<?= $c['id'] ?> - <?= htmlspecialchars($c['cliente_nome']) ?> (R$ <?= number_format($c['valor_mensal'], 2, ',', '.') ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número da NF *</label>
                <input type="text" name="numero_nf" required placeholder="Ex: 2024-001" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valor da NF (R$) *</label>
                <input type="text" name="valor" required placeholder="Ex: 500,00" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 outline-none">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Data de Emissão *</label>
            <input type="date" name="data_emissao" required class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 outline-none">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Arquivo da Nota (PDF ou XML) *</label>
            <input type="file" name="arquivo_nf" required accept="application/pdf, text/xml, application/xml" class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-50 focus:outline-none">
            <p class="text-xs text-gray-500 mt-1">Tamanho máximo: 20MB</p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-green-600 text-white font-bold py-2 px-6 rounded hover:bg-green-700 transition-colors">
                <i class="fas fa-upload mr-2"></i> Anexar NF
            </button>
        </div>
    </form>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>