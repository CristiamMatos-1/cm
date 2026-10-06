<?php
require_once APP_PATH . '/Views/layout/header.php';

use app\Helpers\UI;
use app\Helpers\Security;

$filters = [
    'todos' => 'Todos',
    'pendente' => 'Pendentes',
    'aprovado' => 'Aprovados',
    'rejeitado' => 'Rejeitados',
    'expirado' => 'Expirados',
];
?>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Orçamentos</h2>
        <p class="text-sm text-gray-500">Acompanhe, aprove ou rejeite as propostas enviadas aos clientes.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/novoOrcamento" class="inline-flex items-center justify-center bg-indigo-600 text-white px-4 py-2 rounded-lg shadow hover:bg-indigo-700 transition-colors text-sm font-medium">
        <i class="fas fa-plus mr-2"></i> Criar Orçamento
    </a>
</div>

<div class="flex gap-2 overflow-x-auto pb-2 mb-4" role="group" aria-label="Filtrar por status">
    <?php foreach ($filters as $key => $label): ?>
        <button type="button" data-budget-filter="<?= $key ?>" aria-pressed="<?= $key === 'todos' ? 'true' : 'false' ?>"
                class="filter-chip <?= $key === 'todos' ? 'is-active' : '' ?> shrink-0 inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
            <?= $label ?>
            <span class="chip-count inline-flex items-center justify-center min-w-[1.5rem] px-1.5 rounded-full bg-gray-100 text-xs font-semibold text-gray-600" data-budget-count="<?= $key ?>">0</span>
        </button>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ref.</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cliente</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Orçamento</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Valor</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Validade</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                <?php foreach ($orcamentos as $orcamento):
                    $pendente = $orcamento['status'] === 'pendente';
                    $zap = preg_replace('/\D/', '', $orcamento['cliente_telefone'] ?? '');
                ?>
                <tr data-budget-row data-budget-id="<?= (int)$orcamento['id'] ?>" data-status="<?= Security::esc($orcamento['status']) ?>" class="hover:bg-gray-50">
                    <td data-label="Ref." class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">#<?= (int)$orcamento['id'] ?></td>
                    <td data-label="Cliente" class="px-4 py-4 text-sm text-gray-900"><?= Security::esc($orcamento['cliente_nome']) ?></td>
                    <td data-label="Orçamento" class="px-4 py-4 text-sm text-gray-900 md:max-w-xs">
                        <div class="font-medium"><?= Security::esc($orcamento['titulo']) ?></div>
                        <div class="text-xs text-gray-500 truncate md:max-w-xs"><?= Security::esc($orcamento['descricao']) ?></div>
                        <?php if (!empty($orcamento['ticket_id'])): ?>
                            <div class="text-xs text-indigo-600 mt-0.5"><i class="fas fa-link"></i> Chamado #<?= (int)$orcamento['ticket_id'] ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="Valor" class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 font-bold md:text-right"><?= UI::money($orcamento['valor_total']) ?></td>
                    <td data-label="Validade" class="px-4 py-4 whitespace-nowrap text-sm text-gray-500"><?= UI::date($orcamento['data_validade']) ?></td>
                    <td data-label="Status" class="px-4 py-4 text-sm">
                        <div class="flex flex-col items-start md:items-start gap-1">
                            <span data-budget-status-badge><?= UI::budgetStatusBadge($orcamento['status']) ?></span>
                            <?php $decisionText = UI::budgetDecisionText($orcamento); ?>
                            <span data-budget-decision-text class="text-xs text-gray-500 <?= $decisionText === '' ? 'hidden' : '' ?>"><?= Security::esc($decisionText) ?></span>
                            <?php if ($orcamento['status'] === 'rejeitado' && !empty($orcamento['motivo_rejeicao'])): ?>
                                <span class="text-xs text-red-600 max-w-[16rem] break-words" title="Motivo da rejeição"><i class="fas fa-comment-dots"></i> <?= Security::esc($orcamento['motivo_rejeicao']) ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td data-label="" class="cell-actions px-4 py-4 text-sm font-medium md:text-right">
                        <div class="flex flex-wrap items-center md:justify-end gap-2">
                            <?php if ($pendente): ?>
                                <form action="<?= BASE_URL ?>/admin/aprovarOrcamento/<?= (int)$orcamento['id'] ?>" method="POST" data-budget-decision-form data-decision="aprovar" data-budget-ref="<?= (int)$orcamento['id'] ?>" data-budget-title="<?= Security::esc($orcamento['titulo']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-green-600 text-white text-xs font-semibold hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1 transition-colors">
                                        <i class="fas fa-check mr-1"></i> Aprovar
                                    </button>
                                </form>
                                <form action="<?= BASE_URL ?>/admin/rejeitarOrcamento/<?= (int)$orcamento['id'] ?>" method="POST" data-budget-decision-form data-decision="rejeitar" data-budget-ref="<?= (int)$orcamento['id'] ?>" data-budget-title="<?= Security::esc($orcamento['titulo']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-red-500 text-red-600 bg-white text-xs font-semibold hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 transition-colors">
                                        <i class="fas fa-times mr-1"></i> Rejeitar
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($zap !== ''): ?>
                                <a href="<?= BASE_URL ?>/admin/enviarWhatsAppOrcamento/<?= (int)$orcamento['id'] ?>" target="_blank" rel="noopener" class="text-green-600 hover:text-green-800 p-1" title="Enviar link de aprovação por WhatsApp"><i class="fab fa-whatsapp text-lg"></i></a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/admin/imprimirOrcamentoNovo/<?= (int)$orcamento['id'] ?>" target="_blank" rel="noopener" class="text-gray-500 hover:text-gray-800 p-1" title="Imprimir"><i class="fas fa-print"></i></a>
                            <a href="<?= BASE_URL ?>/admin/editarOrcamento/<?= (int)$orcamento['id'] ?>" class="text-indigo-600 hover:text-indigo-900 p-1" title="Detalhes / Editar"><i class="fas fa-edit"></i></a>
                            <?php if (!empty($podeExcluir)): ?>
                                <form action="<?= BASE_URL ?>/admin/excluirOrcamento/<?= (int)$orcamento['id'] ?>" method="POST" data-hide-when-approved class="<?= $orcamento['status'] === 'aprovado' ? 'hidden' : '' ?>"
                                      data-confirm="O orçamento #<?= (int)$orcamento['id'] ?> será excluído permanentemente. Esta ação não pode ser desfeita." data-confirm-title="Excluir orçamento?" data-confirm-button="Sim, excluir">
                                    <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                                    <button type="submit" data-no-loading class="text-red-600 hover:text-red-800 p-1" title="Excluir"><i class="fas fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="budget-filter-empty" class="<?= empty($orcamentos) ? '' : 'hidden' ?> px-6 py-12 text-center text-gray-500">
        <i class="fas fa-hand-holding-usd text-4xl mb-3 text-gray-300"></i>
        <p><?= empty($orcamentos) ? 'Nenhum orçamento registrado.' : 'Nenhum orçamento neste filtro.' ?></p>
    </div>
</div>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
