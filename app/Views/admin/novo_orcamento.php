<?php
require_once APP_PATH . '/Views/layout/header.php';

use app\Helpers\Security;

$inputClass = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-corpBlue-500';
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Criar Novo Orçamento</h2>
        <a href="<?= BASE_URL ?>/admin/orcamentos" class="text-gray-500 hover:text-gray-700 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form action="<?= BASE_URL ?>/admin/salvarOrcamento" method="POST">
            <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                <div>
                    <label for="cliente_id" class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                    <select id="cliente_id" name="cliente_id" required class="<?= $inputClass ?>">
                        <option value="">Selecione um cliente...</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= (int)$cliente['id'] ?>"><?= Security::esc($cliente['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="ticket_id" class="block text-sm font-medium text-gray-700 mb-1">Vincular a chamado existente (opcional)</label>
                    <select id="ticket_id" name="ticket_id" class="<?= $inputClass ?>">
                        <option value="">Nenhum chamado vinculado</option>
                        <?php foreach ($chamados_abertos as $chamado): ?>
                            <option value="<?= (int)$chamado['id'] ?>">
                                #<?= (int)$chamado['id'] ?> - <?= Security::esc($chamado['cliente_nome']) ?> (<?= Security::esc(ucfirst($chamado['status'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título do orçamento *</label>
                    <input type="text" id="titulo" name="titulo" required maxlength="150" class="<?= $inputClass ?>" placeholder="Ex: Upgrade de SSD e Memória RAM">
                </div>

                <div class="md:col-span-2">
                    <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição detalhada *</label>
                    <textarea id="descricao" name="descricao" rows="4" required class="<?= $inputClass ?>"></textarea>
                </div>

                <div>
                    <label for="data_validade" class="block text-sm font-medium text-gray-700 mb-1">Validade da proposta</label>
                    <input type="date" id="data_validade" name="data_validade" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" class="<?= $inputClass ?>">
                </div>
                <div class="hidden md:block"></div>

                <div>
                    <label for="valor_pecas" class="block text-sm font-medium text-gray-700 mb-1">Valor de peças (R$)</label>
                    <input type="text" inputmode="decimal" name="valor_pecas" id="valor_pecas" class="<?= $inputClass ?>" placeholder="0,00">
                </div>

                <div>
                    <label for="valor_mao_obra" class="block text-sm font-medium text-gray-700 mb-1">Valor de mão de obra (R$)</label>
                    <input type="text" inputmode="decimal" name="valor_mao_obra" id="valor_mao_obra" class="<?= $inputClass ?>" placeholder="0,00">
                </div>

                <div class="md:col-span-2 p-4 bg-blue-50 rounded-lg border border-blue-100 flex justify-between items-center">
                    <span class="font-medium text-corpBlue-800">Valor total estimado</span>
                    <span class="text-2xl font-bold text-corpBlue-900">R$ <span id="valor_total_display">0,00</span></span>
                </div>
                <p class="md:col-span-2 text-xs text-gray-500"><i class="fas fa-info-circle"></i> Após criar, você poderá detalhar peças, mão de obra e serviços item a item.</p>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="w-full sm:w-auto bg-corpBlue-600 text-white px-6 py-2 rounded-lg shadow hover:bg-corpBlue-700 transition-colors text-sm font-semibold">
                    <i class="fas fa-check mr-1"></i> Gerar Orçamento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    function parseMoney(value) {
        value = String(value || '').trim();
        if (value.indexOf(',') !== -1) value = value.replace(/\./g, '').replace(',', '.');
        var n = parseFloat(value.replace(/[^0-9.\-]/g, ''));
        return isNaN(n) ? 0 : n;
    }
    var pecas = document.getElementById('valor_pecas');
    var mao = document.getElementById('valor_mao_obra');
    var display = document.getElementById('valor_total_display');
    function update() {
        var total = parseMoney(pecas.value) + parseMoney(mao.value);
        display.textContent = total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    pecas.addEventListener('input', update);
    mao.addEventListener('input', update);
})();
</script>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
