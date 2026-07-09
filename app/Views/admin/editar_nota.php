<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Editar Nota Fiscal #<?= htmlspecialchars($nota['numero_nf']) ?></h2>
        <a href="<?= BASE_URL ?>/admin/financeiro" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="<?= BASE_URL ?>/admin/salvarEdicaoNota/<?= $nota['id'] ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente (Leitura)</label>
                    <input type="text" value="<?= htmlspecialchars($nota['cliente_nome']) ?>" readonly class="w-full px-3 py-2 border border-gray-200 bg-gray-50 text-gray-500 rounded cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número da NF *</label>
                    <input type="text" name="numero_nf" value="<?= htmlspecialchars($nota['numero_nf']) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor da Nota (R$) *</label>
                    <input type="text" name="valor" value="<?= number_format($nota['valor'], 2, ',', '.') ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="0,00">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Emissão *</label>
                    <input type="date" name="data_emissao" value="<?= $nota['data_emissao'] ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Substituir Arquivo da NF (PDF/XML)</label>
                    <input type="file" name="arquivo_nf" accept=".pdf,.xml" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-corpBlue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">Selecione um arquivo APENAS se desejar substituir o arquivo atual salvo.</p>
                    
                    <?php if(!empty($nota['arquivo_url'])): ?>
                        <div class="mt-3 p-3 bg-gray-50 border border-gray-200 rounded flex items-center justify-between">
                            <span class="text-sm text-gray-600"><i class="fas fa-file-pdf text-red-500 mr-2"></i> Arquivo atual anexado</span>
                            <a href="<?= BASE_URL . '/' . htmlspecialchars($nota['arquivo_url']) ?>" target="_blank" class="text-sm text-corpBlue-600 hover:text-corpBlue-800 font-medium">Visualizar Documento</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-corpBlue-600 text-white px-6 py-2 rounded shadow hover:bg-corpBlue-700 transition-colors">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
