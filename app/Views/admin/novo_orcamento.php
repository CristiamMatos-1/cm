<?php require_once APP_PATH . '/Views/layout/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Criar Novo Orçamento</h2>
        <a href="<?= BASE_URL ?>/admin/orcamentos" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="<?= BASE_URL ?>/admin/salvarOrcamento" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                    <select name="cliente_id" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                        <option value="">Selecione um cliente...</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vincular a Chamado Existente (Opcional)</label>
                    <select name="ticket_id" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500">
                        <option value="">Nenhum chamado vinculado</option>
                        <?php foreach ($chamados_abertos as $chamado): ?>
                            <option value="<?= $chamado['id'] ?>">
                                #<?= $chamado['id'] ?> - <?= htmlspecialchars($chamado['cliente_nome']) ?> (<?= ucfirst($chamado['status']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Título do Orçamento *</label>
                    <input type="text" name="titulo" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="Ex: Upgrade de SSD e Memória RAM">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição Detalhada *</label>
                    <textarea name="descricao" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor de Peças (R$)</label>
                    <input type="text" name="valor_pecas" id="valor_pecas" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="0,00" onkeyup="calcularTotal()">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor de Mão de Obra (R$)</label>
                    <input type="text" name="valor_mao_obra" id="valor_mao_obra" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-corpBlue-500" placeholder="0,00" onkeyup="calcularTotal()">
                </div>
                
                <div class="md:col-span-2 p-4 bg-blue-50 rounded-lg border border-blue-100 flex justify-between items-center">
                    <span class="text-lg font-medium text-corpBlue-800">Valor Total Estimado:</span>
                    <span class="text-2xl font-bold text-corpBlue-900">R$ <span id="valor_total_display">0,00</span></span>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-corpBlue-600 text-white px-6 py-2 rounded shadow hover:bg-corpBlue-700 transition-colors">
                    Gerar Orçamento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function calcularTotal() {
    let pecasStr = document.getElementById('valor_pecas').value.replace(',', '.') || 0;
    let maoObraStr = document.getElementById('valor_mao_obra').value.replace(',', '.') || 0;
    
    let total = parseFloat(pecasStr) + parseFloat(maoObraStr);
    
    if(!isNaN(total)) {
        document.getElementById('valor_total_display').innerText = total.toFixed(2).replace('.', ',');
    }
}
</script>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
