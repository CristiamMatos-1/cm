<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Editar Contrato #<?= $contrato['id'] ?></h2>
        <a href="<?= BASE_URL ?>/admin/financeiro" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="<?= BASE_URL ?>/admin/salvarEdicaoContrato/<?= $contrato['id'] ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente (Leitura)</label>
                    <input type="text" value="<?= htmlspecialchars($contrato['cliente_nome']) ?>" readonly class="w-full px-3 py-2 border border-gray-200 bg-gray-50 text-gray-500 rounded cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor Mensal (R$) *</label>
                    <input type="text" name="valor_mensal" value="<?= number_format($contrato['valor_mensal'], 2, ',', '.') ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="0,00">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status do Contrato</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                        <option value="ativo" <?= ($contrato['status'] ?? 'ativo') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inativo" <?= ($contrato['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo / Cancelado</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Início *</label>
                    <input type="date" name="data_inicio" value="<?= $contrato['data_inicio'] ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data de Validade/Fim *</label>
                    <input type="date" name="data_validade" value="<?= $contrato['data_validade'] ?>" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prazo de Renovação Automática (Anos)</label>
                    <input type="number" name="prazo_renovacao_anos" value="<?= $contrato['prazo_renovacao_anos'] ?>" min="1" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Conteúdo do Contrato / SLA</label>
                    <textarea name="conteudo_sla" rows="6" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500"><?= htmlspecialchars($contrato['conteudo_sla']) ?></textarea>
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
