<?php
require_once APP_PATH . '/Views/layout/header.php';

use app\Helpers\UI;
use app\Helpers\Security;

$badges = [
    'pendente' => 'bg-yellow-100 text-yellow-800 ring-yellow-200',
    'concluido' => 'bg-green-100 text-green-800 ring-green-200',
    'cancelado' => 'bg-red-100 text-red-800 ring-red-200',
];
$labels = ['pendente' => 'Pendente', 'concluido' => 'Concluído', 'cancelado' => 'Cancelado'];
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Serviços Avulsos</h2>
        <p class="text-sm text-gray-500">Serviços pontuais registrados fora de chamados e contratos.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/novoServicoAvulso" class="inline-flex items-center justify-center bg-indigo-600 text-white px-4 py-2 rounded-lg shadow hover:bg-indigo-700 transition-colors text-sm font-medium">
        <i class="fas fa-plus mr-2"></i> Novo Serviço
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Data</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cliente</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Descrição</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Valor</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($servicos)): ?>
                    <tr><td colspan="6" class="px-6 py-10 text-center text-gray-500">Nenhum serviço avulso cadastrado.</td></tr>
                <?php endif; ?>
                <?php foreach ($servicos as $servico): ?>
                    <tr class="hover:bg-gray-50">
                        <td data-label="Data" class="px-4 py-4 whitespace-nowrap text-sm text-gray-600"><?= UI::date($servico['data_servico']) ?></td>
                        <td data-label="Cliente" class="px-4 py-4 text-sm text-gray-900"><?= Security::esc($servico['cliente_nome']) ?></td>
                        <td data-label="Descrição" class="px-4 py-4 text-sm text-gray-700 md:max-w-sm break-words"><?= Security::esc($servico['descricao']) ?></td>
                        <td data-label="Valor" class="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-900 md:text-right"><?= UI::money($servico['valor']) ?></td>
                        <td data-label="Status" class="px-4 py-4 text-sm">
                            <span class="inline-flex px-2.5 py-0.5 text-xs font-semibold rounded-full ring-1 ring-inset <?= $badges[$servico['status']] ?? 'bg-gray-100 text-gray-700 ring-gray-200' ?>">
                                <?= Security::esc($labels[$servico['status']] ?? $servico['status']) ?>
                            </span>
                        </td>
                        <td data-label="" class="cell-actions px-4 py-4 text-sm md:text-right">
                            <div class="flex items-center md:justify-end gap-3">
                                <a href="<?= BASE_URL ?>/admin/editarServicoAvulso/<?= (int)$servico['id'] ?>" class="text-indigo-600 hover:text-indigo-900" title="Editar"><i class="fas fa-edit"></i></a>
                                <form action="<?= BASE_URL ?>/admin/excluirServicoAvulso/<?= (int)$servico['id'] ?>" method="POST"
                                      data-confirm="O serviço avulso será excluído permanentemente." data-confirm-title="Excluir serviço?" data-confirm-button="Sim, excluir">
                                    <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                                    <button type="submit" data-no-loading class="text-red-600 hover:text-red-800" title="Excluir"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
