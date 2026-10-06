<?php
require_once APP_PATH . '/Views/layout/header.php';

use app\Helpers\Security;

$inputClass = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-corpBlue-500';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Editar Serviço Avulso</h2>
        <a href="<?= BASE_URL ?>/admin/servicosAvulsos" class="text-gray-500 hover:text-gray-700 text-sm"><i class="fas fa-arrow-left mr-1"></i> Voltar</a>
    </div>

    <form action="<?= BASE_URL ?>/admin/salvarEdicaoServicoAvulso/<?= (int)$servico['id'] ?>" method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
        <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
            <input type="text" readonly value="<?= Security::esc($servico['cliente_nome']) ?>" class="<?= $inputClass ?> bg-gray-50 text-gray-500 cursor-not-allowed">
        </div>

        <div>
            <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição do serviço *</label>
            <textarea id="descricao" name="descricao" rows="4" required class="<?= $inputClass ?>"><?= Security::esc($servico['descricao']) ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="valor" class="block text-sm font-medium text-gray-700 mb-1">Valor (R$) *</label>
                <input type="text" inputmode="decimal" id="valor" name="valor" required value="<?= number_format($servico['valor'], 2, ',', '.') ?>" class="<?= $inputClass ?>">
            </div>
            <div>
                <label for="data_servico" class="block text-sm font-medium text-gray-700 mb-1">Data *</label>
                <input type="date" id="data_servico" name="data_servico" required value="<?= Security::esc($servico['data_servico']) ?>" class="<?= $inputClass ?>">
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="<?= $inputClass ?>">
                    <?php foreach (['pendente' => 'Pendente', 'concluido' => 'Concluído', 'cancelado' => 'Cancelado'] as $valor => $rotulo): ?>
                        <option value="<?= $valor ?>" <?= $servico['status'] === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="w-full sm:w-auto bg-corpBlue-600 text-white px-6 py-2 rounded-lg shadow hover:bg-corpBlue-700 text-sm font-semibold">
                <i class="fas fa-save mr-1"></i> Salvar alterações
            </button>
        </div>
    </form>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
