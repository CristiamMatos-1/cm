<?php
require_once APP_PATH . '/Views/layout/header.php';

use app\Helpers\UI;
use app\Helpers\Security;
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Meus Orçamentos</h2>
    <p class="text-sm text-gray-500">Confira as propostas enviadas pela nossa equipe e responda com um clique.</p>
</div>

<?php if (empty($orcamentos)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 text-center text-gray-500 py-12">
        <i class="fas fa-file-invoice text-4xl mb-3 text-gray-300"></i>
        <p>Nenhum orçamento disponível no momento.</p>
    </div>
<?php else: ?>
    <div class="space-y-5">
        <?php foreach ($orcamentos as $o):
            $pendente = $o['status'] === 'pendente';
            $decisionText = UI::budgetDecisionText($o);
        ?>
        <article data-budget-card data-budget-id="<?= (int)$o['id'] ?>" data-status="<?= Security::esc($o['status']) ?>"
                 class="bg-white rounded-xl shadow-sm border <?= $pendente ? 'border-indigo-200 bg-indigo-50/40' : 'border-gray-100' ?> p-5 hover:shadow-md transition-shadow">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-4">
                <div class="min-w-0">
                    <h3 class="text-lg font-bold text-gray-800 break-words"><?= Security::esc($o['titulo']) ?></h3>
                    <p class="text-sm text-gray-500">Orçamento #<?= (int)$o['id'] ?> &middot; Enviado em <?= UI::date($o['created_at']) ?></p>
                    <?php if (!empty($o['ticket_id'])): ?>
                        <p class="text-xs text-indigo-600 mt-1"><i class="fas fa-link"></i> Referente ao chamado #<?= (int)$o['ticket_id'] ?><?= !empty($o['tipo_servico']) ? ' (' . Security::esc($o['tipo_servico']) . ')' : '' ?></p>
                    <?php endif; ?>
                    <?php if (!empty($o['data_validade'])): ?>
                        <p class="text-xs text-gray-500 mt-1"><i class="far fa-calendar-alt"></i> Válido até <?= UI::date($o['data_validade']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="sm:text-right flex sm:flex-col items-center sm:items-end justify-between w-full sm:w-auto gap-2">
                    <p class="text-2xl font-bold text-gray-800"><?= UI::money($o['valor_total']) ?></p>
                    <div class="flex flex-col items-end gap-1">
                        <span data-budget-status-badge><?= UI::budgetStatusBadge($o['status']) ?></span>
                        <span data-budget-decision-text class="text-xs text-gray-500 <?= $decisionText === '' ? 'hidden' : '' ?>"><?= Security::esc($decisionText) ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-4 rounded-lg border border-gray-200 text-sm text-gray-700 whitespace-pre-wrap break-words">
                <strong class="block mb-2 text-gray-800">Descrição do Serviço / Peças:</strong>
                <?= Security::esc($o['descricao']) ?>
            </div>

            <?php if ($o['status'] === 'rejeitado' && !empty($o['motivo_rejeicao'])): ?>
                <p class="mt-3 text-sm text-red-700 bg-red-50 border border-red-100 rounded-lg p-3"><i class="fas fa-comment-dots mr-1"></i> <strong>Seu motivo:</strong> <?= Security::esc($o['motivo_rejeicao']) ?></p>
            <?php endif; ?>

            <div data-budget-decided-only data-for="decided" class="<?= in_array($o['status'], ['aprovado', 'rejeitado'], true) ? '' : 'hidden' ?> mt-4 p-3 rounded-lg text-sm bg-blue-50 text-blue-800">
                <i class="fas fa-info-circle mr-1"></i> Sua resposta foi registrada e não pode mais ser alterada por você. Caso precise de uma nova análise, entre em contato com a empresa: somente um administrador pode reabrir o orçamento.
            </div>

            <?php if ($pendente): ?>
                <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end border-t border-gray-200 mt-4 pt-4">
                    <form action="<?= BASE_URL ?>/client/responderOrcamento/<?= (int)$o['id'] ?>" method="POST" data-budget-decision-form data-decision="rejeitar" data-budget-ref="<?= (int)$o['id'] ?>" data-budget-title="<?= Security::esc($o['titulo']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                        <input type="hidden" name="acao" value="rejeitar">
                        <button type="submit" class="w-full sm:w-auto bg-white border border-red-500 text-red-600 hover:bg-red-50 px-4 py-2 rounded-lg text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-red-500">
                            <i class="fas fa-times mr-1"></i> Recusar
                        </button>
                    </form>
                    <form action="<?= BASE_URL ?>/client/responderOrcamento/<?= (int)$o['id'] ?>" method="POST" data-budget-decision-form data-decision="aprovar" data-budget-ref="<?= (int)$o['id'] ?>" data-budget-title="<?= Security::esc($o['titulo']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= Security::esc($csrf_token) ?>">
                        <input type="hidden" name="acao" value="aprovar">
                        <button type="submit" class="w-full sm:w-auto bg-green-600 text-white hover:bg-green-700 px-6 py-2 rounded-lg text-sm font-bold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-green-500">
                            <i class="fas fa-check mr-1"></i> Aprovar Orçamento
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once APP_PATH . '/Views/layout/footer.php'; ?>
